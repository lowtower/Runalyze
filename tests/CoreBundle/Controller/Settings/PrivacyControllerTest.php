<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Settings;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\DomCrawler\Form;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
final class PrivacyControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();
    }

    /**
     * @throws RuntimeException if the page does not contain exactly one form
     */
    private function getPrivacyForm(Crawler $crawler): Form
    {
        $forms = $crawler->filter('form');

        if ($forms->count() !== 1) {
            throw new RuntimeException(sprintf('Expected exactly one form on the privacy settings page, found %d.', $forms->count()));
        }

        return $forms->form();
    }

    /**
     * @throws RuntimeException if no field name contains $partialName
     */
    private function findFieldName(Form $form, string $partialName): string
    {
        foreach (array_keys($form->all()) as $name) {
            if (str_contains($name, $partialName)) {
                return $name;
            }
        }

        throw new RuntimeException(sprintf(
            'No field containing "%s" found. Available fields: %s',
            $partialName,
            implode(', ', array_keys($form->all()))
        ));
    }

    private function getChoiceField(Form $form, string $name): ChoiceFormField
    {
        /** @var ChoiceFormField $field */
        $field = $form[$name];

        return $field;
    }

    public function testSettingsAccountActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/settings/privacy');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testSettingsAccountActionIsAccessibleForLoggedInUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/privacy');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testSubmittingValidPrivacyFormPersistsValuesAndPrefillsFormOnNextLoad(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $crawler = $this->Client->request('GET', '/settings/privacy');
        $form = $this->getPrivacyForm($crawler);

        $athletePageActiveField = $this->findFieldName($form, 'AthletePageActive');
        $showPrivateInListField = $this->findFieldName($form, 'ShowPrivateInList');
        $showStatisticsInListField = $this->findFieldName($form, 'ShowStatisticsInList');
        $mapPrivacyField = $this->findFieldName($form, 'MapPrivacy');

        $this->getChoiceField($form, $athletePageActiveField)
            ->tick();
        $this->getChoiceField($form, $showPrivateInListField)
            ->tick();
        $this->getChoiceField($form, $showStatisticsInListField)
            ->untick();
        $this->getChoiceField($form, $mapPrivacyField)
            ->select('race');

        $this->Client->submit($form);
        $this->assertStatusCode(200, $this->Client);

        $secondCrawler = $this->Client->request('GET', '/settings/privacy');
        $secondForm = $this->getPrivacyForm($secondCrawler);

        /** @var FormField $reloadedAthletePageActiveField */
        $reloadedAthletePageActiveField = $secondForm->get($athletePageActiveField);
        /** @var FormField $reloadedShowPrivateInListField */
        $reloadedShowPrivateInListField = $secondForm->get($showPrivateInListField);
        /** @var FormField $reloadedShowStatisticsInListField */
        $reloadedShowStatisticsInListField = $secondForm->get($showStatisticsInListField);

        $this->assertTrue($reloadedAthletePageActiveField->hasValue());
        $this->assertTrue($reloadedShowPrivateInListField->hasValue());
        $this->assertFalse($reloadedShowStatisticsInListField->hasValue());
        $this->assertSame('race', $secondForm->get($mapPrivacyField)->getValue());
    }

    public function testSubmittingAnInvalidMapPrivacyValueDoesNotPersistItAndDoesNotCrash(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $crawler = $this->Client->request('GET', '/settings/privacy');
        $form = $this->getPrivacyForm($crawler);
        $mapPrivacyField = $this->findFieldName($form, 'MapPrivacy');
        $this->getChoiceField($form, $mapPrivacyField)
            ->select('never');
        $this->Client->submit($form);
        $this->assertStatusCode(200, $this->Client);

        $formName = explode('[', $mapPrivacyField)[0];
        $rawValues = $form->getPhpValues();
        $rawValues[$formName]['MapPrivacy'] = 'not-a-valid-choice';

        $this->Client->request('POST', '/settings/privacy', $rawValues);

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString('500 Internal Server Error', (string) $this->Client->getResponse()->getContent());

        $verificationCrawler = $this->Client->request('GET', '/settings/privacy');
        $verificationForm = $this->getPrivacyForm($verificationCrawler);

        $this->assertSame(
            'never',
            $verificationForm->get($mapPrivacyField)
                ->getValue(),
            'An invalid MapPrivacy value must not overwrite the previously persisted valid value.'
        );
    }
}
