<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Smoke;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\AbstractType;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
final class FormTypeSmokeTest extends KernelTestCase
{
    private string $formDir = __DIR__ . '/../../../src/CoreBundle/Form/';

    private string $fullClassNamespace = 'Runalyze\\Bundle\\CoreBundle\\Form\\';

    public function testAllFormTypesInstantiateCorrectly(): void
    {
        $formTypeClasses = [];

        if (is_dir($this->formDir)) {
            foreach (new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->formDir, \RecursiveDirectoryIterator::SKIP_DOTS)
            ) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $class = $file->getBasename('.php');
                $fullClass = $this->fullClassNamespace . $class;

                if (class_exists($fullClass)) {
                    $reflection = new \ReflectionClass($fullClass);

                    if ($reflection->isAbstract()) {
                        continue;
                    }

                    if ($reflection->isSubclassOf(AbstractType::class)) {
                        $formTypeClasses[] = $fullClass;
                    }
                }
            }
        }

        $this->assertGreaterThan(
            0,
            count($formTypeClasses),
            'No form types found in src/Form/'
        );

        foreach ($formTypeClasses as $formTypeClass) {
            if (! self::getContainer()->has($formTypeClass)) {
                continue;
            }

            $formType = self::getContainer()->get($formTypeClass);
            $this->assertInstanceOf(
                AbstractType::class,
                $formType,
                "Service {$formTypeClass} is not a valid FormType"
            );

            $blockPrefix = $formType->getBlockPrefix();
            $this->assertIsString($blockPrefix);
        }
    }
}
