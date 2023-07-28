<?php

namespace Plugin\AmazonPay4\Controller;

use Eccube\Entity\Delivery;
use Eccube\Entity\OrderItem;
use Eccube\Repository\DeliveryRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\OrderRepository;
use Plugin\AmazonPay4\Repository\ConfigRepository;
use Plugin\AmazonPay4\Service\AmazonOrderHelper;
use Plugin\AmazonPay4\Service\AmazonRequestService;
use Plugin\AmazonPay4\Service\Method\AmazonPay;
use Eccube\Common\EccubeConfig;
use Eccube\Controller\AbstractShoppingController;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Form\Type\Shopping\OrderType;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\PrefRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Repository\PluginRepository;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\OrderHelper;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\AmazonPay4\Exception\AmazonPaymentException;
use Plugin\AmazonPay4\Amazon\Pay\API\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\LockMode;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AmazonShoppingController extends AbstractShoppingController
{
    private $sessionAmazonProfileKey = 'amazon_pay4.profile';
    private $sessionAmazonCheckoutSessionIdKey = 'amazon_pay4.checkout_session_id';
    private $sessionAmazonCustomerParamKey = 'amazon_pay4.customer_regist';
    private $sessionAmazonCustomerErrorKey = 'amazon_pay4.customer_regist_error';
    private $sessionIsShippingRefresh = 'amazon_pay4.is_shipping_refresh';

    protected $validator;
    protected $cartService;
    protected $amazonOrderHelper;
    protected $mailService;
    protected $orderHelper;
    protected $customerRepository;
    protected $orderRepository;
    protected $orderStatusRepository;
    protected $prefRepository;
    protected $productClassRepository;
    protected $pluginRepository;
    protected $Config;
    protected $amazonRequestService;
    protected $encoderFactory;
    protected $tokenStorage;
    protected $deliveryRepository;
    protected $serviceContainer;

    public function __construct(
        EccubeConfig $eccubeConfig,
        PurchaseFlow $cartPurchaseFlow,
        CartService $cartService,
        MailService $mailService,
        OrderHelper $orderHelper,
        CustomerRepository $customerRepository,
        ContainerInterface $serviceContainer,
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository,
        PrefRepository $prefRepository,
        ProductClassRepository $productClassRepository,
        PluginRepository $pluginRepository,
        ConfigRepository $configRepository,
        AmazonOrderHelper $amazonOrderHelper,
        AmazonRequestService $amazonRequestService,
        ValidatorInterface $validator,
        EncoderFactoryInterface $encoderFactory,
        TokenStorageInterface $tokenStorage,
        DeliveryRepository $deliveryRepository
    ){
        $this->eccubeConfig = $eccubeConfig;
        $this->purchaseFlow = $cartPurchaseFlow;
        $this->cartService = $cartService;
        $this->mailService = $mailService;
        $this->orderHelper = $orderHelper;
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->prefRepository = $prefRepository;
        $this->productClassRepository = $productClassRepository;
        $this->pluginRepository = $pluginRepository;
        $this->Config = $configRepository->get();
        $this->amazonOrderHelper = $amazonOrderHelper;
        $this->amazonRequestService = $amazonRequestService;
        $this->validator = $validator;
        $this->encoderFactory = $encoderFactory;
        $this->tokenStorage = $tokenStorage;
        $this->deliveryRepository = $deliveryRepository;
        $this->serviceContainer = $serviceContainer;
    }
    /**
     * @Route("/shopping/amazon_pay", name="amazon_pay_shopping")
     * @Template("Shopping/index.twig")
     *
     * @param Request $request
     */
    public function index(Request $request, PurchaseFlow $cartPurchaseFlow)
    {

        logs('amazon_pay4')->info('AmazonShopping::index start.');

        $Cart = $this->cartService->getCart();

        if (!($Cart && $this->orderHelper->verifyCart($Cart))) {
            logs('amazon_pay4')->info('[注文手続] カートが購入フローへ遷移できない状態のため, カート画面に遷移します.');
            return $this->redirectToRoute('cart');
        }

        $amazonCheckoutSessionId = $this->session->get($this->sessionAmazonCheckoutSessionIdKey);
        $checkoutSession = $this->amazonRequestService->getCheckoutSession($amazonCheckoutSessionId);
        if ($checkoutSession && $checkoutSession->statusDetails->state !== 'Open') {
            logs('amazon_pay4')->info('[注文手続] CheckoutSessionがOpenで無い為決済処理を中断します.', ['CheckoutSessionId => $amazonCheckoutSessionId']);
            $this->session->remove($this->sessionAmazonCheckoutSessionIdKey);
            return $this->redirectToRoute('shopping_error');
        }

        logs('amazon_pay4')->info('[注文手続] 受注の初期化処理を開始します.');


        $Customer = $this->getUser() ? $this->getUser() : $this->amazonOrderHelper->getOrderer($checkoutSession->shippingAddress);

        if (!$this->isGranted('ROLE_USER')) {
            $this->session->set(OrderHelper::SESSION_NON_MEMBER, $Customer);
        }

        $initOrderFlg = false;
        if ($Order = $this->orderHelper->getPurchaseProcessingOrder($Cart->getPreOrderId())) {

            if ($Order->isMultiple()) {
                $Cart->setPreOrderId(null);
            }
        }else{
            $initOrderFlg = true;
            $Order = $this->orderHelper->initializeOrder($Cart, $Customer);
            $Shipping = $Order->getShippings()->first();
            $AmazonDefaultDelivery = $this->getAmazonPayDefaultDelivery($Shipping);
            if (($AmazonDefaultDelivery === false)) {
                $this->addError('Amazon Payでご利用できる配送方法が存在しません。');
            }
            if (($initOrderFlg && $AmazonDefaultDelivery)) {
                $Shipping->setDelivery($AmazonDefaultDelivery);
                $Shipping->setShippingDeliveryName($AmazonDefaultDelivery->getName());
                $this->entityManager->flush();
            }
            $Order = $this->amazonOrderHelper->initializeAmazonOrder($Order, $Customer);
        }
        if ($this->session->get($this->sessionIsShippingRefresh)) {
            $Shippings = $Order->getShippings();
            $this->amazonOrderHelper->convert($Shippings->first(), $checkoutSession->shippingAddress);
            $this->entityManager->flush();
            $this->session->remove($this->sessionIsShippingRefresh);
        }

        logs('amazon_pay4')->info('[注文手続] 集計処理を開始します.', [$Order->getId()]);
        $flowResult = $this->executePurchaseFlow($Order, false);
        $this->entityManager->flush();
        if ($flowResult->hasError()) {
            logs('amazon_pay4')->info('[注文手続] Errorが発生したため購入エラー画面へ遷移します.', [$flowResult->getErrors()]);
            return $this->redirectToRoute('shopping_error');
        }

        if ($flowResult->hasWarning()) {
            logs('amazon_pay4')->info('[注文手続] Warningが発生しました.', [$flowResult->getWarning()]);
            $cartPurchaseFlow->validate($Cart, new PurchaseContext());
            $this->cartService->save();
        }

        if (!$amazonCustomerParam = $this->session->get($this->sessionAmazonCustomerParamKey)) {
            $arrAmazonCustomerParam = [
                'customer_regist' => true,
                'mail_magazine' => true,
                'login_check' => 'regist',
                'amazon_login_email' => null,
                'amazon_login_password' => null
            ];
        }else{
            $arrAmazonCustomerParam = unserialize($amazonCustomerParam);
            if (empty($arrAmazonCustomerParam['customer_regist'])) {
                $arrAmazonCustomerParam['customer_regist'] = false;
            }
            if (!empty($arrAmazonCustomerParam['mail_magazine'])) {
                $arrAmazonCustomerParam['mail_magazine'] = false;
            }
        }

        $form = $this->createForm(OrderType::class, $Order);

        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->setAmazonCustomerData($form, $arrAmazonCustomerParam);
        }
        if (($amazonCustomerError = $this->session->get($this->sessionAmazonCustomerErrorKey))) {
            $arrAmazonCustomerError = unserialize($amazonCustomerError);
            foreach ($arrAmazonCustomerError as $key => $val) {
                $form[$key]->addError(new FormError($val));
            }

            $this->session->set($this->sessionAmazonCustomerErrorKey, null);
        }

        $form->handleRequest($request);
        logs('amazon_pay4')->info('AmazonShopping::index end.');
        return [
            'form' => $form->createView(),
            'Order' => $Order,
            'AmazonCustomer' => $arrAmazonCustomerParam,
            'AmazonPaymentDescriptor' => $checkoutSession->paymentPreferences[0]->paymentDescriptor,
            'AmazonShippingAddress' => $checkoutSession->shippingAddress,
            'activeTradeLaws' => [],
        ];

    }
    /**
     * ご注文内容のご確認
     *
     * @Route("/shopping/amazon_pay/confirm", name="amazon_pay_shopping_confirm", methods={"POST"})
     * @Template("Shopping/confirm.twig")
     */
    public function confirm(Request $request)
    {
        logs('amazon_pay4')->info('AmazonShopping::confirm start.');
        $Cart = $this->cartService->getCart();
        if (!($Cart && $this->orderHelper->verifyCart($Cart))) {
            logs('amazon_pay4')->info('[注文手続] カートが購入フローへ遷移できない状態のため, カート画面に遷移します.');
            return $this->redirectToRoute('cart');
        }
        $preOrderId = $this->cartService->getPreOrderId();
        $Order = $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
        if (!$Order) {
            logs('amazon_pay4')->info('[リダイレクト] 購入処理中の受注が存在しません.');
            return $this->redirectToRoute('shopping_error');
        }
        $arrAmazonCustomerParam = $this->getAmazonCustomerParam($request);
        $this->session->set($this->sessionAmazonCustomerParamKey, serialize($arrAmazonCustomerParam));
        $form = $this->createForm(OrderType::class, $Order);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->setAmazonCustomerData($form, $arrAmazonCustomerParam);
        }
        $form->handleRequest($request);
        if ($arrAmazonCustomerError = $this->checkAmazonCustomerError($request, $form, $Order)) {
            $this->session->set($this->sessionAmazonCustomerErrorKey, serialize($arrAmazonCustomerError));
            return $this->redirectToRoute('amazon_pay_shopping');
        }
        if (($form->isSubmitted() && $form->isValid())) {
            logs('amazon_pay4')->info('[注文確認] 集計処理を開始します.', [$Order->getId()]);
            $response = $this->executePurchaseFlow($Order);
            $this->entityManager->flush();
            if ($response) {
                return $this->redirectToRoute('amazon_pay_shopping');
            }
            logs('amazon_pay4')->info('[注文確認] PaymentMethod::verifyを実行します.', [$Order->getPayment()->getMethodClass()]);
            $paymentMethod = $this->createPaymentMethod($Order, $form);
            $PaymentResult = $paymentMethod->verify();
            if ($PaymentResult) {
                if (!$PaymentResult->isSuccess()) {
                    $this->entityManager->rollback();
                    foreach ($PaymentResult->getErrors() as $error) {
                        $this->addError($error);
                    }
                    logs('amazon_pay4')->info('[注文確認] PaymentMethod::verifyのエラーのため, 注文手続き画面へ遷移します.', [$PaymentResult->getErrors()]);
                    return $this->redirectToRoute('amazon_pay_shopping');
                }

                $response = $PaymentResult->getResponse();
                if (($response && ($response->isRedirection() || $response->getContent()))) {
                    $this->entityManager->flush();
                    logs('amazon_pay4')->info('[注文確認] PaymentMethod::verifyが指定したレスポンスを表示します.');
                    return $response;
                }
            }
            $this->entityManager->flush();
            logs('amazon_pay4')->info('[注文確認] 注文確認画面を表示します.');
            return ['form' => $form->createView(), 'Order' => $Order];
        }
        logs('amazon_pay4')->info('[注文確認] フォームエラーのため, 注文手続画面へ遷移します', [$Order->getId()]);
        return $this->redirectToRoute('amazon_pay_shopping', ['request' => $request], 307);
    }
    /**
     * 購入処理
     *
     * @Route("/shopping/amazon_pay/checkout", name="amazon_pay_shopping_checkout", methods={"POST"})
     * @Template("Shopping/index.twig")
     */
    public function checkout(Request $request){
        logs('amazon_pay4')->info('AmazonShopping::order start.');

        $Cart = $this->cartService->getCart();
        if (!($Cart && $this->orderHelper->verifyCart($Cart))) {
            logs('amazon_pay4')->info('[注文手続] カートが購入フローへ遷移できない状態のため, カート画面に遷移します.');
            return $this->redirectToRoute('cart');
        }

        $preOrderId = $this->cartService->getPreOrderId();
        $Order = $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
        if (!$Order) {
            logs('amazon_pay4')->info('[リダイレクト] 購入処理中の受注が存在しません.');
            return $this->redirectToRoute('shopping_error');
        }

        $amazonCheckoutSessionId = $this->session->get($this->sessionAmazonCheckoutSessionIdKey);
        $checkoutSession = $this->amazonRequestService->getCheckoutSession($amazonCheckoutSessionId);
        $shippingDifference = $this->checkShippingDifference($Order, $checkoutSession->shippingAddress);
        if ($shippingDifference) {
            $this->session->set($this->sessionIsShippingRefresh, true);
            return $this->redirectToRoute('amazon_pay_shopping');
        }

        if (!$this->amazonOrderHelper->checkShippingPref($checkoutSession->shippingAddress)) {
            $this->addError('amazon_pay4.front.shopping.undefined_pref_error');
            logs('amazon_pay4')->error('[注文手続] 都道府県割当エラー', [$Order->getId()]);
            return $this->redirectToRoute('shopping_error');
        }
        if (($checkoutSession && $checkoutSession->statusDetails->state !== 'Open')) {
            logs('amazon_pay4')->info('[注文手続] CheckoutSessionがOpenで無い為決済処理を中断します.', ['CheckoutSessionId => $amazonCheckoutSessionId']);
            $this->session->remove($this->sessionAmazonCheckoutSessionIdKey);
            return $this->redirectToRoute('shopping_error');
        }


        if ($this->Config->getUseConfirmPage() == $this->eccubeConfig['amazon_pay4']['toggle']['off']) {
            $arrAmazonCustomerParam = $this->getAmazonCustomerParam($request);
            $this->session->set($this->sessionAmazonCustomerParamKey, serialize($arrAmazonCustomerParam));
            $form = $this->createForm(OrderType::class, $Order);
        }else{
            $amazonCustomerParam = $this->session->get($this->sessionAmazonCustomerParamKey);
            $arrAmazonCustomerParam = unserialize($amazonCustomerParam);
            $form = $this->createForm(OrderType::class, $Order, ['skip_add_form' => true]);
        }

        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->setAmazonCustomerData($form, $arrAmazonCustomerParam);

        }

        $form->handleRequest($request);
        if (!($this->Config->getUseConfirmPage() == $this->eccubeConfig['amazon_pay4']['toggle']['on'])) {
            if (($arrAmazonCustomerError = $this->checkAmazonCustomerError($request, $form, $Order))) {
                $this->session->set($this->sessionAmazonCustomerErrorKey, serialize($arrAmazonCustomerError));
                return $this->redirectToRoute('amazon_pay_shopping');
            }
        }

        if (($form->isSubmitted() && $form->isValid())) {
            $checkoutSession = $this->amazonRequestService->updateCheckoutSession($Order, $amazonCheckoutSessionId);
            if (isset($checkoutSession->reasonCode)) {
                logs('amazon_pay4')->error('reasonCode: ' . $checkoutSession->reasonCode . ' message: ' . $checkoutSession->message);
                $this->addError('予期しないエラーが発生しました。');
                return $this->redirectToRoute('amazon_pay_shopping');
            }

            logs('amazon_pay4')->info('購入処理開始', [$Order->getId()]);

            $response = $this->executePurchaseFlow($Order);
            $this->entityManager->flush();
            if ($response) {
                return $this->redirectToRoute('amazon_pay_shopping');
            }

            $paymentMethod = $this->createPaymentMethod($Order, $form);
            logs('amazon_pay4')->info('[注文処理] PaymentMethod::applyを実行します.');
            if (($response = $paymentMethod->apply())) {
                return $response;
            }

            $session_temp = ['IS_AUTHENTICATED_FULLY' => $this->isGranted('IS_AUTHENTICATED_FULLY'), $this->sessionAmazonCheckoutSessionIdKey => $amazonCheckoutSessionId, $this->sessionAmazonProfileKey => unserialize($this->session->get($this->sessionAmazonProfileKey)), $this->sessionAmazonCustomerParamKey => unserialize($this->session->get($this->sessionAmazonCustomerParamKey))];
            $Order->setAmazonPay4SessionTemp(serialize($session_temp));
            $this->entityManager->flush();
            return new RedirectResponse($checkoutSession->webCheckoutDetails->amazonPayRedirectUrl);
        }

        logs('amazon_pay4')->info('購入チェックエラー', [$Order->getId()]);
        return $this->redirectToRoute('amazon_pay_shopping', ['request' => $request], 307);

    }
    /**
     * 結果受取
     *
     * @Route("/shopping/amazon_pay/checkout_result", name="amazon_pay_shopping_checkout_result")
     */
    public function checkoutResult(Request $request){
        $preOrderId = $this->cartService->getPreOrderId();
        $Order = $this->orderRepository->findOneBy(['pre_order_id' => $preOrderId]);
        if (!$Order) {
            logs('amazon_pay4')->info('[リダイレクト] 受注が存在しません.');
            return $this->redirectToRoute('shopping_error');
        }
        if ($Order->getOrderStatus() == $this->orderStatusRepository->find(OrderStatus::NEW)) {
            logs('amazon_pay4')->info('[注文処理] IPNにより注文処理完了済.', [$Order->getId()]);
            $this->session->set(OrderHelper::SESSION_ORDER_ID, $Order->getId());
            logs('amazon_pay4')->info('[注文処理] カートをクリアします.', [$Order->getId()]);
            $this->cartService->clear();
            $Order->setAmazonPay4SessionTemp(null);
            $this->entityManager->flush();
            $this->session->set($this->sessionAmazonCheckoutSessionIdKey, null);
            $this->session->set($this->sessionAmazonCustomerParamKey, null);
            $this->session->set($this->sessionAmazonCustomerErrorKey, null);
            logs('amazon_pay4')->info('[注文処理] 購入完了画面へ遷移します.', [$Order->getId()]);
            return $this->redirectToRoute('shopping_complete');
        }else{

            if ($Order->getOrderStatus() != $this->orderStatusRepository->find(OrderStatus::PENDING)) {
                logs('amazon_pay4')->info('[リダイレクト] 決済処理中の受注が存在しません.');
                return $this->redirectToRoute('shopping_error');
            }
        }
        $amazonCheckoutSessionId = $request->get('amazonCheckoutSessionId');
        $amazonCustomerParam = $this->session->get($this->sessionAmazonCustomerParamKey);
        $arrAmazonCustomerParam = unserialize($amazonCustomerParam);


        try {
            logs('amazon_pay4')->info('決済完了レスポンス受取', [$Order->getId()]);
            $form = $this->createForm(OrderType::class, $Order);
            $paymentMethod = $this->createPaymentMethod($Order, $form, $amazonCheckoutSessionId);
            $this->entityManager->beginTransaction();
            $this->entityManager->lock($Order, LockMode::PESSIMISTIC_WRITE);
            logs('amazon_pay4')->info('[注文処理] PaymentMethod::checkoutを実行します.');
            if ($response = $this->executeCheckout($paymentMethod, $Order)) {
                return $response;
            }


            $profile = unserialize($this->session->get($this->sessionAmazonProfileKey));
            if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
                $Customer = $this->getUser();
                $Customers = $this->customerRepository->getNonWithdrawingCustomers(['amazon_user_id' => $profile->buyerId]);
                if (!$Customer->getAmazonUserId() && empty($Customers[0])) {
                    $Customer->setAmazonUserId($profile->buyerId);
                }
            }else{
                if (empty($arrAmazonCustomerParam['login_check']) || $arrAmazonCustomerParam['login_check'] == 'regist') {
                    if ($arrAmazonCustomerParam['customer_regist']) {
                        $url = $this->generateUrl('mypage_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
                        if ($this->customerRepository->getNonWithdrawingCustomers(['email' => $Order->getEmail()]) ||
                            $this->customerRepository->getNonWithdrawingCustomers(['amazon_user_id' => $profile->buyerId])) {
                            $mail = $Order->getEmail();
                            $mail_message = <<<__EOS__
    ************************************************
    　会員登録情報
    ************************************************
    マイページURL：{$url}
    ※会員登録済みです。メールアドレスは{$mail}です。

__EOS__;
                        } else {
                            $password = $this->amazonOrderHelper->registCustomer($Order, $arrAmazonCustomerParam['mail_magazine']);
                            $Customer = $this->getUser();
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
                        $this->setLogin($request, $Order);
                        $Customer = $Order->getCustomer();
                        $Customers = $this->customerRepository->getNonWithdrawingCustomers(['amazon_user_id' => $profile->buyerId]);
                        if (!$Customer->getAmazonUserId() && empty($Customers[0])) {
                            hqGe0:$Customer->setAmazonUserId($profile->buyerId);
                        }
                    }

                }

            }

            $this->entityManager->flush();
            $this->entityManager->commit();
            logs('amazon_pay4')->info('[注文処理] 注文処理が完了しました.', [$Order->getId()]);
            logs('amazon_pay4')->info('購入処理完了', [$Order->getId()]);

        } catch (ShoppingException $e) {
            $this->addError($e->getMessage());
            logs('amazon_pay4')->error('購入エラー', [$e->getMessage()]);
            $this->entityManager->getConnection()->rollback();
            $this->purchaseFlow->rollback($Order, new PurchaseContext());
            $this->entityManager->flush();
            return $this->redirectToRoute('shopping_error');
        } catch (AmazonPaymentException $e) {
            $this->addError($e->getMessage());
            logs('amazon_pay4')->error($e->getMessage(), [$Order->getId()]);
            $this->entityManager->getConnection()->rollback();
            $this->purchaseFlow->rollback($Order, new PurchaseContext());
            $this->entityManager->flush();
            return $this->redirectToRoute('shopping_error');
        } catch (\Exception $e) {
            $this->addError('front.shopping.system_error');
            logs('amazon_pay4')->error('予期しないエラー', [$e->getMessage()]);
            $this->entityManager->getConnection()->rollback();
            $this->purchaseFlow->rollback($Order, new PurchaseContext());
            $this->entityManager->flush();
            return $this->redirectToRoute('shopping_error');
        }


        logs('amazon_pay4')->info('AmazonShopping::complete_order end.');
        $this->session->set(OrderHelper::SESSION_ORDER_ID, $Order->getId());
        logs('amazon_pay4')->info('[注文処理] 注文メールの送信を行います.', [$Order->getId()]);
        if (!is_null($this->Config->getMailNotices())) {
            $Order->appendCompleteMailMessage("特記事項：" . $this->Config->getMailNotices());
        }
        $this->mailService->sendOrderMail($Order);
        $this->entityManager->flush();
        logs('amazon_pay4')->info('[注文処理] カートをクリアします.', [$Order->getId()]);
        $this->cartService->clear();
        $Order->setAmazonPay4SessionTemp(null);
        $this->entityManager->flush();
        $this->session->set($this->sessionAmazonCheckoutSessionIdKey, null);
        $this->session->set($this->sessionAmazonCustomerParamKey, null);
        $this->session->set($this->sessionAmazonCustomerErrorKey, null);
        logs('amazon_pay4')->info('[注文処理] 注文処理が完了しました. 購入完了画面へ遷移します.', [$Order->getId()]);
        return $this->redirectToRoute('shopping_complete');
    }
    /**
     * 購入確認画面から, 他の画面へのリダイレクト.
     * 配送業者や支払方法、お問い合わせ情報をDBに保持してから遷移する.
     *
     * @Route("/shopping/amazon_pay/redirect_to", name="amazon_pay_shopping_redirect_to", methods={"POST"})
     * @Template("Shopping/index.twig")
     */
    public function redirectTo(Request $request, RouterInterface $router)
    {
        $preOrderId = $this->cartService->getPreOrderId();
        $Order = $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
        if (!$Order) {
            logs('amazon_pay4')->info('[リダイレクト] 購入処理中の受注が存在しません.');
            return $this->redirectToRoute('shopping_error');
        }
        $form = $this->createForm(OrderType::class, $Order);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            logs('amazon_pay4')->info('[リダイレクト] 集計処理を開始します.', [$Order->getId()]);

            $flowResult = $this->executePurchaseFlow($Order, false);
            $this->entityManager->flush();
            if ($flowResult->hasError()) {
                logs('amazon_pay4')->info('Errorが発生したため購入エラー画面へ遷移します.', [$flowResult->getErrors()]);
                return $this->redirectToRoute('shopping_error');
            }
            if ($flowResult->hasWarning()) {
                logs('amazon_pay4')->info('Warningが発生したため注文手続き画面へ遷移します.', [$flowResult->getWarning()]);
                return $this->redirectToRoute('amazon_pay_shopping');
            }
            $redirectTo = $form['redirect_to']->getData();
            if (empty($redirectTo)) {
                logs('amazon_pay4')->info('[リダイレクト] リダイレクト先未指定のため注文手続き画面へ遷移します.');
                return $this->redirectToRoute('amazon_pay_shopping');
            }
            try {
                $pattern = '/^' . preg_quote($request->getBasePath(), '/') . '/';
                $redirectTo = preg_replace($pattern, '', $redirectTo);
                $result = $router->match($redirectTo);
                return $this->forwardToRoute($result['_route']);
            } catch (\Exception $e) {
                logs('amazon_pay4')->info('[リダイレクト] URLの形式が不正です', [$redirectTo, $e->getMessage()]);
                return $this->redirectToRoute('shopping_error');
            }
        }

        logs('amazon_pay4')->info('[リダイレクト] フォームエラーのため, 注文手続き画面を表示します.', [$Order->getId()]);

        return $this->redirectToRoute('amazon_pay_shopping', ['request' => $request], 307);
    }
    /**
     * API通信によって配送業者や支払方法、お問い合わせ情報をDBに保存する.
     *
     * @Route("/shopping/amazon_pay/order_save", name="amazon_pay_shopping_order_save", methods={"POST", "GET"})
     */
    public function orderSave(Request $request)
    {
        /*if ($request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }*/
        $preOrderId = $this->cartService->getPreOrderId();
		
        $Order = $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
        /*if ($Order) {
            logs('amazon_pay4')->info('購入処理中の受注が存在しません.');
            return $this->json(['error' => empty($Order)], 500);
        }*/
        $form = $this->createForm(OrderType::class, $Order);
        $form->handleRequest($request);
        if (($form->isSubmitted() && $form->isValid())) {

            logs('amazon_pay4')->info('集計処理を開始します.', [$Order->getId()]);
            $flowResult = $this->executePurchaseFlow($Order, false);
            $this->entityManager->flush();
            if ($flowResult->hasError()) {
                logs('amazon_pay4')->info('executePurchaseFlowでErrorが発生しました.', [$flowResult->getErrors()]);
                return $this->json(['error' => 'executePurchaseFlow::Error'], 500);
            }
            if ($flowResult->hasWarning()) {
                logs('amazon_pay4')->info('executePurchaseFlowでWarningが発生しました.', [$flowResult->getWarning()]);
                return $this->json(['error' => 'executePurchaseFlow::Warning'], 500);
            }
            return $this->json([]);

        }
        logs('amazon_pay4')->info('フォームエラーが発生しました.');
        return $this->json(['error' => 'validateError'], 500);
    }

    private function createPaymentMethod(Order $Order, FormInterface $form, $amazonCheckoutSessionId = null)
    {
		$PaymentMethod = $this->serviceContainer->get($Order->getPayment()->getMethodClass());
		$PaymentMethod->setOrder($Order);
        $PaymentMethod->setFormType($form);
        if (!is_null($amazonCheckoutSessionId)) {
            $PaymentMethod->setAmazonCheckoutSessionId($amazonCheckoutSessionId);
        }
        return $PaymentMethod;
    }

    protected function executeCheckout(AmazonPay $paymentMethod, Order $Order)
    {
        $PaymentResult = $paymentMethod->checkout();
        $response = $PaymentResult->getResponse();
        if ($response && ($response->isRedirection() || $response->getContent())) {
            $this->entityManager->flush();
            logs('amazon_pay4')->info('[注文処理] PaymentMethod::checkoutが指定したレスポンスを表示します.');
            return $response;
        }

        if (!$PaymentResult->isSuccess()) {
            $this->purchaseFlow->rollback($Order, new PurchaseContext());
            foreach ($PaymentResult->getErrors() as $error) {
                $this->addError($error);
            }

            logs('amazon_pay4')->info('[注文処理] PaymentMethod::checkoutのエラーのため, 購入エラー画面へ遷移します.', [$PaymentResult->getErrors()]);

            return $this->redirectToRoute('shopping_error');
        }
    }

    private function getAmazonCustomerParam($request){goto mfNsU;mfNsU:$customer_regist = empty($request->get('_shopping_order')['customer_regist']) ? false : true;goto JAhkK;oug8w:$login_check = empty($request->get('_shopping_order')['login_check']) ? null : $request->get('_shopping_order')['login_check'];goto LEDjX;LEDjX:$amazon_login_email = empty($request->get('_shopping_order')['amazon_login_email']) ? null : $request->get('_shopping_order')['amazon_login_email'];goto kalER;j5UYM:return ['customer_regist' => $customer_regist, 'mail_magazine' => $mail_magazine, 'login_check' => $login_check, 'amazon_login_email' => $amazon_login_email, 'amazon_login_password' => $amazon_login_password];goto W5eEP;kalER:$amazon_login_password = empty($request->get('_shopping_order')['amazon_login_password']) ? null : $request->get('_shopping_order')['amazon_login_password'];goto j5UYM;JAhkK:$mail_magazine = empty($request->get('_shopping_order')['mail_magazine']) ? false : true;goto oug8w;W5eEP:
    }
    private function checkAmazonCustomerError($request, $form, $Order){goto J3sd1;synk_:goto lWYno;goto SZLLG;Slnln:$AmazonCustomer = $this->customerRepository->getNonWithdrawingCustomers(['amazon_user_id' => $profile->buyerId]);goto WXFZ2;sj9z8:$profile = unserialize($this->session->get($this->sessionAmazonProfileKey));goto Slnln;ia5c9:return $arrError;goto hjJ7C;VW4Z0:$salt = $Customer[0]->getSalt();goto f00et;c9Kp9:tRi76:goto xFqO3;SZLLG:Rtefb:goto zK6FW;GUqiL:goto NqYJ6;goto DWSM1;eTdbF:Jh3iD:goto e4qbE;mUm3L:$amazon_login_email_error = '';goto yffyE;zX1l2:$Customer = $this->customerRepository->getNonWithdrawingCustomers(['email' => $form['amazon_login_email']->getData()]);goto wCypk;wCypk:if (empty($Customer[0])) {goto pcGZ6;}goto dqyff;yffyE:foreach ($violations as $violation) {$amazon_login_email_error .= $violation->getMessage() . PHP_EOL;yl1d3:}goto iY2yG;mO6al:if ($login_check == 'regist') {goto Rtefb;}goto f6ILC;GfkDC:goto Jh3iD;goto c9Kp9;e4qbE:lWYno:goto Cm2wa;CKAlu:kHGCI:goto HE5tt;zK6FW:if (empty($form['customer_regist']->getData())) {goto tRi76;}goto GQ_rR;hx6Vk:$violations = $this->validator->validate($form['amazon_login_email']->getData(), [new Assert\NotBlank(), new Assert\Email()]);goto mUm3L;tiIDE:GOpeo:goto aXeVU;ok5BW:$arrError['amazon_login_email'] = '※ メールアドレスまたはパスワードが正しくありません。';goto qjQCN;jeAk2:if (!(!$this->isGranted('IS_AUTHENTICATED_FULLY') && $this->Config->getLoginRequired() == $this->eccubeConfig['amazon_pay4']['toggle']['on'])) {goto OSsAs;}goto elqf2;J3sd1:$arrError = [];goto jeAk2;nCGnt:foreach ($violations as $violation) {$amazon_login_password_error .= $violation->getMessage() . PHP_EOL;S2tDP:}goto BmNfI;DWSM1:NS_gq:goto cuiLE;f00et:$customerPassword = $Customer[0]->getPassword();goto AzVWJ;KqXsy:$login_check = $form['login_check']->getData();goto mO6al;Vb4gJ:NqYJ6:goto GfkDC;MPmPq:if (empty($amazon_login_email_error)) {goto kHGCI;}goto f2cay;nQukN:goto fXeEH;goto qusFn;elqf2:$request_uri = $request->getUri();goto qPElB;Fc6Gl:$amazon_login_password_error = '';goto nCGnt;Cm2wa:tebSD:goto u8hHJ;xFqO3:$arrError['customer_regist'] = '※ 会員登録が選択されていません。';goto eTdbF;r5UxA:$arrError['amazon_login_email'] = '※ メールアドレスまたはパスワードが正しくありません。';goto g6mWb;f2cay:$arrError['amazon_login_email'] = '※ メールアドレスが' . $amazon_login_email_error;goto CKAlu;yX7N1:Zud31:goto GUqiL;BmNfI:rWFhW:goto xNc3u;iY2yG:KRVQC:goto MPmPq;qusFn:pcGZ6:goto r5UxA;gpK8e:XvFo5:goto synk_;biiew:$arrError['customer_regist'] = '※ このAmazonアカウントで既に会員登録済みです。メールアドレスは' . $AmazonCustomer[0]->getEmail() . 'です。';goto yX7N1;AzVWJ:if ($encoder->isPasswordValid($customerPassword, $form['amazon_login_password']->getData(), $salt)) {goto cMhb4;}goto ok5BW;cuiLE:$arrError['customer_regist'] = '※ 会員登録済みです。メールアドレスは' . $Order->getEmail() . 'です。';goto Vb4gJ;WXFZ2:if (!empty($Customer[0])) {goto NS_gq;}goto Xuywv;f6ILC:if (!($login_check == 'login')) {goto XvFo5;}goto hx6Vk;E9hJ0:h1yxX:goto gpK8e;Xuywv:if (empty($AmazonCustomer[0])) {goto Zud31;}goto biiew;aXeVU:if (!(empty($login_check_error) && empty($amazon_login_email_error) && empty($amazon_login_password_error))) {goto h1yxX;}goto zX1l2;g6mWb:fXeEH:goto E9hJ0;qPElB:if (!('POST' === $request->getMethod() && strpos($request_uri, 'shopping/amazon_pay/address') === false && strpos($request_uri, 'shopping/amazon_pay/delivery') === false)) {goto tebSD;}goto KqXsy;dqyff:$encoder = $this->encoderFactory->getEncoder($Customer[0]);goto VW4Z0;qqwEO:$arrError['amazon_login_password'] = '※ パスワードが' . $amazon_login_password_error;goto tiIDE;qjQCN:cMhb4:goto nQukN;GQ_rR:$Customer = $this->customerRepository->getNonWithdrawingCustomers(['email' => $Order->getEmail()]);goto sj9z8;xNc3u:if (empty($amazon_login_password_error)) {goto GOpeo;}goto qqwEO;HE5tt:$violations = $this->validator->validate($form['amazon_login_password']->getData(), [new Assert\NotBlank()]);goto Fc6Gl;u8hHJ:OSsAs:goto ia5c9;hjJ7C:
    }
    private function setLogin($request, $Order){goto Tg3pf;Tg3pf:$Customer = $this->customerRepository->getNonWithdrawingCustomers(['email' => $Order->getEmail()]);goto Cp5OB;Cp5OB:$Order->setCustomer($Customer[0]);goto ahm48;w24uA:$this->tokenStorage->setToken($token);goto rQY2U;ahm48:$token = new UsernamePasswordToken($Customer[0], null, 'customer', ['ROLE_USER']);goto w24uA;rQY2U:$this->amazonOrderHelper->copyToOrderFromCustomer($Order, $Customer[0]);goto sEIuV;sEIuV:
    }
    private function setAmazonCustomerData($form, $arrAmazonCustomerParam)
    {
        $form->get('customer_regist')->setData($arrAmazonCustomerParam['customer_regist']);
        if ($this->pluginRepository->findOneBy(['code' => 'MailMagazine4', 'enabled' => true]) || $this->pluginRepository->findOneBy(['code' => 'PostCarrier4', 'enabled' => true])) {
            $form->get('mail_magazine')->setData($arrAmazonCustomerParam['mail_magazine']);
        }
        if ($this->Config->getLoginRequired() == $this->eccubeConfig['amazon_pay4']['toggle']['on'] && !$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            $form->get('login_check')->setData($arrAmazonCustomerParam['login_check']);
            $form->get('amazon_login_email')->setData($arrAmazonCustomerParam['amazon_login_email']);
            $form->get('amazon_login_password')->setData($arrAmazonCustomerParam['amazon_login_password']);
        }
    }
    public function getPendingProcessingOrder($preOrderId = null)
    {
        goto RHCM7;jCjVX:return null;goto uAfkk;Doy3m:$OrderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);goto yyjJT;uAfkk:a5015:
        goto Doy3m;yyjJT:return $this->orderRepository->findOneBy(['pre_order_id' => $preOrderId, 'OrderStatus' => $OrderStatus]);
        goto rgZP2;RHCM7:if (!(null === $preOrderId)) {goto a5015;}goto jCjVX;rgZP2:
    }
    private function checkShippingDifference($Order, $shippingAddress)
    {
        goto dxI6P;nZ2m0:return $shippingDifference;goto f9G2I;FKrWl:$shippingDifference = true;
        goto vnxL9;H5O65:
        if (!($Shippings->first()->getPostalCode() !== $amazonShipping->getPostalCode() ||
            $Shippings->first()->getName01() !== $amazonShipping->getName01() ||
            $Shippings->first()->getName02() !== $amazonShipping->getName02() ||
            $Shippings->first()->getKana01() !== $amazonShipping->getKana01() ||
            $Shippings->first()->getKana02() !== $amazonShipping->getKana02() ||
            $Shippings->first()->getPref() !== $amazonShipping->getPref() ||
            $Shippings->first()->getAddr01() !== $amazonShipping->getAddr01() ||
            $Shippings->first()->getAddr02() !== $amazonShipping->getAddr02()))
        {
            goto AI00W;
        }goto FKrWl;jzil3:$this->amazonOrderHelper->convert($amazonShipping, $shippingAddress);
        goto SeA_J;vnxL9:AI00W:goto nZ2m0;SeA_J:$Shippings = $Order->getShippings();goto uEx9H;uEx9H:$shippingDifference = false;
        goto H5O65;EzwAP:$amazonShipping->setOrder($Order);goto jzil3;dxI6P:$amazonShipping = new Shipping();goto EzwAP;f9G2I:
    }

    protected function getAmazonPayDefaultDelivery(Shipping $Shipping)
    {
        goto EgAN0;ZvHLv:kTGtg:goto yDa1y;yDa1y:$Deliveries = $this->deliveryRepository->getDeliveries($SaleTypes);
        goto XlTQ5;XlTQ5:
        foreach ($Deliveries as $key => $Delivery) {
            goto iAdv6;D3tx2:Uk9ps:
            goto AbP37;UYOu2:unset($Deliveries[$key]);goto Yvn0t;RxHt9:$amazonPayFlg = false;goto upSfN;XlvZP:if ($amazonPayFlg) {goto MWhCo;}
            goto UYOu2;akJ_H:AloX1:goto XlvZP;
            upSfN:
            foreach ($PaymentOptions as $PaymentOption) {
                goto k5i6R;Uqzs8:wei0b:
                goto QHs6V;oVBEN:$amazonPayFlg = true;goto SmtJn;wHblS:BajFs:goto Uqzs8;SmtJn:goto AloX1;
                goto wHblS;k5i6R:$Payment = $PaymentOption->getPayment();
                goto kPhwK;kPhwK:if (!($Payment->getMethodClass() === AmazonPay::class)) {goto BajFs;}goto oVBEN;QHs6V:
            }
            goto akJ_H;iAdv6:$PaymentOptions = $Delivery->getPaymentOptions();
            goto RxHt9;Yvn0t:MWhCo:goto D3tx2;AbP37:
        }
        goto uATtg;uATtg:qIQN1:goto NXI2M;NXI2M:$Delivery = current($Deliveries);
        goto OjPbA;OjPbA:return $Delivery;
        goto o3XJJ;
        e1S_q:
        foreach ($OrderItems as $OrderItem) {
            goto YqVBL;YqVBL:$ProductClass = $OrderItem->getProductClass();goto dPTKW;dPTKW:$SaleType = $ProductClass->getSaleType();
            goto AOhdw;AOhdw:$SaleTypes[$SaleType->getId()] = $SaleType;goto TQbL9;TQbL9:uAMH_:goto i1DdY;i1DdY:
        }
        goto ZvHLv;pB1UZ:$SaleTypes = [];
        goto e1S_q;EgAN0:$OrderItems = $Shipping->getProductOrderItems();
        goto pB1UZ;o3XJJ:
    }
}