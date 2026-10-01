<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentRepository;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentTypeRepository;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class EquipmentControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected EquipmentTypeRepository $EquipmentTypeRepository;

    protected EquipmentRepository $EquipmentRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();
        $this->EquipmentTypeRepository = $this->EntityManager->getRepository(EquipmentType::class);
        $this->EquipmentRepository = $this->EntityManager->getRepository(Equipment::class);
    }

    /**
     * @return array{0: KernelBrowser, 1: string}
     *
     * @throws RuntimeException if the equipment overview renders no deletable category link
     */
    protected function createAuthenticatedClientWithDeleteEquipmentCategoryCsrfToken(): array
    {
        $this->insertEquipmentTypeFor($this->Account);

        $client = $this->makeAuthenticatedClient();

        $crawler = $client->request('GET', '/my/equipment/overview');
        $link = $crawler->filter('a.color-danger[href*="/equipment/category/"]')
            ->first();

        if ($link->count() === 0) {
            throw new RuntimeException('Expected the equipment overview to render at least one deletable category link to extract a valid CSRF token from.');
        }

        $href = (string) $link->attr('href');
        parse_str((string) parse_url($href, PHP_URL_QUERY), $query);

        if (! isset($query['t'])) {
            throw new RuntimeException(sprintf('No CSRF token query parameter found in delete link "%s".', $href));
        }

        return [$client, $query['t']];
    }

    /**
     * @return array{0: KernelBrowser, 1: string}
     *
     * @throws RuntimeException if the scratch equipment's edit page renders no "danger zone" form
     */
    protected function createAuthenticatedClientWithDeleteEquipmentCsrfToken(): array
    {
        $equipmentType = $this->insertEquipmentTypeFor($this->Account);
        $equipment = $this->insertEquipmentFor($this->Account, $equipmentType);

        $client = $this->makeAuthenticatedClient();

        $crawler = $client->request('GET', '/my/equipment/' . $equipment->getId() . '/edit');
        $input = $crawler->filter('input[name="t"]')
            ->first();

        if ($input->count() === 0) {
            throw new RuntimeException('Expected the scratch equipment edit page to render a deletable "danger zone" form to extract a valid CSRF token from.');
        }

        return [$client, (string) $input->attr('value')];
    }

    protected function insertEquipmentTypeFor(Account $account): EquipmentType
    {
        $equipmentType = (new EquipmentType())->setAccount($account);

        $this->EquipmentTypeRepository->save($equipmentType);

        return $equipmentType;
    }

    protected function insertEquipmentFor(Account $account, EquipmentType $equipmentType): Equipment
    {
        $equipment = (new Equipment())
            ->setName('Test Equipment')
            ->setAccount($account)
            ->setType($equipmentType);

        $this->EquipmentRepository->save($equipment);

        return $equipment;
    }

    /**
     * The HTTP client boots its own kernel with its own EntityManager for
     * every request. Changes made through that request are correctly
     * persisted to the database, but this test's own EntityManager keeps
     * serving the entity from its identity map instead of reading the
     * updated row. Clearing it forces a fresh read from the database.
     */
    protected function reloadEquipmentType(int $id): ?EquipmentType
    {
        $this->EntityManager->clear();

        return $this->EquipmentTypeRepository->find($id);
    }

    /**
     * @see self::reloadEquipmentType()
     */
    protected function reloadEquipment(int $id): ?Equipment
    {
        $this->EntityManager->clear();

        return $this->EquipmentRepository->find($id);
    }

    public function testOverviewAction(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/equipment/overview');

        $this->assertStatusCode(200, $client);
    }

    public function testCategoryAction(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/equipment/category/add');

        $this->assertStatusCode(200, $client);
    }

    public function testCategoryTableActionRedirectsToOverviewForUnknownType(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/equipment/category/314159/table');

        $this->assertStatusCode(302, $client);
        $this->assertSame(
            '/my/equipment/overview',
            $client->getResponse()
                ->headers->get('Location')
        );
    }

    public function testCategoryTableActionRendersStatisticsForSingleAssignedSport(): void
    {
        $equipmentType = $this->insertEquipmentTypeFor($this->Account);
        $equipmentType->addSport($this->getDefaultAccountsRunningSport());

        $this->EquipmentTypeRepository->save($equipmentType);

        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/equipment/category/' . $equipmentType->getId() . '/table');

        $this->assertStatusCode(200, $client);
    }

    public function testTypeAddActionSavesValidSubmissionAndRedirectsToEdit(): void
    {
        $client = $this->makeAuthenticatedClient();
        $crawler = $client->request('GET', '/my/equipment/category/add');
        $token = $crawler->filter('input[name="equipment_category[_token]"]')
            ->attr('value');

        $existingIds = array_map(
            static fn (EquipmentType $type): ?int => $type->getId(),
            $this->EquipmentTypeRepository->findAllFor($this->Account)
        );

        $client->request('POST', '/my/equipment/category/add', [
            'equipment_category' => [
                '_token' => $token,
                'name' => 'Running Shoes',
                'input' => (string) EquipmentType::CHOICE_SINGLE,
                'maxKm' => '',
                'maxTime' => '30:00',
                'sport' => [$this->getDefaultAccountsRunningSport()->getId()],
            ],
        ]);

        $this->assertStatusCode(302, $client);

        $this->EntityManager->clear();

        $newTypes = array_values(array_filter(
            $this->EquipmentTypeRepository->findAllFor($this->Account),
            static fn (EquipmentType $type): bool => ! in_array($type->getId(), $existingIds, true)
        ));

        $this->assertCount(1, $newTypes);
        $this->assertSame(
            '/my/equipment/category/' . $newTypes[0]->getId() . '/edit',
            $client->getResponse()
                ->headers->get('Location')
        );
        $this->assertSame('Running Shoes', $newTypes[0]->getName());
        $this->assertSame(EquipmentType::CHOICE_SINGLE, $newTypes[0]->getInput());
        $this->assertSame(1800, $newTypes[0]->getMaxTime());
        $this->assertCount(1, $newTypes[0]->getSport());
    }

    public function testTypeEditActionThrowsNotFoundForUnknownId(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/equipment/category/314159/edit');

        $this->assertStatusCode(404, $client);
    }

    public function testTypeEditActionThrowsNotFoundForWrongAccount(): void
    {
        $equipmentType = $this->insertEquipmentTypeFor($this->getEmptyAccount());

        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/equipment/category/' . $equipmentType->getId() . '/edit');

        $this->assertStatusCode(404, $client);
    }

    public function testTypeEditActionSavesValidSubmissionAndRedirectsToEdit(): void
    {
        $equipmentType = $this->insertEquipmentTypeFor($this->Account);
        $id = $equipmentType->getId();

        $client = $this->makeAuthenticatedClient();
        $crawler = $client->request('GET', '/my/equipment/category/' . $id . '/edit');
        $token = $crawler->filter('input[name="equipment_category[_token]"]')
            ->attr('value');

        $client->request('POST', '/my/equipment/category/' . $id . '/edit', [
            'equipment_category' => [
                '_token' => $token,
                'name' => 'Updated Category Name',
                'maxKm' => '',
                'maxTime' => '45:00',
                'sport' => [$this->getDefaultAccountsRunningSport()->getId()],
            ],
        ]);

        $this->assertStatusCode(302, $client);
        $this->assertSame(
            '/my/equipment/category/' . $id . '/edit',
            $client->getResponse()
                ->headers->get('Location')
        );

        $updated = $this->reloadEquipmentType($id);

        $this->assertSame('Updated Category Name', $updated->getName());
        $this->assertSame(2700, $updated->getMaxTime());
        $this->assertCount(1, $updated->getSport());
    }

    public function testDeleteEquipmentTypeActionRedirectsToOverviewForInvalidCsrfToken(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/equipment/category/1/delete', [
            't' => 'invalid-token',
        ]);

        $this->assertStatusCode(302, $client);
        $this->assertSame(
            '/my/equipment/overview',
            $client->getResponse()
                ->headers->get('Location')
        );
    }

    public function testDeleteEquipmentTypeActionThrowsNotFoundForUnknownIdWithValidToken(): void
    {
        [$client, $token] = $this->createAuthenticatedClientWithDeleteEquipmentCategoryCsrfToken();

        $client->request('GET', '/my/equipment/category/314159/delete', [
            't' => $token,
        ]);

        $this->assertStatusCode(404, $client);
    }

    public function testDeleteEquipmentTypeActionThrowsNotFoundForWrongAccountWithValidToken(): void
    {
        $equipmentType = $this->insertEquipmentTypeFor($this->getEmptyAccount());

        [$client, $token] = $this->createAuthenticatedClientWithDeleteEquipmentCategoryCsrfToken();

        $client->request('GET', '/my/equipment/category/' . $equipmentType->getId() . '/delete', [
            't' => $token,
        ]);

        $this->assertStatusCode(404, $client);
    }

    public function testDeleteEquipmentTypeActionRemovesEntryForCorrectAccountWithValidToken(): void
    {
        $equipmentType = $this->insertEquipmentTypeFor($this->Account);
        $id = $equipmentType->getId();

        [$client, $token] = $this->createAuthenticatedClientWithDeleteEquipmentCategoryCsrfToken();

        $client->request('GET', '/my/equipment/category/' . $id . '/delete', [
            't' => $token,
        ]);

        $this->assertStatusCode(302, $client);
        $this->assertSame(
            '/my/equipment/overview',
            $client->getResponse()
                ->headers->get('Location')
        );
        $this->assertNull($this->reloadEquipmentType($id));
    }

    public function testEquipmentAddActionThrowsNotFoundForUnknownTypeId(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/equipment/add/314159');

        $this->assertStatusCode(404, $client);
    }

    public function testEquipmentAddActionThrowsNotFoundForWrongAccountType(): void
    {
        $equipmentType = $this->insertEquipmentTypeFor($this->getEmptyAccount());

        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/equipment/add/' . $equipmentType->getId());

        $this->assertStatusCode(404, $client);
    }

    public function testEquipmentAddActionSavesValidSubmissionAndRedirectsToCategoryEdit(): void
    {
        $equipmentType = $this->insertEquipmentTypeFor($this->Account);
        $typeId = $equipmentType->getId();

        $client = $this->makeAuthenticatedClient();
        $crawler = $client->request('GET', '/my/equipment/add/' . $typeId);
        $token = $crawler->filter('input[name="equipment[_token]"]')
            ->attr('value');

        $client->request('POST', '/my/equipment/add/' . $typeId, [
            'equipment' => [
                '_token' => $token,
                'name' => 'New Shoes',
                'additionalKm' => '10',
                'dateStart' => '01.01.2026',
                'dateEnd' => '',
                'notes' => 'created by testEquipmentAddActionSavesValidSubmissionAndRedirectsToCategoryEdit',
            ],
        ]);

        $this->assertStatusCode(302, $client);
        $this->assertSame(
            '/my/equipment/category/' . $typeId . '/edit',
            $client->getResponse()
                ->headers->get('Location')
        );

        $this->EntityManager->clear();

        $newEquipment = $this->EquipmentRepository->findByTypeId($typeId, $this->Account);

        $this->assertCount(1, $newEquipment);
        $this->assertSame('New Shoes', $newEquipment[0]->getName());
        $this->assertGreaterThan(0, $newEquipment[0]->getAdditionalKm());
        $this->assertSame(
            'created by testEquipmentAddActionSavesValidSubmissionAndRedirectsToCategoryEdit',
            $newEquipment[0]->getNotes()
        );
        $this->assertTrue($newEquipment[0]->isActive());
    }

    public function testEquipmentAddActionRerendersFormInsteadOfCrashingWhenAdditionalKmIsEmpty(): void
    {
        $equipmentType = $this->insertEquipmentTypeFor($this->Account);
        $typeId = $equipmentType->getId();

        $client = $this->makeAuthenticatedClient();
        $crawler = $client->request('GET', '/my/equipment/add/' . $typeId);
        $token = $crawler->filter('input[name="equipment[_token]"]')
            ->attr('value');

        $client->request('POST', '/my/equipment/add/' . $typeId, [
            'equipment' => [
                '_token' => $token,
                'name' => 'Incomplete Shoes',
                'additionalKm' => '',
                'dateStart' => '01.01.2026',
                'dateEnd' => '',
                'notes' => '',
            ],
        ]);

        $this->assertStatusCode(200, $client);

        $this->EntityManager->clear();

        $this->assertCount(0, $this->EquipmentRepository->findByTypeId($typeId, $this->Account));
    }

    public function testEquipmentEditActionThrowsNotFoundForUnknownId(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/equipment/314159/edit');

        $this->assertStatusCode(404, $client);
    }

    public function testEquipmentEditActionThrowsNotFoundForWrongAccount(): void
    {
        $equipmentType = $this->insertEquipmentTypeFor($this->getEmptyAccount());
        $equipment = $this->insertEquipmentFor($this->getEmptyAccount(), $equipmentType);

        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/equipment/' . $equipment->getId() . '/edit');

        $this->assertStatusCode(404, $client);
    }

    public function testEquipmentEditActionSavesValidSubmissionAndRedirectsToCategoryEdit(): void
    {
        $equipmentType = $this->insertEquipmentTypeFor($this->Account);
        $equipment = $this->insertEquipmentFor($this->Account, $equipmentType);
        $id = $equipment->getId();

        $client = $this->makeAuthenticatedClient();
        $crawler = $client->request('GET', '/my/equipment/' . $id . '/edit');
        $token = $crawler->filter('input[name="equipment[_token]"]')
            ->attr('value');

        $client->request('POST', '/my/equipment/' . $id . '/edit', [
            'equipment' => [
                '_token' => $token,
                'name' => 'Updated Shoes',
                'additionalKm' => '15',
                'dateStart' => '01.01.2026',
                'dateEnd' => '31.12.2026',
                'notes' => 'updated by testEquipmentEditActionSavesValidSubmissionAndRedirectsToCategoryEdit',
            ],
        ]);

        $this->assertStatusCode(302, $client);
        $this->assertSame(
            '/my/equipment/category/' . $equipmentType->getId() . '/edit',
            $client->getResponse()
                ->headers->get('Location')
        );

        $updated = $this->reloadEquipment($id);

        $this->assertSame('Updated Shoes', $updated->getName());
        $this->assertGreaterThan(0, $updated->getAdditionalKm());
        $this->assertSame(
            'updated by testEquipmentEditActionSavesValidSubmissionAndRedirectsToCategoryEdit',
            $updated->getNotes()
        );
        $this->assertFalse($updated->isActive());
    }

    public function testEquipmentEditActionRerendersFormInsteadOfCrashingWhenAdditionalKmIsEmpty(): void
    {
        $equipmentType = $this->insertEquipmentTypeFor($this->Account);
        $equipment = $this->insertEquipmentFor($this->Account, $equipmentType);
        $id = $equipment->getId();

        $client = $this->makeAuthenticatedClient();
        $crawler = $client->request('GET', '/my/equipment/' . $id . '/edit');
        $token = $crawler->filter('input[name="equipment[_token]"]')
            ->attr('value');

        $client->request('POST', '/my/equipment/' . $id . '/edit', [
            'equipment' => [
                '_token' => $token,
                'name' => 'Should not be saved',
                'additionalKm' => '',
                'dateStart' => '01.01.2026',
                'dateEnd' => '',
                'notes' => '',
            ],
        ]);

        $this->assertStatusCode(200, $client);
        $this->assertSame('Test Equipment', $this->reloadEquipment($id)->getName());
    }

    public function testDeleteEquipmentActionRedirectsToOverviewForInvalidCsrfToken(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/equipment/1/delete', [
            't' => 'invalid-token',
        ]);

        $this->assertStatusCode(302, $client);
        $this->assertSame(
            '/my/equipment/overview',
            $client->getResponse()
                ->headers->get('Location')
        );
    }

    public function testDeleteEquipmentActionThrowsNotFoundForUnknownIdWithValidToken(): void
    {
        [$client, $token] = $this->createAuthenticatedClientWithDeleteEquipmentCsrfToken();

        $client->request('GET', '/my/equipment/314159/delete', [
            't' => $token,
        ]);

        $this->assertStatusCode(404, $client);
    }

    public function testDeleteEquipmentActionThrowsNotFoundForWrongAccountWithValidToken(): void
    {
        $equipmentType = $this->insertEquipmentTypeFor($this->getEmptyAccount());
        $equipment = $this->insertEquipmentFor($this->getEmptyAccount(), $equipmentType);

        [$client, $token] = $this->createAuthenticatedClientWithDeleteEquipmentCsrfToken();

        $client->request('GET', '/my/equipment/' . $equipment->getId() . '/delete', [
            't' => $token,
        ]);

        $this->assertStatusCode(404, $client);
    }

    public function testDeleteEquipmentActionRemovesEntryAndRedirectsForCorrectAccountWithValidToken(): void
    {
        $equipmentType = $this->insertEquipmentTypeFor($this->Account);
        $equipment = $this->insertEquipmentFor($this->Account, $equipmentType);
        $id = $equipment->getId();

        [$client, $token] = $this->createAuthenticatedClientWithDeleteEquipmentCsrfToken();

        $client->request('GET', '/my/equipment/' . $id . '/delete', [
            't' => $token,
        ]);

        $this->assertStatusCode(302, $client);
        $this->assertSame(
            '/my/equipment/category/' . $equipmentType->getId() . '/edit',
            $client->getResponse()
                ->headers->get('Location')
        );
        $this->assertNull($this->reloadEquipment($id));
    }
}
