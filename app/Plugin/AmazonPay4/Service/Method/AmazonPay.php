<?php

namespace Plugin\AmazonPay4\Service\Method;

use Plugin\AmazonPay4\Service\AmazonOrderHelper;
use Plugin\AmazonPay4\Service\AmazonRequestService;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Symfony\Component\Form\FormInterface;

class AmazonPay implements PaymentMethodInterface
{
    protected $productClassRepository;
    protected $purchaseFlow;
    protected $orderStatusRepository;
    protected $amazonRequestService;
    protected $amazonOrderHelper;
    protected $amazonCheckoutSessionId;

    public function __construct(
        OrderStatusRepository $orderStatusRepository,
        ProductClassRepository $productClassRepository,
        PurchaseFlow $shoppingPurchaseFlow,
        AmazonOrderHelper $amazonOrderHelper,
        AmazonRequestService $amazonRequestService
    ){
        $this->orderStatusRepository = $orderStatusRepository;
        $this->productClassRepository = $productClassRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->amazonOrderHelper = $amazonOrderHelper;
        $this->amazonRequestService = $amazonRequestService;
    }
    public function verify()
    {
        return false;
    }

    public function apply()
    {
        $this->purchaseFlow->prepare($this->Order, new PurchaseContext());
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);
        $this->Order->setOrderStatus($OrderStatus);
        return false;
    }
    public function checkout(){
        $response = $this->amazonOrderHelper->completeCheckout($this->Order, $this->amazonCheckoutSessionId);
        $this->amazonOrderHelper->setAmazonOrder($this->Order, $response->chargePermissionId, $response->chargeId);
        $this->purchaseFlow->commit($this->Order, new PurchaseContext());
        $result = new PaymentResult();
        $result->setSuccess(true);
        return $result;
    }

    public function setFormType(FormInterface $form)
    {
    }
    public function setOrder(Order $Order)
    {
        $this->Order = $Order;
    }
    public function setAmazonCheckoutSessionId($amazonCheckoutSessionId){
        $this->amazonCheckoutSessionId = $amazonCheckoutSessionId;
    }
}
?>