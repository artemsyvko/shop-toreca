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

namespace Plugin\TwoFactorAuthCustomer42\Form\Type;

use Doctrine\ORM\EntityRepository;
use Plugin\TwoFactorAuthCustomer42\Entity\TwoFactorAuthType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TwoFactorAuthTypeCustomer extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('two_factor_auth_type', EntityType::class, [
                'label' => 'front.setting.system.two_factor_auth.type',
                'class' => TwoFactorAuthType::class,
                'required' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('tfat')
                        ->where('tfat.isDisabled = :id')
                        ->setParameter('id', false);
                },
                'choice_label' => 'name',
                'mapped' => true,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'plg_customer_2fa';
    }
}
