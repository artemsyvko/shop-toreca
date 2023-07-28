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

namespace Plugin\SiteKit42;

use Eccube\Common\EccubeNav;

class Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'site_kit' => [
                'name' => 'Site Kit',
                'icon' => 'fa-google',
                'children' => [
                    'gsc_query' => [
                        'name' => 'ダッシュボード',
                        'url' => 'site_kit_dashboard',
                    ],
                ],
            ],
        ];
    }
}
