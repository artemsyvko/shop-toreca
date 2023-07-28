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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;

class HolidaySearchType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $deliveryDates = [];

        $now = getdate();

        $period = new \DatePeriod (
            new \DateTime($now['year'].'-1-1'),
            new \DateInterval('P1M'),
            new \DateTime(($now['year']+2).'-1-1')
        );

        foreach ($period as $day) {
            $deliveryDates[$day->format('Y/m')] = $day->format('Y/m');
        }

        $builder
            ->add('month', Type\ChoiceType::class, [
                'label' => false,
                'required' => false,
                'choices' => array_flip($deliveryDates),
                'expanded' => false,
                'multiple' => false,
                'placeholder' => trans('deliverydate.common.choice.default'),
            ])
        ;

    }
}
