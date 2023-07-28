<?php

namespace Plugin\SlnPayment42\Service\Method;

use Eccube\Entity\Order;

class MethodUtils {
    public static function isSlnPaymentMethod(string $methodClass) {
        $slnMethods = [
            CreditCard::class,
            RegisteredCreditCard::class,
            CvsMethod::class
        ];
        return in_array($methodClass, $slnMethods);
    }

    public static function isCreditCardMethod(string $methodClass) {
        $slnMethods = [
            CreditCard::class,
            RegisteredCreditCard::class,
        ];
        return in_array($methodClass, $slnMethods);
    }

    public static function isCvsMethod(string $methodClass) {
        return $methodClass == CvsMethod::class;
    }

    public static function isSlnPaymentMethodByOrder(Order $Order) {
        if ($Order) {
            $payment = $Order->getPayment();
            if ($payment) {
                return MethodUtils::isSlnPaymentMethod($payment->getMethodClass());
            }
        }
        return false;
    }
}