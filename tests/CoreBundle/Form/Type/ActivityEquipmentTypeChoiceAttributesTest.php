<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Form\Type;

use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Form\Type\ActivityEquipmentType;

#[Group('default')]
final class ActivityEquipmentTypeChoiceAttributesTest extends TestCase
{
    public function testMissingEquipmentDatesProduceEmptyChoiceAttributes(): void
    {
        $this->assertSame(
            ['data-start' => '', 'data-end' => ''],
            $this->choiceAttributes(new Equipment())
        );
    }

    public function testImmutableEquipmentDatesAreAvailableToActivityFormChoices(): void
    {
        $equipment = new Equipment();
        $equipment->setDateStart(new DateTime('2024-03-29'));
        $equipment->setDateEnd(new DateTimeImmutable('2024-10-27'));

        $this->assertSame(
            ['data-start' => '2024-03-29', 'data-end' => '2024-10-27'],
            $this->choiceAttributes($equipment)
        );
    }

    /**
     * @return array<string, string>
     */
    private function choiceAttributes(Equipment $equipment): array
    {
        $type = new class () extends ActivityEquipmentType {
            public function __construct() {}

            /**
             * @return array<string, string>
             */
            public function attributesFor(Equipment $equipment): array
            {
                return $this->getChoiceAttributesForEquipment($equipment);
            }
        };

        return $type->attributesFor($equipment);
    }
}
