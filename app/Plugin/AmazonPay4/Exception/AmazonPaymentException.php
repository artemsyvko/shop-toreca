<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.3   |
    |              on 2021-06-01 18:34:50              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
namespace Plugin\AmazonPay4\Exception;
class AmazonPaymentException extends \Exception
{
    const UNDEFINED = false;
    const ZERO_PAYMENT = 101;
    const INVALID_PAYMENT_METHOD = 2;
    const AMAZON_REJECTED = 3;
    const EXPIRED = 5;

    public static $errorMessages = array(
        self::ZERO_PAYMENT => 'amazon_pay4.front.shopping.zero_payment_error',
        self::INVALID_PAYMENT_METHOD => 'Amazonアカウントでのお支払い選択において問題が発生しました。他の支払方法を選択するか、クレジットカード情報更新してください。',
        self::AMAZON_REJECTED => 'お支払い処理が失敗しました。他の支払い方法で再度購入してください。',
        self::EXPIRED => 'セッションの有効期限が切れました。'
    );
    public static $amazon_error_list =
        array(
            'InvalidPaymentMethod' => self::INVALID_PAYMENT_METHOD,
            'AmazonRejected' => self::AMAZON_REJECTED,
            'BuyerCanceled' => self::AMAZON_REJECTED,
            'AmazonCanceled' => self::AMAZON_REJECTED,
            'Declined' => self::INVALID_PAYMENT_METHOD,
            'Expired' => self::EXPIRED
        );

    public static function create(int $error_code)
    {
        goto VVf92;Dc4vA:urV21:goto rRwP3;rRwP3:$message = '予期しないエラーが発生しました。';
        goto xg58N;yLjh3:$message = self::$errorMessages[$error_code];
        goto O3L7O;VVf92:if (!array_key_exists($error_code, self::$errorMessages)) {goto urV21;}
        goto yLjh3;xg58N:BSA5n:goto TC3yq;O3L7O:goto BSA5n;goto Dc4vA;TC3yq:return new self($message, $error_code);
        goto F4_KT;F4_KT:
    }
    public static function getErrorCode($reason_code)
    {
        goto a10kD;BpqzB:return self::UNDEFINED;goto ApZk6;ahm7S:return self::$amazon_error_list[$reason_code];goto TCQP0;a10kD:if (array_key_exists($reason_code, self::$amazon_error_list)) {goto HhSXx;}goto BpqzB;ApZk6:HhSXx:goto ahm7S;TCQP0:
    }
}