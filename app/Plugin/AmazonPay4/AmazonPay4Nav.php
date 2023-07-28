<?php

namespace Plugin\AmazonPay4;
use Eccube\Common\EccubeNav;

class AmazonPay4Nav implements EccubeNav
{
    public static function getNav()
    {
        return [
            'order' => [
                'children' => [
                    'amazon_pay4_admin_payment_status' => [
                        'name' => 'amazon_pay4.admin.nav.payment_list',
                        'url' => 'amazon_pay4_admin_payment_status'
                    ]
                ]
            ]
        ];
    }
}