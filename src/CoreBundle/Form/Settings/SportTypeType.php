<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Form\Settings;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Form\Type\HeartRateType;
use Runalyze\Profile\View\DataBrowserRowProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SportTypeType extends AbstractType
{
    public function __construct(
        protected \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $TokenStorageInterface
    ) {
    }

    /**
     * @return Account
     */
    protected function getAccount()
    {
        $account = $this->TokenStorageInterface->getToken() ? $this->TokenStorageInterface->getToken()
            ->getUser() : null;

        if (! ($account instanceof Account)) {
            throw new \RuntimeException('Activity type must have a valid account token.');
        }

        return $account;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'attr' => [
                    'autofocus' => true,
                ],
            ])
            ->add('abbr', TextType::class, [
                'required' => true,
                'label' => 'Abbreviation',
            ])
            ->add('hrAvg', HeartRateType::class, [
                'attr' => [
                    'min' => 40,
                    'max' => 255,
                ],
                'required' => false,
                'label' => 'avg. HR',
            ])
            ->add('qualitySession', CheckboxType::class, [
                'required' => false,
                'label' => 'Quality session',
            ])
            ->add('displayMode', ChoiceType::class, [
                'choices' => DataBrowserRowProfile::getChoices(),
                'choice_translation_domain' => false,
                'label' => 'Calendar view',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \Runalyze\Bundle\CoreBundle\Entity\Type::class,
        ]);
    }
}
