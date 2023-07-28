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

namespace Plugin\RelatedProduct42;

use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RelatedProductEvent implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Product/product.twig' => 'onRenderAdminProduct',
            'Product/detail.twig' => 'onRenderProductDetail',
        ];
    }

    /**
     * フロント：商品詳細画面に関連商品を表示する.
     *
     * @param TemplateEvent $event
     */
    public function onRenderProductDetail(TemplateEvent $event)
    {
        $event->addSnippet('@RelatedProduct42/front/related_product.twig');
    }

    /**
     * 管理画面：商品登録画面に関連商品登録フォームを表示する.
     *
     * @param TemplateEvent $event
     */
    public function onRenderAdminProduct(TemplateEvent $event)
    {
        $event->addSnippet('@RelatedProduct42/admin/related_product.twig');
    }
}
