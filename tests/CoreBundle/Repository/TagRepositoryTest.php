<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\Group;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Tag;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\TagRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;

#[Group('plugin')]
#[Group('requiresDoctrine')]
final class TagRepositoryTest extends AbstractRepositoryTestCase
{
    public function testTagUsageIsScopedByAccountSportAndStartDate(): void
    {
        $account = $this->getDefaultAccount();
        $running = $this->getDefaultAccountsRunningSport();
        $cycling = $this->getDefaultAccountsCyclingSport();

        $firstTag = $this->createTag($account, 'Easy');
        $secondTag = $this->createTag($account, 'Intervals');

        $this->createActivity(200, $running, [$firstTag, $secondTag]);
        $this->createActivity(300, $running, [$firstTag]);
        $this->createActivity(400, $cycling, [$secondTag]);
        $this->createActivity(100, $running, [$secondTag]);

        $repository = $this->getEntityManager()->getRepository(Tag::class);
        $this->assertInstanceOf(TagRepository::class, $repository);

        $this->assertSame([
            ['id' => $firstTag->getId(), 'tag' => 'Easy', 'usage_count' => 2],
            ['id' => $secondTag->getId(), 'tag' => 'Intervals', 'usage_count' => 1],
        ], $repository->findUsageForSportSince($account, $running->getId(), 200));

        $this->assertSame([], $repository->findUsageForSportSince($this->getEmptyAccount(), $running->getId(), 200));
    }

    private function createTag(Account $account, string $name): Tag
    {
        $tag = new Tag();
        $tag->setAccount($account)->setTag($name);

        $repository = $this->getEntityManager()->getRepository(Tag::class);
        $this->assertInstanceOf(TagRepository::class, $repository);
        $repository->save($tag);

        return $tag;
    }

    /**
     * @param list<Tag> $tags
     */
    private function createActivity(int $timestamp, \Runalyze\Bundle\CoreBundle\Entity\Sport $sport, array $tags): void
    {
        $activity = $this->getActivityForDefaultAccount($timestamp, 3600, 10.0, $sport);

        foreach ($tags as $tag) {
            $activity->addTag($tag);
        }

        $repository = $this->getEntityManager()->getRepository(Training::class);
        $this->assertInstanceOf(TrainingRepository::class, $repository);
        $repository->save($activity);
    }
}
