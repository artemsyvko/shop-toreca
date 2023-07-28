<?php

namespace Plugin\SlnPayment42\Service\Method;

use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Exception\ShoppingException;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Service\CartService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Plugin\SlnPayment42\Repository\PluginConfigRepository;
use Plugin\SlnPayment42\Repository\SlnAgreementRepository;

/**
 * クレジットカードの決済処理を行う.
 */
class CreditCard implements PaymentMethodInterface
{   
    /**
     * @var Order
     */
    protected $Order;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * @var PurchaseFlow
     */
    private $purchaseFlow;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var PluginConfigRepository
     */
    private $configRepository;

    /**
     * @var SlnAgreementRepository
     */
    private $SlnAgreementRepository;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * CreditCard constructor.
     *
     * @param OrderStatusRepository $orderStatusRepository
     * @param PurchaseFlow $shoppingPurchaseFlow
     */
    public function __construct(
        OrderStatusRepository $orderStatusRepository,
        SlnAgreementRepository $SlnAgreementRepository,
        PluginConfigRepository $configRepository,
        OrderRepository $orderRepository,
        CartService $cartService,
        PurchaseFlow $shoppingPurchaseFlow
    ) {
        $this->orderStatusRepository = $orderStatusRepository;
        $this->slnAgreementRepository = $SlnAgreementRepository;
        $this->configRepository = $configRepository;
        $this->orderRepository = $orderRepository;
        $this->cartService = $cartService;
        $this->purchaseFlow = $shoppingPurchaseFlow;
    }

    /**
     * 注文確認画面遷移時に呼び出される.
     *
     * クレジットカードの有効性チェックを行う.
     *
     * @return PaymentResult
     *
     * @throws \Eccube\Service\PurchaseFlow\PurchaseException
     */
    public function verify()
    {
        $result = new PaymentResult();
        $result->setSuccess(true);

        //個人情報同意フラグリセット
        $this->slnAgreementRepository->setAgreementStatus($this->Order, 0);

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
        // カード入力画面へリダイレクトする.
        $response = new RedirectResponse('sln_card_payment');
        $dispatcher = new PaymentDispatcher();
        $dispatcher->setResponse($response);

        //3Dセキュア判定
        $isThreedPay = $this->configRepository->getConfig()->getThreedPay();

        //個人情報取得同意フラグを設置
        if ($isThreedPay == 1) {
            $this->slnAgreementRepository->setAgreementStatus($this->Order, 1);
        } 

        return $dispatcher;
    }

    /**
     * 注文時に呼び出される.
     *
     * クレジットカードの決済処理を行う.
     *
     * @return PaymentResult
     */
    public function checkout()
    {
        $result = new PaymentResult();
        $result->setSuccess(false);
        $result->setErrors([trans('sln_payment.shopping.checkout.error')]);
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
