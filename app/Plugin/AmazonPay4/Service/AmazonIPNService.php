<?php

namespace Plugin\AmazonPay4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Plugin\AmazonPay4\Entity\Master\AmazonStatus;
use Plugin\AmazonPay4\Entity\AmazonTrading;
use Plugin\AmazonPay4\Repository\ConfigRepository;
use Plugin\AmazonPay4\Service\AmazonOrderHelper;
use Plugin\AmazonPay4\Service\AmazonRequestService;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\MailService;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\DBAL\LockMode;

class AmazonIPNService{
    private $sessionAmazonProfileKey = 'amazon_pay4.profile';
    private $sessionAmazonCheckoutSessionIdKey = 'amazon_pay4.checkout_session_id';
    private $sessionAmazonCustomerParamKey = 'amazon_pay4.customer_regist';
    protected $orderRepository;
    protected $entityManager;
    protected $eccubeConfig;
    protected $mailService;
    protected $customerRepository;
    protected $orderStatusRepository;
    protected $purchaseFlow;
    protected $configRepository;
    protected $amazonOrderHelper;
    protected $amazonRequestService;
    protected $container;
    protected $Config;

    public function __construct(
        EccubeConfig $eccubeConfig,
        MailService $mailService,
        CustomerRepository $customerRepository,
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository,
        EntityManagerInterface $entityManager,
        PurchaseFlow $shoppingPurchaseFlow,
        ConfigRepository $configRepository,
        AmazonOrderHelper $amazonOrderHelper,
        AmazonRequestService $amazonRequestService,
        ContainerInterface $container
    ){

        $this->eccubeConfig = $eccubeConfig;
        $this->mailService = $mailService;
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->entityManager = $entityManager;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->configRepository = $configRepository;
        $this->amazonOrderHelper = $amazonOrderHelper;
        $this->amazonRequestService = $amazonRequestService;
        $this->container = $container;
        $this->Config = $this->configRepository->get();

    }

    public function mainProcess($arrParam){
        if ($this->Config->getSellerId() != $arrParam['MerchantID']) {
            return null;
        }
        $objectId = $arrParam['ObjectId'];
        if ($arrParam['ObjectType'] == 'CHARGE') {
            $chargeResponse = $this->amazonRequestService->getCharge($objectId);
            $chargeResponseState = $chargeResponse->statusDetails->state;
            $Order = $this->getFirstOrder($arrParam, $chargeResponse);
            if ($Order && ($chargeResponseState == 'Authorized' || $chargeResponseState == 'Captured') && $Order->getAmazonPay4SessionTemp()) {
                logs('amazon_pay4')->info('AmazonIPNService::初回注文処理 start');
                $this->firstOrderProcess($Order);
                logs('amazon_pay4')->info('AmazonIPNService::初回注文処理 end');
            }
            $Order = $this->getOrder($arrParam, $chargeResponse);
            if ($Order) {
                $AmazonTradings = $Order->getAmazonPay4AmazonTradings();
                $arrChargeId = [];
                foreach ($AmazonTradings as $AmazonTrading)
                {
                    $arrChargeId[] = $AmazonTrading->getChargeId();
                }

                if ($chargeResponseState == 'Captured' && !in_array($objectId, $arrChargeId)) {
                    logs('amazon_pay4')->info('AmazonIPNService::売上請求処理 start');
                    logs('amazon_pay4')->info('AmazonIPNService::売上請求処理 end');
                }
                if ($chargeResponseState == 'Canceled' && in_array($objectId, $arrChargeId)) {
                    logs('amazon_pay4')->info('AmazonIPNService::売上キャンセル処理 start');
                    logs('amazon_pay4')->info('AmazonIPNService::売上キャンセル処理 end');
                }
            }
        }else{
            if ($arrParam['ObjectType'] == 'REFUND') {
                logs('amazon_pay4')->info('AmazonIPNService::返金処理 start');
                logs('amazon_pay4')->info('AmazonIPNService::返金処理 end');
            }
        }
    }

    public function getOrder($arrParam, $response)
    {
        $Order = $this->orderRepository->findOneBy([
            'amazonpay4charge_permission_id' => $arrParam['ChargePermissionId'],
            'AmazonPay4AmazonStatus' => [AmazonStatus::AUTHORI, AmazonStatus::CAPTURE],
            'OrderStatus' => [OrderStatus::NEW, OrderStatus::PAID]
        ]);
        return $Order;
    }

    public function getFirstOrder($arrParam, $response)
    {
        $Order = $this->orderRepository->findOneBy(['amazonpay4charge_permission_id' => $arrParam['ChargePermissionId'], 'payment_total' => (int) $response->chargeAmount->amount, 'OrderStatus' => OrderStatus::PENDING]);
        if (!$Order) {
            $prefix = '';
            $iniFile = dirname(__FILE__) . '/../amazon_pay_config.ini';
            if (file_exists($iniFile)) {
                $arrInit = parse_ini_file($iniFile);
                $prefix = $arrInit['prefix'];
            }

            $prefix = $prefix === '' ? '' : $prefix . '_';
            $order_id = $response->merchantMetadata->merchantReferenceId;
            if ($prefix) {
                $order_id = str_replace($prefix, '', $order_id);
            }
            $Order = $this->orderRepository->findOneBy(['id' => $order_id, 'payment_total' => (int) $response->chargeAmount->amount, 'OrderStatus' => OrderStatus::PENDING]);
        }
        return $Order;
    }

    function firstOrderProcess($Order)
    {
        $this->entityManager->beginTransaction();
        $this->entityManager->lock($Order, LockMode::PESSIMISTIC_WRITE);
        $session_temp = unserialize($Order->getAmazonPay4SessionTemp());
        $arrAmazonCustomerParam = $session_temp[$this->sessionAmazonCustomerParamKey];
        $profile = $session_temp[$this->sessionAmazonProfileKey];
        $amazonCheckoutSessionId = $session_temp[$this->sessionAmazonCheckoutSessionIdKey];
        $response = $this->amazonOrderHelper->completeCheckout($Order, $amazonCheckoutSessionId);
        $this->amazonOrderHelper->setAmazonOrder($Order, $response->chargePermissionId, $response->chargeId);
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);
        $Order->setOrderStatus($OrderStatus);
        $this->purchaseFlow->commit($Order, new PurchaseContext());
        if ($session_temp['IS_AUTHENTICATED_FULLY']) {
            $Customer = $Order->getCustomer();
            $Customers = $this->customerRepository->getNonWithdrawingCustomers(['amazon_user_id' => $profile->buyerId]);
            if (!$Customer->getAmazonUserId() && empty($Customers[0])) {
                $Customer->setAmazonUserId($profile->buyerId);
            }
        }else{
            if (empty($arrAmazonCustomerParam['login_check']) || $arrAmazonCustomerParam['login_check'] == 'regist') {
                if ($arrAmazonCustomerParam['customer_regist']) {
                    $url = $this->container->get('router')->generate('mypage_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
                    if ($this->customerRepository->getNonWithdrawingCustomers(['email' => $Order->getEmail()]) || $this->customerRepository->getNonWithdrawingCustomers(['amazon_user_id' => $profile->buyerId])) {
                        $mail = $Order->getEmail();
                        $mail_message = <<<__EOS__
************************************************
　会員登録情報
************************************************
マイページURL：{$url}
※会員登録済みです。メールアドレスは{$mail}です。

__EOS__;
                    }else{
                        $password = $this->amazonOrderHelper->registCustomer($Order, $arrAmazonCustomerParam['mail_magazine'], $profile);
                        $Customer = $this->customerRepository->findOneBy(['email' => $Order->getEmail()]);
                        $Order->setCustomer($Customer);
                        $mail = $Customer->getEmail();
                        $mail_message = <<<__EOS__
************************************************
　会員登録情報
************************************************
マイページURL：{$url}
ログインメールアドレス：{$mail}
初期パスワード：{$password}

__EOS__;
                    }
                    $Order->setCompleteMailMessage($mail_message);
                }
            }else{
                if ($arrAmazonCustomerParam['login_check'] == 'login') {
                    $Customer = $this->customerRepository->findOneBy(['email' => $arrAmazonCustomerParam['amazon_login_email']]);
                    $Order->setCustomer($Customer);
                    $this->amazonOrderHelper->copyToOrderFromCustomer($Order, $Customer);
                    $Customers = $this->customerRepository->getNonWithdrawingCustomers(['amazon_user_id' => $profile->buyerId]);
                    if (!$Customer->getAmazonUserId() && empty($Customers[0])) {
                        $Customer->setAmazonUserId($profile->buyerId);
                    }
                }
            }
        }
        $Order->setAmazonPay4SessionTemp(null);
        $this->entityManager->flush();
        $this->entityManager->commit();
        logs('amazon_pay4')->info('[注文処理] 注文処理が完了しました.', [$Order->getId()]);
        logs('amazon_pay4')->info('[注文処理] 注文メールの送信を行います.', [$Order->getId()]);
        if (!(is_null($this->Config->getMailNotices()))) {
            $Order->appendCompleteMailMessage("特記事項：" . $this->Config->getMailNotices());
        }
        $this->mailService->sendOrderMail($Order);
        $this->entityManager->flush();
        logs('amazon_pay4')->info('購入処理完了', [$Order->getId()]);

    }
}
?>