<?php
namespace Plugin\AmazonPay4\Form\Type\Master;

class ConfigTypeMaster
{
    const ACCOUNT_MODE = array(
        'SHARED' => 1,
        'OWNED' => 2
    );

    const ENV = array(
        'SANDBOX' => 1,
        'PROD' => 2
    );

    const SALE = array(
        'AUTORI' => 1,
        'CAPTURE' => 2
    );

    const CART_BUTTON_PLACE = array(
        'AUTO' => 1,
        'MANUAL' => 2
    );

    const MYPAGE_LOGIN_BUTTON_PLACE = array(
        'AUTO' => 1,
        'MANUAL' => 2
    );

    const PRODUCTS_BUTTON_PLACE = array(
        'AUTO' => 1,
        'MANUAL' => 2
    );
}
?>