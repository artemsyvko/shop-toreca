<?php
/*
* Plugin Name : DeliveryDate4
*
* Copyright (C) BraTech Co., Ltd. All Rights Reserved.
* http://www.bratech.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\DeliveryDate42\Form\Type\Admin;

use Plugin\DeliveryDate42\Entity\DeliveryDateConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $timeChoices[0] = trans('deliverydate.admin.setting.deliverydate.config.choice.default');
        for($i=1; $i<24; $i++){
            $timeChoices[$i] = $i.':00';
        }

        $methodChoices[DeliveryDateConfig::DISABLED] = trans('deliverydate.admin.setting.deliverydate.config.choice.holiday.disable');
        $methodChoices[DeliveryDateConfig::ENABLED] = trans('deliverydate.admin.setting.deliverydate.config.choice.holiday.enable');;

        for($i=1; $i<=12; $i++){
            $monthChoices[$i] = $i . trans('deliverydate.admin.setting.deliverydate.config.choice.month');
        }

        $builder
            ->add('accept_time', Type\ChoiceType::class, [
                'label' => trans('deliverydate.admin.setting.deliverydate.config.label.1'),
                'mapped' => false,
                'required' => true,
                'choices' => array_flip($timeChoices),
                'multiple' => false,
                'expanded' => false,
            ])
            ->add('same_day_flg', Type\CheckboxType::class, [
                'label' => trans('deliverydate.admin.setting.deliverydate.config.label.5'),
                'mapped' => false,
                'required' => false,
            ])
            ->add('default_deliverydate_days', Type\TextType::class, [
                'label' => trans('deliverydate.admin.setting.deliverydate.config.label.2'),
                'mapped' => false,
                'required' => false,
            ])
            ->add('method', Type\ChoiceType::class, [
                'label' => trans('deliverydate.admin.setting.deliverydate.config.label.3'),
                'mapped' => false,
                'required' => true,
                'choices' => array_flip($methodChoices),
                'multiple' => false,
                'expanded' => false,
            ])
            ->add('calendar_month', Type\ChoiceType::class, [
                'label' => trans('deliverydate.admin.setting.deliverydate.config.label.4'),
                'mapped' => false,
                'required' => true,
                'choices' => array_flip($monthChoices),
                'multiple' => false,
                'expanded' => false,
            ])
        ;

    }
}
