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

namespace Plugin\DeliveryDate42;

use Eccube\Common\EccubeNav;

class DeliveryDateNav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'setting' => [
                'children' => [
                    'shop' => [
                        'children' => [
                            'holiday' => [
                                'id' => 'admin_setting_deliverydate_holiday',
                                'name' => 'deliverydate.admin.nav.setting.deliverydate.holiday',
                                'url' => 'admin_setting_deliverydate_holiday',
                                ]
                            ]
                    ],
                    'system' =>[
                        'children' => [
                            'deliverydate_config' => [
                                'id' => 'admin_setting_deliverydate_config',
                                'name' => 'deliverydate.admin.nav.setting.deliverydate.config',
                                'url' => 'admin_setting_deliverydate_config',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}