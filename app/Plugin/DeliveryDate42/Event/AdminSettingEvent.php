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

namespace Plugin\DeliveryDate42\Event;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AdminSettingEvent implements EventSubscriberInterface
{
    private $entityManager;

    public function __construct(
            EntityManagerInterface $entityManager
            )
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Setting/Shop/delivery_edit.twig' => 'onTemplateAdminSettingShopDeliveryEdit',
            EccubeEvents::ADMIN_SETTING_SHOP_DELIVERY_EDIT_COMPLETE => 'hookAdminSettingShopDeliveryEditComplete',
        ];
    }

    public function onTemplateAdminSettingShopDeliveryEdit(TemplateEvent $event)
    {
        $twig = '@DeliveryDate42/admin/Setting/Shop/delivery_date.twig';
        $event->addSnippet($twig);
        $js = '@DeliveryDate42/admin/Setting/Shop/delivery_date.js';
        $event->addAsset($js);
    }

    public function hookAdminSettingShopDeliveryEditComplete(EventArgs $event)
    {
        $form = $event->getArgument('form');
        $Delivery = $event->getArgument('Delivery');

        $DeliveryDates = $form['delivery_dates']->getData();
        foreach($DeliveryDates as $DeliveryDate){
            $DeliveryDate->setDelivery($Delivery);
            $Delivery->addDeliveryDate($DeliveryDate);
            $this->entityManager->persist($DeliveryDate);
        }
        $this->entityManager->flush();
    }
}
