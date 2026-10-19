<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Settings;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Runalyze\Profile\Sport\SportProfile;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
final class SportControllerTest extends AbstractFixturesAwareWebTestCase
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
     * @throws RuntimeException if no form's name attribute contains $partialFormName
     */
    private function getFormByName(Crawler $crawler, string $partialFormName): Form
    {
        $forms = $crawler->filter('form');

        for ($i = 0; $i < $forms->count(); $i++) {
            $formNode = $forms->eq($i);
            $name = $formNode->attr('name') ?? '';

            if (str_contains($name, $partialFormName)) {
                return $formNode->form();
            }
        }

        if ($forms->count() === 1) {
            return $forms->eq(0)->form();
        }

        $availableNames = $forms->each(fn ($node) => $node->attr('name') ?? '(no name)');
        throw new RuntimeException(sprintf(
            'No form with name attribute containing "%s" found. Available forms: %s',
            $partialFormName,
            implode(', ', $availableNames)
        ));
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

    /**
     * @throws RuntimeException if the scratch sport's edit page renders no deletable type link
     */
    private function extractDeleteSportTypeCsrfToken(): string
    {
        $sport = $this->persistSportForAccount($this->Account, 'csrf-token-source-sport');
        $this->persistTypeForSport($sport, $this->Account, 'csrf-token-source-type');

        $crawler = $this->Client->request('GET', '/settings/sport/' . $sport->getId() . '/edit');
        $link = $crawler->filter('#assigned-sport-types a.color-danger')
            ->first();

        if ($link->count() === 0) {
            throw new RuntimeException('Expected the scratch sport to render a deletable activity type link to extract a valid CSRF token from.');
        }

        $href = (string) $link->attr('href');
        parse_str((string) parse_url($href, PHP_URL_QUERY), $query);

        if (! isset($query['t'])) {
            throw new RuntimeException(sprintf('No CSRF token query parameter found in delete link "%s".', $href));
        }

        return $query['t'];
    }

    /**
     * @throws RuntimeException if the scratch sport's edit page renders no "danger zone" form
     */
    private function extractDeleteSportCsrfToken(): string
    {
        $sport = $this->persistSportForAccount($this->Account, 'csrf-token-source-sport-for-delete');

        $crawler = $this->Client->request('GET', '/settings/sport/' . $sport->getId() . '/edit');
        $input = $crawler->filter('input[name="t"]')
            ->first();

        if ($input->count() === 0) {
            throw new RuntimeException('Expected the scratch sport to render a deletable "danger zone" form to extract a valid CSRF token from.');
        }

        return (string) $input->attr('value');
    }

    private function persistSportForAccount(Account $account, string $name = 'Test Sport'): Sport
    {
        $sport = new Sport()
            ->setAccount($account)
            ->setName($name);

        $this->getEntityManager()
            ->persist($sport);
        $this->getEntityManager()
            ->flush();

        $this->getEntityManager()
            ->refresh($sport);

        return $sport;
    }

    private function persistTypeForSport(Sport $sport, Account $account, string $name = 'Test Type'): Type
    {
        $type = new Type()
            ->setAccount($account)
            ->setSport($sport)
            ->setName($name);

        $this->getEntityManager()
            ->persist($type);
        $this->getEntityManager()
            ->flush();

        $this->getEntityManager()
            ->refresh($type);

        return $type;
    }

    private function reloadSport(int $id): ?Sport
    {
        $this->getEntityManager()
            ->clear();

        return $this->getEntityManager()
            ->getRepository(Sport::class)->find($id);
    }

    private function reloadType(int $id): ?Type
    {
        $this->getEntityManager()
            ->clear();

        return $this->getEntityManager()
            ->getRepository(Type::class)->find($id);
    }

    public function testOverviewActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/settings/sport');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testOverviewActionIsAccessibleForLoggedInUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/sport');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testTypeAddActionRequiresAuthentication(): void
    {
        $sport = $this->persistSportForAccount($this->Account);

        $this->Client->request('GET', '/settings/sport/' . $sport->getId() . '/type/add');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testTypeAddActionIsAccessibleForLoggedInUser(): void
    {
        $sport = $this->persistSportForAccount($this->Account);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/sport/' . $sport->getId() . '/type/add');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testSubmittingValidTypeAddFormPersistsNewTypeAndRedirects(): void
    {
        $sport = $this->persistSportForAccount($this->Account);

        $this->loginClient($this->Client, $this->Account, 'default');
        $crawler = $this->Client->request('GET', '/settings/sport/' . $sport->getId() . '/type/add');
        $form = $this->getSingleForm($crawler);

        $form[$this->findFieldName($form, '[name]')] = 'Long Run';
        $form[$this->findFieldName($form, 'abbr')] = 'LR';

        $this->Client->submit($form);

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/settings/sport/' . $sport->getId() . '/edit', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testTypeEditActionRequiresAuthentication(): void
    {
        $sport = $this->persistSportForAccount($this->Account);
        $type = $this->persistTypeForSport($sport, $this->Account);

        $this->Client->request('GET', '/settings/sport/type/' . $type->getId() . '/edit');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testTypeEditActionReturns404ForUnknownId(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/sport/type/314159/edit');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testTypeEditActionReturns404ForTypeOwnedByAnotherAccount(): void
    {
        $otherSport = $this->persistSportForAccount($this->getEmptyAccount());
        $otherType = $this->persistTypeForSport($otherSport, $this->getEmptyAccount());

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/sport/type/' . $otherType->getId() . '/edit');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testSubmittingValidTypeEditFormPersistsChanges(): void
    {
        $sport = $this->persistSportForAccount($this->Account);
        $type = $this->persistTypeForSport($sport, $this->Account, 'Original Name');

        $this->loginClient($this->Client, $this->Account, 'default');
        $crawler = $this->Client->request('GET', '/settings/sport/type/' . $type->getId() . '/edit');
        $form = $this->getSingleForm($crawler);

        $form[$this->findFieldName($form, '[name]')] = 'Updated Name';
        $form[$this->findFieldName($form, 'abbr')] = 'UPD';

        $this->Client->submit($form);
        $this->assertStatusCode(302, $this->Client);

        $reloaded = $this->reloadType($type->getId());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Type::class, $reloaded);
        $this->assertSame('Updated Name', $reloaded->getName());
    }

    public function testSubmittingTypeFormWithBlankAbbrShowsValidationErrorInsteadOfCrashing(): void
    {
        $sport = $this->persistSportForAccount($this->Account);
        $type = $this->persistTypeForSport($sport, $this->Account, 'Original Name');
        $originalAbbr = $type->getAbbr();

        $this->loginClient($this->Client, $this->Account, 'default');
        $crawler = $this->Client->request('GET', '/settings/sport/type/' . $type->getId() . '/edit');
        $form = $this->getSingleForm($crawler);

        $form[$this->findFieldName($form, 'abbr')] = '';

        $this->Client->submit($form);

        $this->assertStatusCode(200, $this->Client);

        $reloaded = $this->reloadType($type->getId());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Type::class, $reloaded);
        $this->assertSame($originalAbbr, $reloaded->getAbbr(), 'A rejected, invalid submission must not have persisted the blank value.');
    }

    public function testDeleteSportTypeActionRequiresAuthentication(): void
    {
        $sport = $this->persistSportForAccount($this->Account);
        $type = $this->persistTypeForSport($sport, $this->Account);

        $this->Client->request('GET', '/settings/sport/type/' . $type->getId() . '/delete');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testDeleteSportTypeActionWithInvalidCsrfTokenDoesNotDelete(): void
    {
        $sport = $this->persistSportForAccount($this->Account);
        $type = $this->persistTypeForSport($sport, $this->Account);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/sport/type/' . $type->getId() . '/delete?t=invalid-token');

        $this->assertStatusCode(302, $this->Client);
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Type::class, $this->reloadType($type->getId()));
    }

    public function testDeleteSportTypeActionDeletesTypeWithoutTrainings(): void
    {
        $sport = $this->persistSportForAccount($this->Account);
        $type = $this->persistTypeForSport($sport, $this->Account);
        $typeId = $type->getId();

        $this->loginClient($this->Client, $this->Account, 'default');
        $token = $this->extractDeleteSportTypeCsrfToken();

        $this->Client->request('GET', '/settings/sport/type/' . $typeId . '/delete?t=' . $token);

        $this->assertStatusCode(302, $this->Client);
        $this->assertNull($this->reloadType($typeId));
    }

    public function testDeleteSportTypeActionDoesNotDeleteTypeWithTrainings(): void
    {
        $sport = $this->persistSportForAccount($this->Account);
        $type = $this->persistTypeForSport($sport, $this->Account);

        $training = $this->getActivityForDefaultAccount(time(), 1800, null, $sport);
        $training->setType($type);
        $this->getEntityManager()
            ->persist($training);
        $this->getEntityManager()
            ->flush();

        $this->loginClient($this->Client, $this->Account, 'default');
        $token = $this->extractDeleteSportTypeCsrfToken();

        $this->Client->request('GET', '/settings/sport/type/' . $type->getId() . '/delete?t=' . $token);

        $this->assertStatusCode(302, $this->Client);
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Type::class, $this->reloadType($type->getId()));
    }

    public function testSportAddActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/settings/sport/add/custom');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testSportAddActionCustomRouteRendersForm(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/sport/add/custom');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testSportAddActionWithFreeInternalTypeImmediatelyCreatesSportAndRedirects(): void
    {
        $freeInternalTypes = $this->getEntityManager()
            ->getRepository(Sport::class)->getFreeInternalTypes($this->Account);
        $this->assertNotEmpty($freeInternalTypes, 'Test assumes at least one internal sport type is free for a freshly registered account.');
        $freeInternalTypeId = array_key_first($freeInternalTypes);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/sport/add/' . $freeInternalTypeId);

        $this->assertStatusCode(302, $this->Client);
        $this->assertStringContainsString('/settings/sport/', (string) $this->Client->getResponse()->headers->get('Location'));

        $sports = $this->getEntityManager()
            ->getRepository(Sport::class)->findBy([
                'account' => $this->Account->getId(),
            ]);
        $matching = array_filter($sports, fn (Sport $s): bool => $s->getInternalSportId() === $freeInternalTypeId);
        $this->assertNotEmpty($matching, sprintf('Expected a new Sport with internalSportId=%d to have been created.', $freeInternalTypeId));
    }

    public function testSportAddActionWithAlreadyUsedInternalTypeFallsBackToCustomForm(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/sport/add/' . SportProfile::CYCLING);

        $this->assertStatusCode(200, $this->Client);
    }

    public function testSubmittingValidCustomSportFormPersistsNewSport(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $crawler = $this->Client->request('GET', '/settings/sport/add/custom');
        $form = $this->getSingleForm($crawler);

        $form[$this->findFieldName($form, '[name]')] = 'My Custom Sport';

        $formName = $form->getName();
        $values = $form->getPhpValues();
        unset($values[$formName]['hrZoneBounds']['__name__']);

        $files = $form->getPhpFiles();
        $this->Client->request('POST', $form->getUri(), $values, $files);

        $this->assertStatusCode(302, $this->Client);

        $sports = $this->getEntityManager()
            ->getRepository(Sport::class)->findBy([
                'account' => $this->Account->getId(),
                'name' => 'My Custom Sport',
            ]);
        $this->assertCount(1, $sports);
    }

    public function testSportEditActionRequiresAuthentication(): void
    {
        $sport = $this->persistSportForAccount($this->Account);

        $this->Client->request('GET', '/settings/sport/' . $sport->getId() . '/edit');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testSportEditActionReturns404ForUnknownId(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/sport/314159/edit');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testSportEditActionReturns404ForSportOwnedByAnotherAccount(): void
    {
        $otherSport = $this->persistSportForAccount($this->getEmptyAccount());

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/sport/' . $otherSport->getId() . '/edit');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testSubmittingValidSportEditFormPersistsChanges(): void
    {
        $sport = $this->persistSportForAccount($this->Account, 'Original Sport Name');

        $this->loginClient($this->Client, $this->Account, 'default');
        $crawler = $this->Client->request('GET', '/settings/sport/' . $sport->getId() . '/edit');
        $form = $this->getFormByName($crawler, 'sport');

        $form[$this->findFieldName($form, '[name]')] = 'Updated Sport Name';

        $formName = $form->getName();
        $values = $form->getPhpValues();
        unset($values[$formName]['hrZoneBounds']['__name__']);

        $files = $form->getPhpFiles();
        $this->Client->request('POST', $form->getUri(), $values, $files);

        $this->assertStatusCode(302, $this->Client);

        $reloaded = $this->reloadSport($sport->getId());
        $this->assertSame('Updated Sport Name', $reloaded->getName());
    }

    public function testSubmittingSportFormWithBlankNameShowsValidationErrorInsteadOfCrashing(): void
    {
        $sport = $this->persistSportForAccount($this->Account, 'Original Sport Name');

        $this->loginClient($this->Client, $this->Account, 'default');
        $crawler = $this->Client->request('GET', '/settings/sport/' . $sport->getId() . '/edit');
        $form = $this->getFormByName($crawler, 'sport');

        $form[$this->findFieldName($form, '[name]')] = '';

        $formName = $form->getName();
        $values = $form->getPhpValues();
        unset($values[$formName]['hrZoneBounds']['__name__']);

        $files = $form->getPhpFiles();
        $this->Client->request('POST', $form->getUri(), $values, $files);

        $this->assertStatusCode(200, $this->Client);

        $reloaded = $this->reloadSport($sport->getId());
        $this->assertSame('Original Sport Name', $reloaded->getName(), 'A rejected, invalid submission must not have persisted the blank value.');
    }

    public function testSportDeleteActionRequiresAuthentication(): void
    {
        $sport = $this->persistSportForAccount($this->Account);

        $this->Client->request('GET', '/settings/sport/' . $sport->getId() . '/delete');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testSportDeleteActionWithInvalidCsrfTokenDoesNotDelete(): void
    {
        $sport = $this->persistSportForAccount($this->Account);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/sport/' . $sport->getId() . '/delete?t=invalid-token');

        $this->assertStatusCode(302, $this->Client);
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Sport::class, $this->reloadSport($sport->getId()));
    }

    public function testSportDeleteActionDeletesSportWithoutTrainings(): void
    {
        $sport = $this->persistSportForAccount($this->Account);
        $sportId = $sport->getId();

        $this->loginClient($this->Client, $this->Account, 'default');
        $token = $this->extractDeleteSportCsrfToken();

        $this->Client->request('GET', '/settings/sport/' . $sportId . '/delete?t=' . $token);

        $this->assertStatusCode(302, $this->Client);
        $this->assertNull($this->reloadSport($sportId));
    }

    public function testSportDeleteActionDoesNotDeleteSportWithTrainings(): void
    {
        $sport = $this->persistSportForAccount($this->Account);

        $training = $this->getActivityForDefaultAccount(time(), 1800, null, $sport);
        $this->getEntityManager()
            ->persist($training);
        $this->getEntityManager()
            ->flush();

        $this->loginClient($this->Client, $this->Account, 'default');
        $token = $this->extractDeleteSportCsrfToken();

        $this->Client->request('GET', '/settings/sport/' . $sport->getId() . '/delete?t=' . $token);

        $this->assertStatusCode(302, $this->Client);
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Sport::class, $this->reloadSport($sport->getId()));
    }
}
