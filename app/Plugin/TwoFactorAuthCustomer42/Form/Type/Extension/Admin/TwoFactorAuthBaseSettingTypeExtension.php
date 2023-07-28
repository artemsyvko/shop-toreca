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
use Eccube\Form\Type\Admin\ShopMasterType;
use Eccube\Form\Type\ToggleSwitchType;
use Plugin\TwoFactorAuthCustomer42\Entity\TwoFactorAuthType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class TwoFactorAuthBaseSettingTypeExtension extends AbstractTypeExtension
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
        yield ShopMasterType::class;
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

            if ($this->entityManager->getRepository(TwoFactorAuthType::class)->count(['isDisabled' => false]) > 0) {
                $form->add('two_factor_auth_use', ToggleSwitchType::class, [
                    'required' => false,
                    'mapped' => true,
                ]);
            }

            $form->add('option_activate_device', ToggleSwitchType::class, [
                    'required' => false,
                    'mapped' => true,
                ]);
        });
    }
}
