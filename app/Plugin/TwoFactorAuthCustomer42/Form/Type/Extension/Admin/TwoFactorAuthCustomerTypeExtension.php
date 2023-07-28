<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\TwoFactorAuthCustomer42\Form\Type\Extension\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Form\Type\Admin\CustomerType;
use Eccube\Form\Type\PhoneNumberType;
use Eccube\Form\Type\ToggleSwitchType;
use Plugin\TwoFactorAuthCustomer42\Entity\TwoFactorAuthType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class TwoFactorAuthCustomerTypeExtension extends AbstractTypeExtension
{
    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    /**
     * CouponDetailType constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritDoc}
     */
    public static function getExtendedTypes(): iterable
    {
        yield CustomerType::class;
    }

    /**
     * buildForm.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!empty($options['skip_add_form'])) {
            return;
        }

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $form->add('two_factor_auth_type', EntityType::class, [
                'class' => TwoFactorAuthType::class,
                'required' => false,
                'choice_label' => 'name',
                'mapped' => true,
                'placeholder' => 'admin.customer.2fa.type_option.default',
            ])
                ->add('device_authed', ToggleSwitchType::class, [
                    'required' => false,
                    'mapped' => true,
                ])
                ->add('device_authed_phone_number', PhoneNumberType::class, [
                    'required' => false,
                ]);
        });
    }
}
