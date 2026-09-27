<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Activity;

use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * These tests exercise EditController through real HTTP requests against the actual routes.
 *
 * The elevation correction tests are limited to branches that do not perform any real HTTP call
 * to an external elevation provider (Geonames/Google Maps): the "no route present" case and the
 * "strategy=none" (remove corrected elevation) case.
 */
#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
class EditControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->registerUniqueAccount();
    }

    private function registerUniqueAccount(): Account
    {
        $account = (new Account())
            ->setUsername('edit_test_' . uniqid('', true))
            ->setMail('edit_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordEncoderInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_encoder.generic');

        $registration = new Registration($this->getEntityManager(), $account, $passwordEncoder);
        $account->setPassword($passwordEncoder->encodePassword($account, 'irrelevant-for-this-test'));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    private function getRunningSportForAccount(Account $account): Sport
    {
        return $this->getEntityManager()
            ->getRepository(Sport::class)
            ->findOneBy([
                'account' => $account->getId(),
                'name' => 'Running',
            ]);
    }

    private function getTrainingRepository(): TrainingRepository
    {
        return $this->getEntityManager()
            ->getRepository(Training::class);
    }

    private function createTrainingForAccount(Account $account, int $time, bool $withRoute = false): Training
    {
        // Training::$s defaults to 0.0, but is constrained by Assert\GreaterThan(0). Any test
        // that submits the ActivityType edit form (even unmodified, pre-filled values obtained
        // via the DomCrawler) would otherwise fail validation on this field regardless of what
        // else is being tested, causing the controller to re-render the form as invalid instead
        // of persisting the submission.
        $activity = (new Training())
            ->setAccount($account)
            ->setSport($this->getRunningSportForAccount($account))
            ->setTime($time)
            ->setS(1800.0);

        if ($withRoute) {
            $activity->setRoute(new Route());
        }

        $this->getTrainingRepository()
            ->save($activity);

        return $activity;
    }

    /**
     * Removes the "splits" CollectionType prototype markup (the ".split-prototype" container in
     * activity/form.html.twig) from the crawled DOM before building the Form object.
     *
     * That container is only hidden via a CSS class ("hide"), not via a "hidden" input type or
     * a "disabled" attribute, so its inputs (named "activity[splits][__name__][distance]" etc.,
     * using Symfony's default CollectionType prototype_name "__name__") are regular, active form
     * fields as far as DomCrawler is concerned. In a real browser, client-side JavaScript is
     * responsible for never submitting this template (it only clones and activates it when the
     * user clicks "add new lap"); this test has no JavaScript engine, so without this removal
     * the crawler would submit the empty prototype as if it were a real split, and
     * ActivityRoundType::reverseTransform() would then construct a Round with a null distance,
     * causing a TypeError. This mirrors real browser behaviour rather than working around a
     * production defect.
     */
    private function removeSplitsPrototypeFromForm(Crawler $formCrawler): void
    {
        $formNode = $formCrawler->getNode(0);

        foreach ((new Crawler($formNode))->filter('.split-prototype') as $prototypeNode) {
            $prototypeNode->parentNode->removeChild($prototypeNode);
        }
    }

    /**
     * Toggles the "is_race" checkbox on an already-crawled ActivityType edit form using the
     * DomCrawler's dedicated checkbox API (tick()/untick()), rather than guessing the submitted
     * string value ('1' vs. 'on' vs. empty). The field must be addressed by its full rendered
     * HTML name ("activity[is_race]"), not the short form-relative name ("is_race") - the latter
     * only works with Client::submitForm(), not with the lower-level Form::set()/ArrayAccess API
     * used by Client::submit(), which resolves names by their exact bracket-notation path.
     */
    private function setIsRaceCheckbox(Form $form, bool $isRace): void
    {
        /** @var ChoiceFormField $isRaceField */
        $isRaceField = $form->get('activity[is_race]');

        if ($isRace) {
            $isRaceField->tick();
        } else {
            $isRaceField->untick();
        }
    }

    /**
     * Crawls the edit form for $activity and returns a submittable Form object, with the
     * "splits" collection prototype already stripped out (see removeSplitsPrototypeFromForm()).
     */
    private function crawlActivityEditForm(int $activityId): Form
    {
        $crawler = $this->Client->request('GET', '/activity/' . $activityId . '/edit');
        $this->assertStatusCode(200, $this->Client);

        $formCrawler = $crawler->filter('form[name=activity]');
        $this->removeSplitsPrototypeFromForm($formCrawler);

        return $formCrawler->form();
    }

    public function testActivityEditActionRequiresAuthentication(): void
    {
        $activity = $this->createTrainingForAccount($this->Account, time());

        $this->Client->request('GET', '/activity/' . $activity->getId() . '/edit');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testActivityEditActionReturns404ForAnUnknownId(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/999999999/edit');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testActivityEditActionReturns404ForAnActivityBelongingToAnotherAccount(): void
    {
        $otherAccount = $this->registerUniqueAccount();
        $activity = $this->createTrainingForAccount($otherAccount, time());

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/activity/' . $activity->getId() . '/edit');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testActivityEditActionRendersTheActivityForm(): void
    {
        $activity = $this->createTrainingForAccount($this->Account, time());
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/' . $activity->getId() . '/edit');

        $this->assertStatusCode(200, $this->Client);
        $content = (string) $this->Client->getResponse()
            ->getContent();
        $this->assertStringContainsString('id="activity"', $content);
        $this->assertStringContainsString('isNew: false', $content);
        $this->assertStringContainsString('Runalyze.ActivityForm.init', $content);
    }

    /**
     * Regression test for the fix in TrainingRepository::ensureThatSportAndTypeAreManaged() being
     * called explicitly and early in EditController::activityEditAction(). SportChoiceType
     * resolves the submitted "sport" choice into a Sport entity that is not guaranteed to be the
     * exact object instance the current EntityManager's unit of work already tracks for that row
     * - this happens on EVERY submission of this form, regardless of what else changes. Before
     * the fix, saving such an activity failed with "A new entity was found through the
     * relationship 'Training#sport' that was not configured to cascade persist operations",
     * turning every edit of an existing activity into a 500 error.
     */
    public function testActivityEditActionSuccessfullyUpdatesAnActivityWithoutCrashingOnTheSportAssociation(): void
    {
        $activity = $this->createTrainingForAccount($this->Account, time());
        $this->loginClient($this->Client, $this->Account, 'default');

        $form = $this->crawlActivityEditForm($activity->getId());
        $form['activity[title]'] = 'Updated via regression test';
        $this->Client->submit($form);

        $this->assertStatusCode(200, $this->Client);

        $this->getEntityManager()
            ->clear();
        $reloadedActivity = $this->getTrainingRepository()
            ->find($activity->getId());
        $this->assertSame('Updated via regression test', $reloadedActivity->getTitle());
        $this->assertSame('Running', $reloadedActivity->getSport()->getName());
    }

    public function testActivityEditActionCreatesARaceresultWhenIsRaceIsSubmitted(): void
    {
        $activity = $this->createTrainingForAccount($this->Account, time());
        $this->loginClient($this->Client, $this->Account, 'default');

        $form = $this->crawlActivityEditForm($activity->getId());
        $this->setIsRaceCheckbox($form, isRace: true);
        $this->Client->submit($form);

        $this->assertStatusCode(200, $this->Client);

        $this->getEntityManager()
            ->clear();
        $reloadedActivity = $this->getTrainingRepository()
            ->find($activity->getId());
        $this->assertTrue($reloadedActivity->hasRaceresult());
    }

    public function testActivityEditActionRemovesTheRaceresultWhenIsRaceIsUnchecked(): void
    {
        $activity = $this->createTrainingForAccount($this->Account, time());
        $activity->setRaceresult((new Raceresult())->fillFromActivity($activity));
        $this->getTrainingRepository()
            ->save($activity);
        $this->assertTrue($activity->hasRaceresult());

        $this->loginClient($this->Client, $this->Account, 'default');

        $form = $this->crawlActivityEditForm($activity->getId());
        $this->setIsRaceCheckbox($form, isRace: false);
        $this->Client->submit($form);

        $this->assertStatusCode(200, $this->Client);

        $this->getEntityManager()
            ->clear();
        $reloadedActivity = $this->getTrainingRepository()
            ->find($activity->getId());
        $this->assertFalse($reloadedActivity->hasRaceresult());
    }

    public function testMultiEditorActionRendersNavigationForGivenActivityIds(): void
    {
        $first = $this->createTrainingForAccount($this->Account, time() - 3600);
        $second = $this->createTrainingForAccount($this->Account, time());
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/multi-editor?ids=' . $first->getId() . ',' . $second->getId());

        $this->assertStatusCode(200, $this->Client);
        $content = (string) $this->Client->getResponse()
            ->getContent();
        $this->assertStringContainsString('data-activity="' . $first->getId() . '"', $content);
        $this->assertStringContainsString('data-activity="' . $second->getId() . '"', $content);
    }

    /**
     * Regression test for the fix in TrainingRepository::getPartialEntitiesForPreview(), which
     * previously omitted "sport" from its partial select. Since ActivityDecorator::getTitle() -
     * called unconditionally by ActivityPreview for every result - unconditionally reads
     * Training::getSport() in nearly every branch, and Training::$sport is a non-nullable typed
     * property without a default value, every activity returned by this query used to trigger a
     * fatal "must not be accessed before initialization" error as soon as the multi-editor
     * navigation was rendered with at least one real, persisted activity.
     */
    public function testMultiEditorActionDoesNotCrashAndRendersTheSportIconForRealActivities(): void
    {
        $activity = $this->createTrainingForAccount($this->Account, time());
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/multi-editor?ids=' . $activity->getId());

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('icons8-Running', (string) $this->Client->getResponse()->getContent());
    }

    public function testMultiEditorActionWithoutIdsFallsBackToTheLatestActivitiesForTheAccount(): void
    {
        $this->createTrainingForAccount($this->Account, time());
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/multi-editor');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('icons8-Running', (string) $this->Client->getResponse()->getContent());
    }

    public function testDeleteActionRemovesTheActivityAndRedirects(): void
    {
        $activity = $this->createTrainingForAccount($this->Account, time());
        $activityId = $activity->getId();
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/' . $activityId . '/delete');

        $this->assertStatusCode(302, $this->Client);

        $this->getEntityManager()
            ->clear();
        $this->assertNull($this->getTrainingRepository()->find($activityId));
    }

    public function testDeleteActionReturns404ForAnActivityBelongingToAnotherAccount(): void
    {
        $otherAccount = $this->registerUniqueAccount();
        $activity = $this->createTrainingForAccount($otherAccount, time());

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/activity/' . $activity->getId() . '/delete');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testElevationCorrectionActionShowsAnErrorWhenTheActivityHasNoRoute(): void
    {
        $activity = $this->createTrainingForAccount($this->Account, time(), withRoute: false);
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/' . $activity->getId() . '/elevation-correction');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString('Runalyze.Overlay.load', (string) $this->Client->getResponse()->getContent());
    }

    public function testElevationCorrectionActionWithStrategyNoneRemovesCorrectedElevationWithoutContactingAnExternalProvider(): void
    {
        $activity = $this->createTrainingForAccount($this->Account, time(), withRoute: true);
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/' . $activity->getId() . '/elevation-correction?strategy=none');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Runalyze.Overlay.load', (string) $this->Client->getResponse()->getContent());

        $this->getEntityManager()
            ->clear();
        $reloadedActivity = $this->getTrainingRepository()
            ->find($activity->getId());
        $this->assertFalse($reloadedActivity->getRoute()->hasCorrectedElevations());
    }
}
