<?php

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Runalyze\Activity\Duration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class DurationType extends AbstractType implements DataTransformerInterface
{
    protected bool $IsRequired = false;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this);

        if (isset($options['required'])) {
            $this->IsRequired = $options['required'];
        }
    }

    public function setRequired(bool $flag = true): void
    {
        $this->IsRequired = $flag;
    }

    /**
     * @param mixed $duration [s]
     */
    public function transform(mixed $duration): string
    {
        return $duration === null ? '0:00' : Duration::format($duration);
    }

    /**
     * @throws TransformationFailedException if $duration is null and required
     */
    public function reverseTransform($duration): mixed
    {
        if ($duration === null) {
            if ($this->IsRequired) {
                throw new TransformationFailedException('Duration cannot be empty');
            }

            return null;
        }

        return (new Duration($duration))->seconds();
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
