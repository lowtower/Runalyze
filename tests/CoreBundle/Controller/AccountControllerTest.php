<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\Sha256SaltedEncoder;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
final class AccountControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();
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

    private function reloadAccount(int $id): ?Account
    {
        $this->getEntityManager()
            ->clear();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($id);
    }

    public function testDeleteAccountActionShowsProblemPageForUnknownHash(): void
    {
        $this->Client->request('GET', '/en/account/delete/00000000000000000000000000000000');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testDeleteAccountActionShowsConfirmationPageForKnownHash(): void
    {
        $this->Account->setNewDeletionHash();
        $this->getEntityManager()
            ->flush();
        $hash = $this->Account->getDeletionHash();

        $crawler = $this->Client->request('GET', '/en/account/delete/' . $hash);

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString($this->Account->getUsername(), $crawler->filter('body')->text());
    }

    public function testDeleteAccountConfirmedActionShowsProblemPageForUnknownHash(): void
    {
        $this->Client->request('GET', '/en/account/delete/00000000000000000000000000000000/confirmed');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testDeleteAccountConfirmedActionDeletesTheAccount(): void
    {
        $this->Account->setNewDeletionHash();
        $this->getEntityManager()
            ->flush();
        $hash = $this->Account->getDeletionHash();
        $accountId = $this->Account->getId();

        $this->Client->request('GET', '/en/account/delete/' . $hash . '/confirmed');

        $this->assertStatusCode(200, $this->Client);
        $this->assertNull($this->reloadAccount($accountId));
    }

    public function testDeleteAccountConfirmedActionInvalidatesSessionOfLoggedInAccountBeingDeleted(): void
    {
        $this->Account->setNewDeletionHash();
        $this->getEntityManager()
            ->flush();
        $hash = $this->Account->getDeletionHash();

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/account');
        $this->assertStatusCode(200, $this->Client);

        $this->Client->request('GET', '/en/account/delete/' . $hash . '/confirmed');
        $this->assertStatusCode(200, $this->Client);

        $this->Client->request('GET', '/settings/account');
        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testDeleteAccountConfirmedActionDoesNotInvalidateSessionOfADifferentLoggedInAccount(): void
    {
        $otherAccount = $this->getEmptyAccount();
        $otherAccount->setNewDeletionHash();
        $this->getEntityManager()
            ->flush();
        $hash = $otherAccount->getDeletionHash();

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/account');
        $this->assertStatusCode(200, $this->Client);

        $this->Client->request('GET', '/en/account/delete/' . $hash . '/confirmed');
        $this->assertStatusCode(200, $this->Client);

        $this->Client->request('GET', '/settings/account');
        $this->assertStatusCode(200, $this->Client);
    }

    public function testActivateAccountActionActivatesAccountWithValidHash(): void
    {
        $this->Account->setNewActivationHash();
        $this->getEntityManager()
            ->flush();
        $hash = $this->Account->getActivationHash();
        $accountId = $this->Account->getId();

        $this->Client->request('GET', '/en/account/activate/' . $hash);

        $this->assertStatusCode(200, $this->Client);
        $reloaded = $this->reloadAccount($accountId);
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Account::class, $reloaded);
        $this->assertNull($reloaded->getActivationHash());
    }

    public function testActivateAccountActionShowsAlreadyActivatedForKnownUsernameAndInvalidHash(): void
    {
        $this->Client->request(
            'GET',
            '/en/account/activate/00000000000000000000000000000000/' . $this->Account->getUsername()
        );

        $this->assertStatusCode(200, $this->Client);
    }

    public function testActivateAccountActionShowsProblemForUnknownHashAndNoUsername(): void
    {
        $this->Client->request('GET', '/en/account/activate/00000000000000000000000000000000');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testRecoverActionRendersFormOnGet(): void
    {
        $this->Client->request('GET', '/en/account/recover');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testSubmittingRecoverActionWithUnknownUsernameShowsError(): void
    {
        $crawler = $this->Client->request('GET', '/en/account/recover');
        $form = $this->getFormByName($crawler, 'form');

        $form[$this->findFieldName($form, 'username')] = 'this-user-does-not-exist';

        $crawler = $this->Client->submit($form);

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('not known', $crawler->filter('body')->text());
    }

    public function testSubmittingRecoverActionWithKnownUsernameSetsChangePasswordHash(): void
    {
        $this->assertNull($this->Account->getChangepwHash());

        $crawler = $this->Client->request('GET', '/en/account/recover');
        $form = $this->getFormByName($crawler, 'form');

        $form[$this->findFieldName($form, 'username')] = $this->Account->getUsername();

        $this->Client->submit($form);

        $this->assertStatusCode(200, $this->Client);

        $reloaded = $this->reloadAccount($this->Account->getId());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Account::class, $reloaded);
        $this->assertNotNull($reloaded->getChangepwHash());
    }

    public function testRecoverForHashActionShowsInvalidHashPageForUnknownHash(): void
    {
        $this->Client->request('GET', '/en/account/recover/00000000000000000000000000000000');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testRecoverForHashActionRendersFormForValidHash(): void
    {
        $this->Account->setNewChangePasswordHash();
        $this->getEntityManager()
            ->flush();
        $hash = $this->Account->getChangepwHash();

        $crawler = $this->Client->request('GET', '/en/account/recover/' . $hash);

        $this->assertStatusCode(200, $this->Client);
        $form = $this->getFormByName($crawler, 'recover_password');
        $this->findFieldName($form, 'plainPassword');
    }

    public function testSubmittingValidRecoverForHashFormChangesPasswordAndRemovesHash(): void
    {
        $this->Account->setNewChangePasswordHash();
        $this->getEntityManager()
            ->flush();
        $hash = $this->Account->getChangepwHash();
        $originalPasswordHash = $this->Account->getPassword();

        $crawler = $this->Client->request('GET', '/en/account/recover/' . $hash);
        $form = $this->getFormByName($crawler, 'recover_password');

        $newPasswordFirstField = $this->findFieldName($form, 'plainPassword][first]');
        $newPasswordSecondField = $this->findFieldName($form, 'plainPassword][second]');

        $form[$newPasswordFirstField] = 'brand-new-password-123';
        $form[$newPasswordSecondField] = 'brand-new-password-123';

        $this->Client->submit($form);

        $this->assertStatusCode(200, $this->Client);

        $reloaded = $this->reloadAccount($this->Account->getId());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Account::class, $reloaded);
        $this->assertNotSame($originalPasswordHash, $reloaded->getPassword());
        $this->assertNull($reloaded->getChangepwHash());

        $encoder = new Sha256SaltedEncoder();
        $this->assertTrue($encoder->verify($reloaded->getPassword(), 'brand-new-password-123', $reloaded->getSalt()));
    }

    public function testSubmittingRecoverForHashFormWithMismatchedPasswordsDoesNotChangePassword(): void
    {
        $this->Account->setNewChangePasswordHash();
        $this->getEntityManager()
            ->flush();
        $hash = $this->Account->getChangepwHash();
        $originalPasswordHash = $this->Account->getPassword();

        $crawler = $this->Client->request('GET', '/en/account/recover/' . $hash);
        $form = $this->getFormByName($crawler, 'recover_password');

        $newPasswordFirstField = $this->findFieldName($form, 'plainPassword][first]');
        $newPasswordSecondField = $this->findFieldName($form, 'plainPassword][second]');

        $form[$newPasswordFirstField] = 'brand-new-password-123';
        $form[$newPasswordSecondField] = 'does-not-match';

        $this->Client->submit($form);

        $this->assertStatusCode(200, $this->Client);

        $reloaded = $this->reloadAccount($this->Account->getId());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Account::class, $reloaded);
        $this->assertSame($originalPasswordHash, $reloaded->getPassword());
        $this->assertNotNull($reloaded->getChangepwHash());
    }
}
