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

namespace Plugin\DeliveryDate42\Form\Extension;

use Eccube\Form\Type\Admin\DeliveryType;
use Eccube\Repository\Master\PrefRepository;
use Plugin\DeliveryDate42\Entity\DeliveryDate;
use Plugin\DeliveryDate42\Form\Type\Admin\DeliveryDateType;
use Plugin\DeliveryDate42\Repository\DeliveryDateRepository;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DeliveryExtension extends AbstractTypeExtension
{

    private $prefRepository;

    private $deliveryDateRepository;

    public function __construct(
            PrefRepository $prefRepository,
            DeliveryDateRepository $deliveryDateRepository
            )
    {
        $this->prefRepository = $prefRepository;
        $this->deliveryDateRepository = $deliveryDateRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date_all', Type\NumberType::class, [
                'label' => false,
                'required' => false,
                'mapped' => false,
            ])
            ->add('delivery_dates', Type\CollectionType::class, [
                'label' => trans('deliverydate.admin.setting.shop.delivery.edit.label.1'),
                'required' => false,
                'entry_type' => DeliveryDateType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
            ])
            ->add(
                'delivery_date_flg',
                Type\CheckboxType::class,
                [
                    'label' => trans('deliverydate.admin.setting.shop.delivery.edit.label.2'),
                    'required' => false
                ]
            );

        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                $Delivery = $event->getData();
                $form = $event->getForm();
                if (is_null($Delivery)) {
                    return;
                }

                $Prefs = $this->prefRepository->findAll();

                $delivery_dates = new \Doctrine\Common\Collections\ArrayCollection();
                foreach ($Prefs as $Pref) {
                    $DeliveryDate = $this->deliveryDateRepository->findOneBy(['Delivery' => $Delivery, 'Pref' => $Pref]);
                    if(is_null($DeliveryDate)){
                        $DeliveryDate = new DeliveryDate();
                        $DeliveryDate->setPref($Pref);
                        $DeliveryDate->setDates(null);
                    }
                    $delivery_dates[] = $DeliveryDate;
                }
                $form['delivery_dates']->setData($delivery_dates);

            });
    }

    public static function getExtendedTypes(): iterable
    {
        return [DeliveryType::class];
    }
}
