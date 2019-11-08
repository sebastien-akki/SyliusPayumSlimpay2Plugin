<?php

declare(strict_types=1);

namespace Akki\SyliusPayumSlimpayPlugin\Form\Type;

use Akki\SyliusPayumSlimpayPlugin\Legacy\Slimpay;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

final class SlimpayGatewayConfigurationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('app_id', TextType::class, [
                'label' => 'akki.sylius_payum_slimpay_plugin.app_id',
                'constraints' => [
                    new NotBlank([
                        'message' => 'akki.sylius_payum_slimpay_plugin.app_id.not_blank',
                        'groups' => ['sylius']
                    ])
                ],
            ])
            ->add('app_secret', TextType::class, [
                'label' => 'akki.sylius_payum_slimpay_plugin.app_secret',
                'constraints' => [
                    new NotBlank([
                        'message' => 'akki.sylius_payum_slimpay_plugin.app_secret.not_blank',
                        'groups' => ['sylius']
                    ])
                ],
            ])
            ->add('creditor_reference', TextType::class, [
                'label' => 'akki.sylius_payum_slimpay_plugin.creditor_reference',
                'constraints' => [
                    new NotBlank([
                        'message' => 'akki.sylius_payum_slimpay_plugin.creditor_reference.not_blank',
                        'groups' => ['sylius']
                    ])
                ],
            ])
            ->add('sandbox', ChoiceType::class, [
                'label' => 'akki.sylius_payum_slimpay_plugin.sandbox',
                'choices' => [
                    'akki.sylius_payum_slimpay_plugin.no' => false,
                    'akki.sylius_payum_slimpay_plugin.yes' => true
                ],
            ])
            ->add('default_checkout_mode', TextType::class, [
                'label' => 'akki.sylius_payum_slimpay_plugin.default_checkout_mode',
                'constraints' => [
                    new NotBlank([
                        'message' => 'akki.sylius_payum_slimpay_plugin.default_checkout_mode.not_blank',
                        'groups' => ['sylius']
                    ])
                ],
            ])
        ;
    }
}
