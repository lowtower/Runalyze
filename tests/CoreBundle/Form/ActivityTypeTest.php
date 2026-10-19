<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Form;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Tag;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Form\ActivityType;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\Form\FormInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
final class ActivityTypeTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();
        $this->loginClient($this->Client, $this->Account, 'default');
    }

    private function persistTag(string $name): Tag
    {
        $tag = new Tag()
            ->setAccount($this->Account)
            ->setTag($name);

        $this->getEntityManager()
            ->persist($tag);
        $this->getEntityManager()
            ->flush();
        $this->getEntityManager()
            ->refresh($tag);

        return $tag;
    }

    private function createTransientActivity(): Training
    {
        return new Training()
            ->setAccount($this->Account)
            ->setSport($this->getDefaultAccountsRunningSport())
            ->setTime(1700000000);
    }

    private function createActivityForm(Training $activity): FormInterface
    {
        return self::getContainer()->get('form.factory')->create(ActivityType::class, $activity);
    }

    public function testSelectingAPreviouslyUnassignedExistingTagAttachesIt(): void
    {
        $existingTag = $this->persistTag('existing-tag');
        $activity = $this->createTransientActivity();
        $form = $this->createActivityForm($activity);

        $this->assertCount(0, $activity->getTag(), 'Test assumes the activity starts with no tags assigned.');

        $form->submit([
            'tag' => [(string) $existingTag->getId()],
        ], false);

        $this->assertTrue($form->isSubmitted());
        $this->assertCount(1, $activity->getTag());
        $this->assertTrue($activity->getTag()->contains($existingTag));
    }

    public function testDeselectingAPreviouslyAssignedExistingTagRemovesIt(): void
    {
        $existingTag = $this->persistTag('to-be-removed');
        $activity = $this->createTransientActivity();
        $activity->addTag($existingTag);

        $form = $this->createActivityForm($activity);
        $this->assertCount(1, $activity->getTag(), 'Test assumes the activity starts with exactly the one pre-assigned tag.');

        $form->submit([
            'tag' => [],
        ], false);

        $this->assertCount(0, $activity->getTag());
    }

    public function testEnteringABrandNewTagNameCreatesAndAttachesIt(): void
    {
        $activity = $this->createTransientActivity();
        $form = $this->createActivityForm($activity);

        $form->submit([
            'tag' => ['completely-new-tag-name'],
        ], false);

        $newTags = $this->getEntityManager()
            ->getRepository(Tag::class)->findBy([
                'account' => $this->Account->getId(),
                'tag' => 'completely-new-tag-name',
            ]);
        $this->assertCount(1, $newTags, 'The new tag must have been persisted exactly once.');
        $this->assertCount(1, $activity->getTag());
        $this->assertTrue($activity->getTag()->contains($newTags[0]));
    }

    public function testSubmittingAMixOfAnExistingTagAndANewTagNameKeepsBoth(): void
    {
        $existingTag = $this->persistTag('existing-tag');
        $activity = $this->createTransientActivity();
        $form = $this->createActivityForm($activity);

        $form->submit([
            'tag' => [(string) $existingTag->getId(), 'another-new-tag'],
        ], false);

        $this->assertCount(2, $activity->getTag());
        $this->assertTrue($activity->getTag()->contains($existingTag));
    }

    public function testSwitchingFromOneExistingTagToAnotherReplacesIt(): void
    {
        $oldTag = $this->persistTag('old-tag');
        $newTag = $this->persistTag('new-tag');
        $activity = $this->createTransientActivity();
        $activity->addTag($oldTag);

        $form = $this->createActivityForm($activity);
        $form->submit([
            'tag' => [(string) $newTag->getId()],
        ], false);

        $this->assertCount(1, $activity->getTag());
        $this->assertTrue($activity->getTag()->contains($newTag));
        $this->assertFalse($activity->getTag()->contains($oldTag));
    }
}
