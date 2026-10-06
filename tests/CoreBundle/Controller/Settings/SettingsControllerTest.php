<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Settings;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Dataset;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Runalyze\Dataset\Keys;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class SettingsControllerTest extends AbstractFixturesAwareWebTestCase
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
    private function getSingleForm(Crawler $crawler): Form
    {
        $forms = $crawler->filter('form');

        if ($forms->count() !== 1) {
            throw new RuntimeException(sprintf('Expected exactly one form on the page, found %d.', $forms->count()));
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

    public function testLoadedAccountHasNoValidationViolations(): void
    {
        $validator = static::getContainer()->get('validator');
        $this->assertInstanceOf(ValidatorInterface::class, $validator);

        $violations = [];

        foreach ($validator->validate($this->Account) as $violation) {
            $violations[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
        }

        $this->assertSame([], $violations);
    }

    public function testSettingsAccountActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/settings/account');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testSettingsAccountActionIsAccessibleForLoggedInUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/account');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testSubmittingValidAccountFormPersistsNameAndPrefillsFormOnNextLoad(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $crawler = $this->Client->request('GET', '/settings/account');
        $form = $this->getSingleForm($crawler);

        $nameField = $this->findFieldName($form, '[name]');
        $form[$nameField] = 'Updated Test Name';

        $this->Client->submit($form);
        $this->assertStatusCode(200, $this->Client);

        $secondCrawler = $this->Client->request('GET', '/settings/account');
        $secondForm = $this->getSingleForm($secondCrawler);

        $this->assertSame('Updated Test Name', $secondForm[$nameField]->getValue());
    }

    public function testSubmittingAccountFormWithResetConfigurationFlagShowsSuccessMessage(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $crawler = $this->Client->request('GET', '/settings/account');
        $form = $this->getSingleForm($crawler);
        $resetField = $this->findFieldName($form, 'reset_configuration');

        $this->getChoiceField($form, $resetField)
            ->tick();
        $crawler = $this->Client->submit($form);

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Default configuration has been restored', $crawler->filter('body')->text());
    }

    public function testSettingsPasswordActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/settings/password');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testSettingsPasswordActionIsAccessibleForLoggedInUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/password');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testSubmittingPasswordFormWithWrongCurrentPasswordDoesNotChangeIt(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $originalPasswordHash = $this->Account->getPassword();

        $crawler = $this->Client->request('GET', '/settings/password');
        $form = $this->getSingleForm($crawler);

        $currentPasswordField = $this->findFieldName($form, '[password]');
        $newPasswordFirstField = $this->findFieldName($form, 'plainPassword][first]');
        $newPasswordSecondField = $this->findFieldName($form, 'plainPassword][second]');

        $form[$currentPasswordField] = 'definitely-the-wrong-password';
        $form[$newPasswordFirstField] = 'brand-new-password-123';
        $form[$newPasswordSecondField] = 'brand-new-password-123';

        $this->Client->submit($form);
        $this->assertStatusCode(200, $this->Client);

        $this->getEntityManager()
            ->clear();
        $reloadedAccount = $this->getEntityManager()
            ->getRepository(Account::class)->find($this->Account->getId());

        $this->assertSame($originalPasswordHash, $reloadedAccount->getPassword());
    }

    public function testSubmittingPasswordFormWithMismatchedNewPasswordsDoesNotChangeIt(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $originalPasswordHash = $this->Account->getPassword();

        $crawler = $this->Client->request('GET', '/settings/password');
        $form = $this->getSingleForm($crawler);

        $newPasswordFirstField = $this->findFieldName($form, 'plainPassword][first]');
        $newPasswordSecondField = $this->findFieldName($form, 'plainPassword][second]');

        $form[$newPasswordFirstField] = 'brand-new-password-123';
        $form[$newPasswordSecondField] = 'does-not-match';

        $this->Client->submit($form);
        $this->assertStatusCode(200, $this->Client);

        $this->getEntityManager()
            ->clear();
        $reloadedAccount = $this->getEntityManager()
            ->getRepository(Account::class)->find($this->Account->getId());

        $this->assertSame($originalPasswordHash, $reloadedAccount->getPassword());
    }

    public function testSubmittingValidPasswordFormChangesThePasswordHash(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $originalPasswordHash = $this->Account->getPassword();

        $crawler = $this->Client->request('GET', '/settings/password');
        $form = $this->getSingleForm($crawler);

        $currentPasswordField = $this->findFieldName($form, '[password]');
        $newPasswordFirstField = $this->findFieldName($form, 'plainPassword][first]');
        $newPasswordSecondField = $this->findFieldName($form, 'plainPassword][second]');

        $form[$currentPasswordField] = 'defaultPassword';
        $form[$newPasswordFirstField] = 'brand-new-password-123';
        $form[$newPasswordSecondField] = 'brand-new-password-123';

        $this->Client->submit($form);
        $this->assertStatusCode(200, $this->Client);

        $this->getEntityManager()
            ->clear();
        $reloadedAccount = $this->getEntityManager()
            ->getRepository(Account::class)->find($this->Account->getId());

        $this->assertNotSame($originalPasswordHash, $reloadedAccount->getPassword());
    }

    public function testSettingsMailActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/settings/mail');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testSettingsMailActionIsAccessibleForLoggedInUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/mail');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testSubmittingMailFormWithoutCurrentPasswordDoesNotChangeMailAddress(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $originalMail = $this->Account->getMail();

        $crawler = $this->Client->request('GET', '/settings/mail');
        $form = $this->getSingleForm($crawler);

        $values = $form->getPhpValues();
        $values[$form->getName()]['mail'] = 'new-address@example.test';

        $this->Client->request('POST', '/settings/mail', $values);
        $this->assertStatusCode(200, $this->Client);

        $this->getEntityManager()
            ->clear();
        $reloadedAccount = $this->getEntityManager()
            ->getRepository(Account::class)->find($this->Account->getId());

        $this->assertSame($originalMail, $reloadedAccount->getMail());
    }

    public function testSubmittingMailFormWithCorrectPasswordChangesTheMailAddress(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $crawler = $this->Client->request('GET', '/settings/mail');
        $form = $this->getSingleForm($crawler);
        $this->findFieldName($form, 'password');

        $values = $form->getPhpValues();
        $values[$form->getName()]['password'] = 'defaultPassword';
        $values[$form->getName()]['mail'] = 'new-address@example.test';

        $this->Client->request('POST', '/settings/mail', $values);
        $this->assertStatusCode(200, $this->Client);

        $this->getEntityManager()
            ->clear();
        $reloadedAccount = $this->getEntityManager()
            ->getRepository(Account::class)->find($this->Account->getId());

        $this->assertSame('new-address@example.test', $reloadedAccount->getMail());
    }

    public function testWindowDeleteActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/settings/account/delete');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testWindowDeleteActionSetsADeletionHashForTheAccount(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->assertNull($this->Account->getDeletionHash());

        $this->Client->request('GET', '/settings/account/delete');

        $this->assertStatusCode(200, $this->Client);

        $this->getEntityManager()
            ->clear();
        $reloadedAccount = $this->getEntityManager()
            ->getRepository(Account::class)->find($this->Account->getId());

        $this->assertNotNull($reloadedAccount->getDeletionHash());
    }

    /**
     * @param array<string, string|null> $fields field name => new value
     * @return array<string, mixed>
     */
    private function withDatasetFieldsSet(Form $form, int $keyId, array $fields): array
    {
        $values = $form->getPhpValues();
        $formName = $form->getName();

        $datasets = $values[$formName]['datasets'] ?? null;

        if (! is_array($datasets)) {
            throw new RuntimeException('Expected a "datasets" collection in the form values.');
        }

        foreach ($datasets as $index => $entry) {
            if ((int) ($entry['keyId'] ?? -1) === $keyId) {
                foreach ($fields as $field => $value) {
                    if ($value === null) {
                        unset($values[$formName]['datasets'][$index][$field]);
                    } else {
                        $values[$formName]['datasets'][$index][$field] = $value;
                    }
                }

                return $values;
            }
        }

        throw new RuntimeException(sprintf('No dataset entry found for keyId=%d.', $keyId));
    }

    private function findDatasetForAccount(Account $account, int $keyId): ?Dataset
    {
        $this->getEntityManager()
            ->clear();

        return $this->getEntityManager()
            ->getRepository(Dataset::class)
            ->find([
                'account' => $account->getId(),
                'keyid' => $keyId,
            ]);
    }

    public function testDatasetActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/settings/dataset');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testDatasetActionIsAccessibleForLoggedInUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/dataset');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testDatasetPostActionRequiresAuthentication(): void
    {
        $this->Client->request('POST', '/settings/dataset');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testSubmittingDatasetFormActivatesAPreviouslyInactiveKeyAndChangesItsStyle(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $crawler = $this->Client->request('GET', '/settings/dataset');
        $form = $this->getSingleForm($crawler);

        $keyId = Keys::TOOLS;
        $this->assertNull(
            $this->findDatasetForAccount($this->Account, $keyId),
            'Test assumes Keys::TOOLS has no pre-existing row for the default account fixture.'
        );

        $values = $this->withDatasetFieldsSet($form, $keyId, [
            'active' => '1',
            'style' => 'my-custom-style',
        ]);

        $this->Client->request('POST', '/settings/dataset', $values);
        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/settings/dataset', $this->Client->getResponse()->headers->get('Location'));

        $persisted = $this->findDatasetForAccount($this->Account, $keyId);

        $this->assertNotNull($persisted);
        $this->assertTrue($persisted->getActive());
        $this->assertSame('my-custom-style', $persisted->getStyle());
    }

    public function testSubmittingDatasetFormWithNonNumericPositionDoesNotPersistChanges(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $crawler = $this->Client->request('GET', '/settings/dataset');
        $form = $this->getSingleForm($crawler);

        $keyId = Keys::TOOLS;
        $this->assertNull(
            $this->findDatasetForAccount($this->Account, $keyId),
            'Test assumes Keys::TOOLS has no pre-existing row for the default account fixture.'
        );

        $values = $this->withDatasetFieldsSet($form, $keyId, [
            'active' => '1',
            'position' => 'not-a-number',
        ]);

        $this->Client->request('POST', '/settings/dataset', $values);

        $this->assertStatusCode(302, $this->Client);
        $this->assertNull(
            $this->findDatasetForAccount($this->Account, $keyId),
            'An invalid form submission must not persist any dataset changes.'
        );
    }
}
