<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Import;

use Runalyze\Bundle\CoreBundle\Entity\Conf;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerToActivityContextConverter;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class ActivityDataContainerToActivityContextConverterTest extends AbstractFixturesAwareWebTestCase
{
    protected ActivityDataContainerToActivityContextConverter $Converter;

    protected ActivityDataContainer $Container;

    protected function setUp(): void
    {
        parent::setUp();

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(
            new PreAuthenticatedToken(
                $this->getDefaultAccount(),
                'bar'
            )
        );
        $this->Container = new ActivityDataContainer();
        $this->Converter = new ActivityDataContainerToActivityContextConverter(
            $this->EntityManager->getRepository(Sport::class),
            $this->EntityManager->getRepository(Type::class),
            $this->EntityManager->getRepository(Equipment::class),
            new ConfigurationManager(
                $this->EntityManager->getRepository(Conf::class),
                $tokenStorage
            ),
            $this->getDefaultAccount()
        );
    }

    public function testEmptyContainer(): void
    {
        $activityContext = $this->Converter->getContextFor($this->Container);

        $this->assertEquals($this->getDefaultAccount(), $activityContext->getAccount());

        $this->assertNotNull($activityContext->getSport());
        $this->assertNull($activityContext->getActivity()->getType());
        $this->assertNull($activityContext->getRoute());
        $this->assertNull($activityContext->getTrackdata());
        $this->assertNull($activityContext->getSwimdata());
        $this->assertNull($activityContext->getHrv());
        $this->assertNull($activityContext->getRaceResult());
        $this->assertEmpty($activityContext->getActivity()->getEquipment());
    }

    public function testThatSportTypeAndEquipmentCanBeGuessedByName(): void
    {
        $this->Container->Metadata->setSportName('Running');
        $this->Container->Metadata->setTypeName('Fartlek');
        $this->Container->Metadata->addEquipment('singlet');

        $activityContext = $this->Converter->getContextFor($this->Container);

        $this->assertEquals($this->getDefaultAccountsRunningSport(), $activityContext->getSport());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Type::class, $activityContext->getActivity()->getType());
        $this->assertSame('Fartlek', $activityContext->getActivity()->getType()->getName());
        $this->assertCount(1, $activityContext->getActivity()->getEquipment());
        $this->assertEquals('singlet', $activityContext->getActivity()->getEquipment()[0]->getName());
    }

    public function testThatSportsDefaultTypeIsUsed(): void
    {
        $this->Container->Metadata->setSportName('Running');

        $backupDefaultType = $this->getDefaultAccountsRunningSport()
            ->getDefaultType();
        $defaultType = $this->EntityManager->getRepository(Type::class)
            ->findBy([
                'name' => 'Fartlek',
            ])[0];
        $this->getDefaultAccountsRunningSport()
            ->setDefaultType($defaultType);

        $activityContext = $this->Converter->getContextFor($this->Container);

        $this->assertEquals($this->getDefaultAccountsRunningSport(), $activityContext->getSport());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Type::class, $activityContext->getActivity()->getType());
        $this->assertSame('Fartlek', $activityContext->getActivity()->getType()->getName());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Type::class, $backupDefaultType);

        $this->getDefaultAccountsRunningSport()
            ->setDefaultType($backupDefaultType);
    }

    public function testThatSwimmingCanBeGuessedByName(): void
    {
        $this->Container->Metadata->setSportName('Swimming');
        $this->Container->Metadata->setTypeName('bathtub party');
        $this->Container->Metadata->addEquipment('rubber duck');

        $activityContext = $this->Converter->getContextFor($this->Container);

        $this->assertNotNull($activityContext->getSport());
        $this->assertSame('Swimming', $activityContext->getActivity()->getSport()->getName());

        $this->assertNull($activityContext->getActivity()->getType());
        $this->assertEmpty($activityContext->getActivity()->getEquipment());
    }

    public function testThatLatitudesAndLongitudesAreTreatedFine(): void
    {
        $this->Container->ContinuousData->Latitude = [49.78, 49.79, 49.80];
        $this->Container->ContinuousData->Longitude = [7.77, 7.77, 7.77];

        $activityContext = $this->Converter->getContextFor($this->Container);

        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Route::class, $activityContext->getRoute());
        $this->assertNotEmpty($activityContext->getRoute()->getGeohashes());
        $this->assertEquals($this->Container->ContinuousData->Latitude, $activityContext->getRoute()->getLatitudes());
        $this->assertEquals($this->Container->ContinuousData->Longitude, $activityContext->getRoute()->getLongitudes());
    }

    public function testThatBarometricAltitudeDataIsStoredAsOriginalOnly(): void
    {
        $this->Container->ContinuousData->IsAltitudeDataBarometric = true;
        $this->Container->ContinuousData->Altitude = [117, 117, 119, 124, 123, 120];

        $activityContext = $this->Converter->getContextFor($this->Container);

        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Route::class, $activityContext->getRoute());
        $this->assertEquals($this->Container->ContinuousData->Altitude, $activityContext->getRoute()->getElevationsOriginal());
        $this->assertNull($activityContext->getRoute()->getElevationsCorrected());
        $this->assertTrue($activityContext->getRoute()->getAltitudeBarometric());
    }

    public function testThatAltitudeBarometricFlagIsTransferred(): void
    {
        $this->Container->ContinuousData->IsAltitudeDataBarometric = true;
        $this->Container->ContinuousData->Altitude = [117, 118];

        $activityContext = $this->Converter->getContextFor($this->Container);
        $this->assertTrue($activityContext->getRoute()->getAltitudeBarometric());
    }

    public function testThatRRIntervalsAreConverted(): void
    {
        $this->Container->RRIntervals = [731, 746, 740, 752];

        $activityContext = $this->Converter->getContextFor($this->Container);

        $this->assertEquals($this->Container->RRIntervals, $activityContext->getHrv()->getData());
    }

    public function testDistanceRounding(): void
    {
        $this->Container->ContinuousData->Distance = [0.12345678, 0.98765432];

        $activityContext = $this->Converter->getContextFor($this->Container);

        $this->assertEquals([0.12346, 0.98765], $activityContext->getTrackdata()->getDistance());
    }

    public function testThatMissingTimestampFallsBackToNow(): void
    {
        $this->Container->Metadata->setSportName('Running');

        $beforeCall = time();
        $activityContext = $this->Converter->getContextFor($this->Container);
        $afterCall = time();

        $this->assertGreaterThanOrEqual(
            $beforeCall,
            $activityContext->getActivity()
                ->getTime(),
            'A missing timestamp should fall back to the current time, not 0 or any other placeholder.'
        );
        $this->assertLessThanOrEqual(
            $afterCall,
            $activityContext->getActivity()
                ->getTime(),
            'The fallback timestamp should not be in the future relative to when this test ran.'
        );
    }
}
