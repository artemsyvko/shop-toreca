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
use Eccube\Repository\ProductClassRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AdminProductEvent implements EventSubscriberInterface
{
    private $entityManager;

    private $productClassRepository;

    public function __construct(
            EntityManagerInterface $entityManager,
            ProductClassRepository $productClassRepository
            )
    {
        $this->entityManager = $entityManager;
        $this->productClassRepository = $productClassRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EccubeEvents::ADMIN_PRODUCT_COPY_COMPLETE => 'hookAdminProductCopyComplete',
            '@admin/Product/product.twig' => 'onTemplateAdminProductEdit',
            '@admin/Product/product_class.twig' => 'onTemplateAdminProductClassEdit',
        ];
    }

    public function hookAdminProductCopyComplete(EventArgs $event)
    {
        $Product = $event->getArgument('Product');
        $CopyProduct = $event->getArgument('CopyProduct');
        $orgProductClasses = $Product->getProductClasses();

        foreach ($orgProductClasses as $ProductClass) {
            $CopyProductClass = $this->productClassRepository->findOneBy(['Product'=> $CopyProduct, 'ClassCategory1' => $ProductClass->getClassCategory1(), 'ClassCategory2' => $ProductClass->getClassCategory2()]);
            if($CopyProductClass){
                $CopyProductClass->setDeliveryDateDays($ProductClass->getDeliveryDateDays());
                $this->entityManager->persist($CopyProductClass);
            }
        }

        $this->entityManager->flush();
    }

    public function onTemplateAdminProductEdit(TemplateEvent $event)
    {
        $twig = '@DeliveryDate42/admin/Product/product_days.twig';
        $event->addSnippet($twig);
        $js = '@DeliveryDate42/admin/Product/product_days.js';
        $event->addAsset($js);
    }

    public function onTemplateAdminProductClassEdit(TemplateEvent $event)
    {
        $twig = '@DeliveryDate42/admin/Product/product_class_days.twig';
        $event->addSnippet($twig);
        $js = '@DeliveryDate42/admin/Product/product_class_days.js';
        $event->addAsset($js);
    }
}
