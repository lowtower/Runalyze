<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Form\Type;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Form\Type\HrZoneBoundType;
use Symfony\Component\Form\Forms;

final class HrZoneBoundTypeTest extends TestCase
{
    public function testRequiredOptionIsIsolatedPerField(): void
    {
        $formFactory = Forms::createFormFactory();
        $required = $formFactory->create(HrZoneBoundType::class, null, [
            'required' => true,
        ]);
        $optional = $formFactory->create(HrZoneBoundType::class, null, [
            'required' => false,
        ]);

        $required->submit('');
        $optional->submit('');

        $this->assertFalse($required->isSynchronized());
        $this->assertTrue($optional->isSynchronized());
        $this->assertNull($optional->getData());
    }
}
