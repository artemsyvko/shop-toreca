<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.3   |
    |              on 2021-06-01 18:34:50              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
namespace Plugin\AmazonPay4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CustomerRepository;
use Eccube\Service\CartService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\AmazonPay4\Exception\AmazonException;
use Plugin\AmazonPay4\Exception\AmazonPaymentException;
use Plugin\AmazonPay4\Repository\ConfigRepository;
use Plugin\AmazonPay4\Amazon\Pay\API\Client as AmazonPayClient;
use GuzzleHttp\Client;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;
// use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\usernamePasswordToken;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Carbon\Carbon;

class AmazonRequestService{
    // use ControllerTrait;
    protected $entityManager;
    protected $baseInfoRepository;
    protected $customerRepository;
    protected $cartService;
    protected $purchaseFlow;
    protected $eccubeConfig;
    protected $configRepository;
    protected $Config;
    protected $amazonApi;
    protected $amazonApiConfig;
    protected $session;
    protected $tokenStorage;
    protected $container;
    protected $BaseInfo;

    public function __construct(
        EntityManagerInterface $entityManager,
        BaseInfoRepository $baseInfoRepository,
        CustomerRepository $customerRepository,
        CartService $cartService,
        PurchaseFlow $cartPurchaseFlow,
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository,
        SessionInterface $session,
        TokenStorageInterface $tokenStorage,
        ContainerInterface $container
    ){
        $this->entityManager = $entityManager;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->customerRepository = $customerRepository;
        $this->cartService = $cartService;
        $this->purchaseFlow = $cartPurchaseFlow;
        $this->eccubeConfig = $eccubeConfig;
        $this->configRepository = $configRepository;
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->container = $container;
        $this->Config = $this->configRepository->get();
        if ($this->Config->getAmazonAccountMode() == $this->eccubeConfig['amazon_pay4']['account_mode']['owned'] && $this->Config->getEnv() == $this->eccubeConfig['amazon_pay4']['env']['prod']) {
            $this->amazonApi = $this->eccubeConfig['amazon_pay4']['api']['prod'];
        }else{
            $this->amazonApi = $this->eccubeConfig['amazon_pay4']['api']['sandbox'];
        }
        $this->amazonApiConfig = $this->eccubeConfig['amazon_pay4']['api']['config'];
    }

    private function payoutSellerOrderId($orderId, $request_type = ''){
        $request_attr = $request_type === '' ? '' : strtoupper($request_type) . '_';
        $prefix = '';
        $iniFile = dirname(__FILE__) . '/../amazon_pay_config.ini';
        if (file_exists($iniFile)) {
            $arrInit = parse_ini_file($iniFile);
            $prefix = $arrInit['prefix'];
        }
        $prefix = $prefix === '' ? '' : $prefix . '_';
        $timestamp = '';
        if ($this->Config->getAmazonAccountMode() === $this->eccubeConfig['amazon_pay4']['account_mode']['shared']) {
            $timestamp = Carbon::now()->timestamp;
        }
        $timestamp = $timestamp === '' ? '' : $timestamp . '_';
        return $timestamp . $prefix . $request_attr . $orderId;
    }

    protected function getAmazonPayConfig(){
        $Config = $this->configRepository->get();
        $config = [
            'public_key_id' => $Config->getPublicKeyId(),
            'private_key' => $this->container->getParameter('kernel.project_dir') . '/' . $Config->getPrivateKeyPath(),
            'sandbox' => $Config->getEnv() == $this->eccubeConfig['amazon_pay4']['env']['sandbox'] ? true : false,
            'region' => 'jp'
        ];
        return $config;
    }
    public function createCheckoutSessionPayload($cart_key){
        $Config = $this->configRepository->get();
        $payload = [
            'webCheckoutDetails' => [
                'checkoutReviewReturnUrl' => $this->container->get('router')->generate('amazon_checkout_review', ['cart' => $cart_key], UrlGeneratorInterface::ABSOLUTE_URL)
            ],
            'paymentDetails' => ['allowOvercharge' => true],
            'storeId' => $Config->getClientId(),
            'deliverySpecifications' => [
                'addressRestrictions' => [
                    'type' => 'Allowed',
                    'restrictions' => [
                        'JP' => []
                    ]
                ]
            ]
        ];
        return json_encode($payload, JSON_FORCE_OBJECT);
    }

    public function createUpdateCheckoutSessionPayload($Order){
        if ($Order->getPaymentTotal() == 0) {
            throw AmazonPaymentException::create(AmazonPaymentException::ZERO_PAYMENT);
        }
        $config = $this->configRepository->get();
        if ($config->getSale() == $this->eccubeConfig['amazon_pay4']['sale']['authori']) {
            $paymentIntent = 'Authorize';
        }else{
            if ($config->getSale() == $this->eccubeConfig['amazon_pay4']['sale']['capture']) {
                $paymentIntent = 'AuthorizeWithCapture';
            }
        }
        $payload = [
            'webCheckoutDetails' => [
                'checkoutResultReturnUrl' => $this->container->get('router')->generate('amazon_pay_shopping_checkout_result', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ],
            'paymentDetails' => [
                'paymentIntent' => $paymentIntent,
                'canHandlePendingAuthorization' => false,
                'chargeAmount' => [
                    'amount' => (int) $Order->getPaymentTotal(),
                    'currencyCode' => "JPY"
                ]
            ],
            'merchantMetadata' => [
                'merchantReferenceId' => $this->payoutSellerOrderId($Order->getId()),
                'merchantStoreName' => $this->BaseInfo->getShopName(),
                'noteToBuyer' => ''
            ],
            "platformId" => "A1LODGGQOBGE66"
        ];
		
        return json_encode($payload, JSON_FORCE_OBJECT);
    }

    public function createCompleteCheckoutSessionPayload($Order)
    {
        $payload = ['chargeAmount' => ['amount' => (int) $Order->getPaymentTotal(), 'currencyCode' => 'JPY']];
        return json_encode($payload, JSON_FORCE_OBJECT);
    }

    public function createCaptureChargePayload($Order, $billingAmount = null)
    {
        $payload = ['captureAmount' => ['amount' => is_null($billingAmount) ? (int) $Order->getPaymentTotal() : $billingAmount, 'currencyCode' => 'JPY']];
        return json_encode($payload, JSON_FORCE_OBJECT);
    }

    public function createCancelChargePayload($cancellationReason = null)
    {
        $payload = ['cancellationReason' => $cancellationReason];
        return json_encode($payload, JSON_FORCE_OBJECT);
    }

    public function createCloseChargePermissionPayload($closureReason = null, $cancelPendingCharges = null)
    {
        $payload = ['closureReason' => $closureReason, 'cancelPendingCharges' => $cancelPendingCharges];
        return json_encode($payload, JSON_FORCE_OBJECT);
    }
    public function createCreateRefundPayload($chargeId, $refundAmount){
        $payload = [
            'chargeId' => $chargeId,
            'refundAmount' => [
                'amount' => $refundAmount,
                'currencyCode' => $this->eccubeConfig['amazon_pay4']['api']['payload']['currency_code']
            ]
        ];
        return json_encode($payload, JSON_FORCE_OBJECT);
    }
    public function createCreateChargePayload($chargePermissionId, $paymentTotal, $CaptureNow = false, $canHandlePendingAuthorization = false)
    {
        $payload = [
            'chargePermissionId' => $chargePermissionId,
            'chargeAmount' => [
                'amount' => $paymentTotal,
                'currencyCode' => $this->eccubeConfig['amazon_pay4']['api']['payload']['currency_code']
            ],
            'captureNow' => $CaptureNow,
            'canHandlePendingAuthorization' => $canHandlePendingAuthorization
        ];
        return json_encode($payload, JSON_FORCE_OBJECT);
    }
    public function updateCheckoutSession($Order, $amazonCheckoutSessionId){
        $client = new AmazonPayClient($this->getAmazonPayConfig());
        $result = $client->updateCheckoutSession($amazonCheckoutSessionId, $this->createUpdateCheckoutSessionPayload($Order));
        return json_decode($result['response']);
    }

    public function signaturePayload($payload){
        $client = new AmazonPayClient($this->getAmazonPayConfig());
        $signature = $client->generateButtonSignature($payload);
        return $signature;
    }
    public function getCheckoutSession($amazonCheckoutSessionId){
        $client = new AmazonPayClient($this->getAmazonPayConfig());
        $result = $client->getCheckoutSession($amazonCheckoutSessionId);
        return json_decode($result['response']);
    }
    public function completeCheckoutSession($Order, $amazonCheckoutSessionId){
        $client = new AmazonPayClient($this->getAmazonPayConfig());
        $result = $client->completeCheckoutSession($amazonCheckoutSessionId, $this->createCompleteCheckoutSessionPayload($Order));
        $response = json_decode($result['response']);
        if ($result['status'] == 200 || $result['status'] == 201) {
            if ($response->statusDetails->state == 'Completed') {
                return $response;
            }
        }else{
            if (isset($response->reasonCode)) {
                goto XkiHl;l9YE_:XkiHl:
                if ($response->reasonCode == 'CheckoutSessionCanceled') {
                    $checkoutSession = $this->getCheckoutSession($amazonCheckoutSessionId);
                    if ($checkoutSession && isset($checkoutSession->statusDetails->reasonCode)) {
                        $errorCode = AmazonPaymentException::getErrorCode($checkoutSession->statusDetails->reasonCode);
                        if ($errorCode) {
                            throw AmazonPaymentException::create($errorCode);
                        }
                    }
                }

            }
        }
        throw new AmazonException();
    }
    public function captureCharge($chargeId, $Order, $billingAmount = null){
        $client = new AmazonPayClient($this->getAmazonPayConfig());
        $headers = ['x-amz-pay-Idempotency-Key' => uniqid()];
        $result = $client->captureCharge($chargeId, $this->createCaptureChargePayload($Order, $billingAmount), $headers);
        return json_decode($result['response']);
    }
    public function cancelCharge($chargeId, $cancellationReason = null){
        $payload = $this->createCancelChargePayload($cancellationReason);
        $client = new AmazonPayClient($this->getAmazonPayConfig());
        $result = $client->cancelCharge($chargeId, $payload);
        return json_decode($result['response']);
    }
    public function closeChargePermission($chargePermissionId, $closureReason = null, $cancelPendingCharges = true){
        $payload = $this->createCloseChargePermissionPayload($closureReason, $cancelPendingCharges);
        $client = new AmazonPayClient($this->getAmazonPayConfig());
        $result = $client->closeChargePermission($chargePermissionId, $payload);
        return json_decode($result['response']);
    }
    public function createRefund($chargeId, $refundAmount, $softDescriptor = null, $idempotencyKey = null){
        $payload = $this->createCreateRefundPayload($chargeId, $refundAmount);
        if (null != $softDescriptor) {
            $payload = array_merge($payload, ["softDescriptor" => $softDescriptor]);
        }
        if ($idempotencyKey == null) {
            $idempotencyKey = uniqid();
        }
        $headers = ['x-amz-pay-Idempotency-Key' => $idempotencyKey];
        $client = new AmazonPayClient($this->getAmazonPayConfig());
        $result = $client->createRefund($payload, $headers);
        return json_decode($result['response']);
    }
    public function createCharge($chargePermissionId, $paymentTotal, $CaptureNow = false, $softDescriptor = null, $canHandlePendingAuthorization = false, $merchantMetadataMerchantReferenceId = null, $idempotencyKey = null)
    {
        $payload = $this->createCreateChargePayload($chargePermissionId, $paymentTotal, $CaptureNow, $canHandlePendingAuthorization);
        if (null != $merchantMetadataMerchantReferenceId) {
            $payload = array_merge($payload, ["merchantMetadata" => ["merchantReferenceId" => $merchantMetadataMerchantReferenceId]]);
        }
        if (null != $softDescriptor) {
            $payload = array_merge($payload, ["softDescriptor" => $softDescriptor]);
        }
        if ($idempotencyKey == null) {
            $idempotencyKey = uniqid();
        }

        $headers = ['x-amz-pay-Idempotency-Key' => $idempotencyKey];
        $client = new AmazonPayClient($this->getAmazonPayConfig());

        $result = $client->createCharge($payload, $headers);

        return json_decode($result['response']);
    }

    public function getCharge($chargeId){
        $client = new AmazonPayClient($this->getAmazonPayConfig());
        $result = $client->getCharge($chargeId);
        return json_decode($result['response']);
    }

    public function createSigninPayload($returnUrl){
        $Config = $this->configRepository->get();
        $payload = ['signInReturnUrl' => $returnUrl, 'storeId' => $Config->getClientId()];
        return json_encode($payload, JSON_FORCE_OBJECT);
    }

    public function getBuyer($buyerToken, $headers = null){
        $client = new AmazonPayClient($this->getAmazonPayConfig());
        $result = $client->getBuyer($buyerToken, $headers);
        if ($result['status'] != 200) {
            throw new AmazonException();
        }
        return json_decode($result['response']);
    }

    public function loginWithBuyerId(Request $request, $buyerId){
        $Customers = $this->customerRepository->getNonWithdrawingCustomers(['amazon_user_id' => $buyerId]);
        if (empty($Customers[0]) || !$Customers[0] instanceof \Eccube\Entity\Customer) {
            return false;
        }

        $token = new UsernamePasswordToken($Customers[0], null, 'customer', ['ROLE_USER']);
        $this->tokenStorage->setToken($token);
        $request->getSession()->migrate(true);
        $this->cartService->mergeFromPersistedCart();
        foreach ($this->cartService->getCarts() as $Cart) {
            $this->purchaseFlow->validate($Cart, new PurchaseContext($Cart, $Customers[0]));
        }
        $this->cartService->save();
        return true;
    }
}
?>