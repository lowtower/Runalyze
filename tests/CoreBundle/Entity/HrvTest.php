<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Hrv;
use Runalyze\Bundle\CoreBundle\Entity\Training;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class HrvTest extends TestCase
{
    private Hrv $hrv;

    protected function setUp(): void
    {
        $this->hrv = new Hrv();
    }

    public function testDataDefaultsToNull(): void
    {
        $this->assertNull($this->hrv->getData());
    }

    public function testGetAccountDefaultsToNull(): void
    {
        $this->assertNull($this->hrv->getAccount());
    }

    public function testGetActivityDefaultsToNull(): void
    {
        $this->assertNull($this->hrv->getActivity());
    }

    public function testSetDataGetData(): void
    {
        $data = [800, 810, 795, 805];
        $this->hrv->setData($data);

        $this->assertSame($data, $this->hrv->getData());
    }

    public function testSetDataToNullClearsData(): void
    {
        $this->hrv->setData([800, 810]);
        $this->hrv->setData();

        $this->assertNull($this->hrv->getData());
    }

    public function testSetAccountGetAccount(): void
    {
        $account = new Account();
        $this->hrv->setAccount($account);

        $this->assertSame($account, $this->hrv->getAccount());
    }

    public function testSetActivityGetActivity(): void
    {
        $activity = new Training();
        $this->hrv->setActivity($activity);

        $this->assertSame($activity, $this->hrv->getActivity());
    }

    public function testSettersAreFluent(): void
    {
        $this->assertSame($this->hrv, $this->hrv->setData());
        $this->assertSame($this->hrv, $this->hrv->setAccount(new Account()));
        $this->assertSame($this->hrv, $this->hrv->setActivity(new Training()));
    }
}
