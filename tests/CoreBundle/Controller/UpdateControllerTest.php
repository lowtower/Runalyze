<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class UpdateControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (self::getContainer()->getParameter('app.update_disabled') !== 'no') {
            self::markTestSkipped('The /update routes are disabled (app.update_disabled != "no") in this environment.');
        }
    }

    private function poisonShellVerbosityAsQuiet(): void
    {
        putenv('SHELL_VERBOSITY=-1');
    }

    public function testUpdateActionIsReachableWithoutBeingLoggedIn(): void
    {
        $this->Client->request('GET', '/update');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testUpdateActionRendersRealMigrationCommandOutput(): void
    {
        $this->Client->request('GET', '/update');

        $this->assertStatusCode(200, $this->Client);

        $content = (string) $this->Client->getResponse()
            ->getContent();
        $this->assertTrue(
            str_contains($content, 'Already at latest version')
                || str_contains($content, 'New migrations')
                || str_contains($content, 'metadata storage is not up to date'),
            'Expected either a real migration status table or the known metadata-storage error to appear in the response.'
        );
    }

    public function testUpdateActionSucceedsEvenWhenShellVerbosityWasLeftQuietByAnEarlierCommand(): void
    {
        $this->poisonShellVerbosityAsQuiet();

        $this->Client->request('GET', '/update');

        $this->assertStatusCode(200, $this->Client);
        $content = (string) $this->Client->getResponse()
            ->getContent();
        $this->assertStringContainsString('doctrine:migrations:status', $content);
    }
}
