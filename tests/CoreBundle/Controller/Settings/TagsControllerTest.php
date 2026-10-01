<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Settings;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Tag;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class TagsControllerTest extends AbstractFixturesAwareWebTestCase
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
     * @throws RuntimeException if the overview page renders no delete link to read a token from
     */
    private function extractDeleteTagCsrfToken(): string
    {
        $this->persistTagForAccount($this->Account, 'token-source-tag');

        $crawler = $this->Client->request('GET', '/settings/tags');
        $link = $crawler->filter('a.color-danger')
            ->first();

        if ($link->count() === 0) {
            throw new RuntimeException('Expected the tags overview to render at least one delete link to extract a valid CSRF token from.');
        }

        $href = (string) $link->attr('href');
        parse_str((string) parse_url($href, PHP_URL_QUERY), $query);

        if (! isset($query['t'])) {
            throw new RuntimeException(sprintf('No CSRF token query parameter found in delete link "%s".', $href));
        }

        return $query['t'];
    }

    private function persistTagForAccount(Account $account, string $name = 'test-tag'): Tag
    {
        $tag = (new Tag())
            ->setAccount($account)
            ->setTag($name);

        $this->getEntityManager()
            ->persist($tag);
        $this->getEntityManager()
            ->flush();
        $this->getEntityManager()
            ->refresh($tag);

        return $tag;
    }

    private function reloadTag(int $id): ?Tag
    {
        $this->getEntityManager()
            ->clear();

        return $this->getEntityManager()
            ->getRepository(Tag::class)->find($id);
    }

    public function testOverviewActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/settings/tags');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testOverviewActionIsAccessibleForLoggedInUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/tags');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testOverviewActionListsExistingTags(): void
    {
        $this->persistTagForAccount($this->Account, 'zebra-tag');
        $this->persistTagForAccount($this->Account, 'alpha-tag');

        $this->loginClient($this->Client, $this->Account, 'default');
        $crawler = $this->Client->request('GET', '/settings/tags');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('alpha-tag', $crawler->filter('body')->text());
        $this->assertStringContainsString('zebra-tag', $crawler->filter('body')->text());
    }

    public function testTagEditActionRequiresAuthentication(): void
    {
        $tag = $this->persistTagForAccount($this->Account);

        $this->Client->request('GET', '/settings/tags/' . $tag->getId() . '/edit');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testTagEditActionReturns404ForUnknownId(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/tags/314159/edit');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testTagEditActionReturns404ForTagOwnedByAnotherAccount(): void
    {
        $otherTag = $this->persistTagForAccount($this->getEmptyAccount());

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/tags/' . $otherTag->getId() . '/edit');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testSubmittingValidTagEditFormPersistsChanges(): void
    {
        $tag = $this->persistTagForAccount($this->Account, 'original-tag');

        $this->loginClient($this->Client, $this->Account, 'default');
        $crawler = $this->Client->request('GET', '/settings/tags/' . $tag->getId() . '/edit');
        $form = $this->getFormByName($crawler, 'tag');

        $form[$this->findFieldName($form, '[tag]')] = 'updated-tag';

        $this->Client->submit($form);
        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/settings/tags', $this->Client->getResponse()->headers->get('Location'));

        $reloaded = $this->reloadTag($tag->getId());
        $this->assertSame('updated-tag', $reloaded->getTag());
    }

    public function testSubmittingTagEditFormWithBlankTagShowsValidationErrorInsteadOfCrashing(): void
    {
        $tag = $this->persistTagForAccount($this->Account, 'original-tag');

        $this->loginClient($this->Client, $this->Account, 'default');
        $crawler = $this->Client->request('GET', '/settings/tags/' . $tag->getId() . '/edit');
        $form = $this->getFormByName($crawler, 'tag');

        $form[$this->findFieldName($form, '[tag]')] = '';

        $this->Client->submit($form);

        $this->assertStatusCode(200, $this->Client);

        $reloaded = $this->reloadTag($tag->getId());
        $this->assertSame('original-tag', $reloaded->getTag());
    }

    public function testTagDeleteActionRequiresAuthentication(): void
    {
        $tag = $this->persistTagForAccount($this->Account);

        $this->Client->request('GET', '/settings/tags/' . $tag->getId() . '/delete');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testTagDeleteActionReturns404ForUnknownId(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $token = $this->extractDeleteTagCsrfToken();

        $this->Client->request('GET', '/settings/tags/314159/delete?t=' . $token);

        $this->assertStatusCode(404, $this->Client);
    }

    public function testTagDeleteActionReturns404ForTagOwnedByAnotherAccount(): void
    {
        $otherTag = $this->persistTagForAccount($this->getEmptyAccount());

        $this->loginClient($this->Client, $this->Account, 'default');
        $token = $this->extractDeleteTagCsrfToken();

        $this->Client->request('GET', '/settings/tags/' . $otherTag->getId() . '/delete?t=' . $token);

        $this->assertStatusCode(404, $this->Client);
    }

    public function testTagDeleteActionWithInvalidCsrfTokenDoesNotDelete(): void
    {
        $tag = $this->persistTagForAccount($this->Account);
        $tagId = $tag->getId();

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/settings/tags/' . $tagId . '/delete?t=invalid-token');

        $this->assertStatusCode(302, $this->Client);
        $this->assertNotNull($this->reloadTag($tagId));
    }

    public function testTagDeleteActionDeletesTag(): void
    {
        $tag = $this->persistTagForAccount($this->Account, 'tag-to-be-deleted');
        $tagId = $tag->getId();

        $this->loginClient($this->Client, $this->Account, 'default');
        $crawler = $this->Client->request('GET', '/settings/tags');

        $deleteLink = $crawler->filter(sprintf('a.color-danger[href*="/settings/tags/%d/delete"]', $tagId));
        $this->assertGreaterThan(0, $deleteLink->count(), 'Expected the overview to render a delete link for the freshly created tag.');

        $this->Client->request('GET', (string) $deleteLink->attr('href'));

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/settings/tags', $this->Client->getResponse()->headers->get('Location'));
        $this->assertNull($this->reloadTag($tagId));
    }
}
