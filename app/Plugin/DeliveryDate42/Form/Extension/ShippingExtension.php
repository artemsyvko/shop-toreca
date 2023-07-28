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

use Eccube\Form\Type\Shopping\ShippingType;
use Plugin\DeliveryDate42\Service\ShoppingService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ShippingExtension extends AbstractTypeExtension
{

    private $shoppingService;

    public function __construct(
        ShoppingService $shoppingService
    ) {
        $this->shoppingService = $shoppingService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                /** @var \Eccube\Entity\Shipping $data */
                $data = $event->getData();
                /** @var \Symfony\Component\Form\Form $form */
                $form = $event->getForm();

                $delivery = $data->getDelivery();
                if (is_null($delivery)) {
                    return;
                }

                // お届け日を取得
                $emptyValue = trans('deliverydate.shopping.shipping.form.nothing');
                $deliveryDates = $this->shoppingService->getFormDeliveryDates($data, $delivery);

                if($delivery->getDeliveryDateFlg()){
                    $emptyValue = trans('deliverydate.shopping.shipping.form.cannot');
                    $deliveryDates = [];

                    $form->add(
                        'DeliveryTime',
                        EntityType::class,
                        [
                            'label' => trans('admin.order.delivery_time'),
                            'class' => 'Eccube\Entity\DeliveryTime',
                            'choice_label' => 'deliveryTime',
                            'choices' => [],
                            'required' => false,
                            'placeholder' => $emptyValue,
                            'mapped' => false,
                        ]
                    );
                }


                $form
                    ->add('shipping_delivery_date', Type\ChoiceType::class, [
                        'choices' => array_flip($deliveryDates),
                        'required' => false,
                        'placeholder' => $emptyValue,
                        'mapped' => false,
                        'data' => $data->getShippingDeliveryDate() ? $data->getShippingDeliveryDate()->format('Y/m/d') : null,
                    ]);
            });
    }


    public static function getExtendedTypes(): iterable
    {
        return [ShippingType::class];
    }
}
