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

namespace Plugin\MailMagazine42;

use Eccube\Common\EccubeNav;

class MailMagazineNav implements EccubeNav
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getNav()
    {
        return [
            'mailmagazine' => [
                'id' => 'mailmagazine',
                'name' => 'mailmagazine.title',
                'icon' => 'fa-envelope',
                'children' => [
                    'mailmagazine' => [
                        'id' => 'mailmagazine',
                        'name' => 'mailmagazine.index.title',
                        'url' => 'plugin_mail_magazine',
                    ],
                    'mailmagazine_template' => [
                        'id' => 'mailmagazine_template',
                        'name' => 'mailmagazine.template.title',
                        'url' => 'plugin_mail_magazine_template',
                    ],
                    'mailmagazine_history' => [
                        'id' => 'mailmagazine_history',
                        'name' => 'mailmagazine.history.title',
                        'url' => 'plugin_mail_magazine_history',
                    ],
                ],
            ],
        ];
    }
}
