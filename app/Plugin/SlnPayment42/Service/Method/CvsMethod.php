<?php

namespace Plugin\SlnPayment42\Service\Method;

use Eccube\Entity\Order;
use Eccube\Exception\ShoppingException;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Plugin\SlnPayment42\Service\Util;

/**
 * コンビニの決済処理を行う.
 */
class CvsMethod implements PaymentMethodInterface {

    /**
     * @var Order
     */
    protected $Order;
    
    public function __construct(Util $util) {
        $this->util = $util;
    }

    /**
     * 注文確認画面遷移時に呼び出される.
     *
     * @return PaymentResult|void
     */
    public function verify()
    {
        $result = new PaymentResult();
        $result->setSuccess(true);

        return $result;
    }

    /**
     * 注文時に呼び出される.
     *
     * 決済サーバのカード入力画面へリダイレクトする.
     *
     * @return PaymentDispatcher
     *
     * @throws ShoppingException
     */
    public function apply()
    {
        // 決済サーバのカード入力画面へリダイレクトする.
        $response = new RedirectResponse('sln_cvs_payment');
        $dispatcher = new PaymentDispatcher();
        $dispatcher->setResponse($response);

        return $dispatcher;
    }

    /**
     * 注文時に呼び出される.
     * リンク式の場合, applyで決済サーバのカード入力画面へ遷移するため, checkoutは使用しない.
     *
     * @return PaymentResult
     */
    public function checkout()
    {
        $result = new PaymentResult();
        $result->setSuccess(true);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setFormType(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrder(Order $Order)
    {
        $this->Order = $Order;
    }
}