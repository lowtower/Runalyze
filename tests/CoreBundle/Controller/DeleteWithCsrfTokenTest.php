<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Tag;
use Runalyze\Bundle\CoreBundle\Entity\Type as ActivityType;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
class DeleteWithCsrfTokenTest extends AbstractFixturesAwareWebTestCase
{
    private const string PASSWORD = 'irrelevant-for-this-test';

    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->registerUniqueAccount();
        $this->loginClient($this->Client, $this->Account, 'default');
    }

    private function registerUniqueAccount(): Account
    {
        $account = (new Account())
            ->setUsername('delete_test_' . uniqid('', true))
            ->setMail('delete_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $account, $passwordHasher);
        $account->setPassword($passwordHasher->hashPassword($account, self::PASSWORD));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    /**
     * @param array<string, mixed> $params
     */
    private function path(string $route, array $params = []): string
    {
        return self::getContainer()->get('router')->generate($route, $params);
    }

    /**
     * @return array{class: class-string, id: int, deleteRoute: string, tokenPage: ?array{0: string, 1: array<string, mixed>}, redirect: ?string}
     */
    private function createSubject(string $kind): array
    {
        $em = $this->getEntityManager();

        switch ($kind) {
            case 'tag':
                $entity = new Tag();
                $entity->setTag('csrf-test-tag');
                $entity->setAccount($this->Account);
                $em->persist($entity);
                $em->flush();

                return [
                    'class' => Tag::class,
                    'id' => $entity->getId(),
                    'deleteRoute' => 'settings-tags-delete',
                    'tokenPage' => ['settings-tags', []],
                    'redirect' => $this->path('settings-tags'),
                ];

            case 'sport':
                $entity = $em->getRepository(Sport::class)->findOneBy([
                    'account' => $this->Account,
                    'name' => 'Gymnastics',
                ]);

                return [
                    'class' => Sport::class,
                    'id' => $entity->getId(),
                    'deleteRoute' => 'sport-delete',
                    'tokenPage' => ['settings-sports', []],
                    'redirect' => null,
                ];

            case 'sportType':
                $entity = $em->getRepository(ActivityType::class)->findOneBy([
                    'account' => $this->Account,
                    'name' => 'Warm-up',
                ]);

                return [
                    'class' => ActivityType::class,
                    'id' => $entity->getId(),
                    'deleteRoute' => 'sport-type-delete',
                    'tokenPage' => null,
                    'redirect' => null,
                ];

            case 'equipmentCategory':
                $entity = $em->getRepository(EquipmentType::class)->findOneBy([
                    'account' => $this->Account,
                    'name' => 'Bikes',
                ]);

                return [
                    'class' => EquipmentType::class,
                    'id' => $entity->getId(),
                    'deleteRoute' => 'equipment-category-delete',
                    'tokenPage' => ['equipment-overview', []],
                    'redirect' => $this->path('equipment-overview'),
                ];

            case 'equipment':
                $entity = $em->getRepository(Equipment::class)->findOneBy([
                    'account' => $this->Account,
                    'name' => 'hat',
                ]);
                $categoryId = $entity->getType()
                    ->getId();

                return [
                    'class' => Equipment::class,
                    'id' => $entity->getId(),
                    'deleteRoute' => 'equipment-delete',
                    'tokenPage' => [
                        'equipment-category-edit', [
                            'id' => $categoryId,
                        ]],
                    'redirect' => $this->path('equipment-category-edit', [
                        'id' => $categoryId,
                    ]),
                ];
        }

        throw new \InvalidArgumentException('Unknown kind: ' . $kind);
    }

    /**
     * Reads the token from the delete link on the overview page, like a browser would.
     *
     * @param array<string, mixed> $subject
     */
    private function fetchValidToken(array $subject): string
    {
        [$route, $params] = $subject['tokenPage'];

        $crawler = $this->Client->request('GET', $this->path($route, $params));
        $this->assertStatusCode(200, $this->Client);

        $deletePath = $this->path($subject['deleteRoute'], [
            'id' => $subject['id'],
        ]);
        $link = $crawler->filter('a[href*="' . $deletePath . '"]');
        $this->assertGreaterThan(0, $link->count(), 'No delete link found for ' . $deletePath);

        parse_str((string) parse_url((string) $link->first()->attr('href'), PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('t', $query);

        return (string) $query['t'];
    }

    /**
     * @param array<string, mixed> $subject
     */
    private function requestDelete(array $subject, string $method, ?string $token): void
    {
        $parameters = [];

        if ($token !== null) {
            $parameters['t'] = $token;

            if ($method === 'POST') {
                $parameters['submit'] = 'submit';
            }
        }

        $this->Client->request($method, $this->path($subject['deleteRoute'], [
            'id' => $subject['id'],
        ]), $parameters);
    }

    /**
     * @param array<string, mixed> $subject
     */
    private function entityExists(array $subject): bool
    {
        $em = $this->getEntityManager();
        $em->clear();

        return $em->find($subject['class'], $subject['id']) !== null;
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function deletableKindsAndMethods(): iterable
    {
        foreach (['tag', 'sport', 'equipmentCategory', 'equipment'] as $kind) {
            foreach (['GET', 'POST'] as $method) {
                yield $kind . ' via ' . $method => [$kind, $method];
            }
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function allKindsAndMethods(): iterable
    {
        foreach (['tag', 'sport', 'sportType', 'equipmentCategory', 'equipment'] as $kind) {
            foreach (['GET', 'POST'] as $method) {
                yield $kind . ' via ' . $method => [$kind, $method];
            }
        }
    }

    #[DataProvider('deletableKindsAndMethods')]
    public function testValidTokenDeletesTheEntity(string $kind, string $method): void
    {
        $subject = $this->createSubject($kind);
        $token = $this->fetchValidToken($subject);

        $this->requestDelete($subject, $method, $token);

        $this->assertTrue($this->Client->getResponse()->isRedirection());
        $this->assertFalse($this->entityExists($subject), 'The entity should be deleted.');

        if ($subject['redirect'] !== null) {
            $this->assertStringEndsWith($subject['redirect'], (string) $this->Client->getResponse()->headers->get('Location'));
        }
    }

    #[DataProvider('allKindsAndMethods')]
    public function testInvalidTokenKeepsTheEntity(string $kind, string $method): void
    {
        $subject = $this->createSubject($kind);

        $this->requestDelete($subject, $method, 'not-a-valid-token');

        $this->assertTrue($this->Client->getResponse()->isRedirection());
        $this->assertTrue($this->entityExists($subject), 'The entity must not be deleted.');
    }

    #[DataProvider('allKindsAndMethods')]
    public function testMissingTokenKeepsTheEntity(string $kind, string $method): void
    {
        $subject = $this->createSubject($kind);

        $this->requestDelete($subject, $method, null);

        $this->assertTrue($this->Client->getResponse()->isRedirection());
        $this->assertTrue($this->entityExists($subject), 'The entity must not be deleted.');
    }

    #[DataProvider('deletableKindsAndMethods')]
    public function testTokenOfAnotherDeleteActionIsRejected(string $kind, string $method): void
    {
        // A valid token for "deleteTag" must not work for any other delete action.
        $tag = $this->createSubject('tag');
        $tagToken = $this->fetchValidToken($tag);

        if ($kind === 'tag') {
            $kind = 'equipmentCategory';
        }

        $subject = $this->createSubject($kind);

        $this->requestDelete($subject, $method, $tagToken);

        $this->assertTrue($this->entityExists($subject), 'A token with the wrong ID must not delete anything.');
    }

    public function testEquipmentDeleteWithoutLoginIsDenied(): void
    {
        $subject = $this->createSubject('equipment');

        $this->Client->getCookieJar()
            ->clear();
        $this->requestDelete($subject, 'GET', 'whatever');

        $this->assertTrue($this->Client->getResponse()->isRedirection() || $this->Client->getResponse()->isClientError());
        $this->assertTrue($this->entityExists($subject));
    }
}
