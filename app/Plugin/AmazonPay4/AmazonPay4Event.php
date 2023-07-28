<?php

namespace Plugin\AmazonPay4;

use Eccube\Event\TemplateEvent;
use Eccube\Event\EventArgs;
use Eccube\Event\EccubeEvents;
use Eccube\Common\EccubeConfig;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\PluginRepository;
use Eccube\Service\OrderHelper;
use Eccube\Service\CartService;
use Plugin\AmazonPay4\Repository\ConfigRepository;
use Plugin\AmazonPay4\Service\AmazonRequestService;
use Plugin\AmazonPay4\Service\Method\AmazonPay;
use Plugin\AmazonPay4\phpseclib\Crypt\Random;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Eccube\Repository\DeliveryRepository;
use Eccube\Repository\PaymentOptionRepository;

class AmazonPay4Event implements EventSubscriberInterface{
    private $sessionAmazonProfileKey = 'amazon_pay4.profile';
    private $sessionAmazonCheckoutSessionIdKey = 'amazon_pay4.checkout_session_id';
    private $sessionAmazonLoginStateKey = 'amazon_pay4.amazon_login_state';
    protected $eccubeConfig;
    private $router;
    protected $configRepository;
    protected $amazonRequestService;
    protected $deliveryRepository;
    protected $paymentOptionRepository;
    protected $requestStack;
    protected $session;
    protected $tokenStorage;
    protected $paymentRepository;
    protected $pluginRepository;
    protected $container;
    protected $orderHelper;
    protected $cartService;

    public function __construct(
        RequestStack $requestStack,
        SessionInterface $session,
        TokenStorageInterface $tokenStorage,
        EccubeConfig $eccubeConfig,
        UrlGeneratorInterface $router,
        PaymentRepository $paymentRepository,
        PluginRepository $pluginRepository,
        ConfigRepository $configRepository,
        ContainerInterface $container,
        OrderHelper $orderHelper,
        CartService $cartService,
        AmazonRequestService $amazonRequestService,
        DeliveryRepository $deliveryRepository,
        PaymentOptionRepository $paymentOptionRepository
    ){
        $this->requestStack = $requestStack;
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->eccubeConfig = $eccubeConfig;
        $this->router = $router;
        $this->paymentRepository = $paymentRepository;
        $this->pluginRepository = $pluginRepository;
        $this->configRepository = $configRepository;
        $this->container = $container;
        $this->orderHelper = $orderHelper;
        $this->cartService = $cartService;
        $this->amazonRequestService = $amazonRequestService;
        $this->deliveryRepository = $deliveryRepository;
        $this->paymentOptionRepository = $paymentOptionRepository;
    }
    public static function getSubscribedEvents()
    {
        return [
            EccubeEvents::FRONT_CART_BUYSTEP_COMPLETE => 'amazon_cart_buystep',
            'Cart/index.twig' => 'cart',
            'Shopping/index.twig' => 'amazon_pay_shopping',
            'Mypage/login.twig' => 'mypage_login',
            'Shopping/confirm.twig' => 'amazon_pay_shopping_confirm'
        ];
    }

    public function cart(TemplateEvent $event){
        $parameters = $event->getParameters();
        if (empty($parameters['Carts'])) {
            return;
        }

        $Config = $this->configRepository->get();
        if ($Config->getUseCartButton() == $this->eccubeConfig['amazon_pay4']['toggle']['off']) {
            return;
        }

        $Payment = $this->paymentRepository->findOneBy(['method_class' => AmazonPay::class]);
        $AmazonDeliveries = $this->paymentOptionRepository->findBy(['payment_id' => $Payment->getId()]);

        $AmazonSaleTypes = [];
        foreach ($AmazonDeliveries as $AmazonDelivery) {
            $Delivery = $this->deliveryRepository->findOneBy(['id' => $AmazonDelivery->getDelivery()->getId()]);
            $AmazonSaleTypes[] = $Delivery->getSaleType()->getId();
        }
        $parameters['AmazonSaleTypes'] = $AmazonSaleTypes;

        foreach ($parameters['Carts'] as $Cart) {
            $cartKey = $Cart->getCartKey();
            $payload = $this->amazonRequestService->createCheckoutSessionPayload($Cart->getCartKey());
            $signature = $this->amazonRequestService->signaturePayload($payload);
            $parameters['cart'][$cartKey]['payload'] = $payload;
            $parameters['cart'][$cartKey]['signature'] = $signature;
        }

        $parameters['AmazonPay4Config'] = $Config;
        $parameters['AmazonPay4Api'] = $this->eccubeConfig['amazon_pay4']['api']['sandbox'];

        if ($Config->getEnv() == $this->eccubeConfig['amazon_pay4']['env']['prod']) {
            $parameters['AmazonPay4Api'] = $this->eccubeConfig['amazon_pay4']['api']['prod'];
        }
        $event->setParameters($parameters);

        if (($Config->getCartButtonPlace() == $this->eccubeConfig['amazon_pay4']['button_place']['auto'])) {
            $event->addSnippet('@AmazonPay4/default/Cart/button.twig');
        }

        $event->addSnippet('@AmazonPay4/default/Cart/amazon_pay_js.twig');

    }

    public function amazon_cart_buystep(EventArgs $event)
    {
        if ($this->orderHelper->getNonmember() && $this->session->get($this->sessionAmazonProfileKey)) {
            $this->session->remove(OrderHelper::SESSION_NON_MEMBER);
            $this->session->remove($this->sessionAmazonProfileKey);
            $this->cartService->setPreOrderId(null);
            $this->cartService->save();
        }
    }

    public function amazon_pay_shopping(TemplateEvent $event)
    {
        $request = $this->requestStack->getMasterRequest();
        $uri = $request->getUri();

        $parameters = $event->getParameters();

        if (preg_match('/shopping\\/amazon_pay/', $uri) == false) {
            $referer = $request->headers->get('referer');
            $Order = $parameters['Order'];
            $Payment = $Order->getPayment();
            if ($Payment && $Payment->getMethodClass() === AmazonPay::class && preg_match('/shopping_coupon/', $referer)) {
                header("Location:" . $this->container->get('router')->generate('amazon_pay_shopping'));
                exit;
            }
            return;
        }

        $Config = $this->configRepository->get();

        $event->addSnippet('@AmazonPay4/default/Shopping/widgets.twig');
        $event->addSnippet('@AmazonPay4/default/Shopping/customer_regist.twig');

        $amazonCheckoutSessionId = $this->session->get($this->sessionAmazonCheckoutSessionIdKey);
        $parameters = $event->getParameters();
        $parameters['amazonCheckoutSessionId'] = $amazonCheckoutSessionId;
        $parameters['AmazonPay4Config'] = $Config;

        if ($this->pluginRepository->findOneBy(['code' => 'MailMagazine4', 'enabled' => true]) || $this->pluginRepository->findOneBy(['code' => 'PostCarrier4', 'enabled' => true])) {
            $useMailMagazine = true;
        }else{
            $useMailMagazine = false;
        }
        $parameters['useMailMagazine'] = $useMailMagazine;

        if ($Config->getEnv() == $this->eccubeConfig['amazon_pay4']['env']['prod']) {
            $parameters['AmazonPay4Api'] = $this->eccubeConfig['amazon_pay4']['api']['prod'];
        }else{
            $parameters['AmazonPay4Api'] = $this->eccubeConfig['amazon_pay4']['api']['sandbox'];
        }

        $event->setParameters($parameters);

    }

    public function amazon_pay_shopping_confirm(TemplateEvent $event)
    {
        $request = $this->requestStack->getMasterRequest();
        $uri = $request->getUri();
        if (preg_match('/shopping\\/amazon_pay/', $uri) == false) {
            return;
        }
        $Config = $this->configRepository->get();
        $event->addSnippet('@AmazonPay4/default/Shopping/confirm_widgets.twig');
        $event->addSnippet('@AmazonPay4/default/Shopping/confirm_customer_regist.twig');
        $parameters = $event->getParameters();
        $parameters['AmazonPay4Config'] = $Config;
        if ($this->pluginRepository->findOneBy(['code' => 'MailMagazine4', 'enabled' => true]) || $this->pluginRepository->findOneBy(['code' => 'PostCarrier4', 'enabled' => true])) {
            $useMailMagazine = true;
        }else{
            $useMailMagazine = false;
        }
        $parameters['useMailMagazine'] = $useMailMagazine;
        if ($Config->getEnv() == $this->eccubeConfig['amazon_pay4']['env']['prod']) {
            $parameters['AmazonPay4Api'] = $this->eccubeConfig['amazon_pay4']['api']['prod'];
        }else{
            $parameters['AmazonPay4Api'] = $this->eccubeConfig['amazon_pay4']['api']['sandbox'];
        }
        $event->setParameters($parameters);
    }

    public function mypage_login(TemplateEvent $event)
    {
        $Config = $this->configRepository->get();
        if ($Config->getUseMypageLoginButton() == $this->eccubeConfig['amazon_pay4']['toggle']['off']) {
            return;
        }
        $state = $this->session->get($this->sessionAmazonLoginStateKey);
        if (!($state)) {
            $state = bin2hex(Random::string(16));
            $this->session->set($this->sessionAmazonLoginStateKey, $state);
        }
        $returnUrl = $this->router->generate('login_with_amazon', ['state' => $state], UrlGeneratorInterface::ABSOLUTE_URL);
        $parameters = $event->getParameters();
        $payload = $this->amazonRequestService->createSigninPayload($returnUrl);
        $signature = $this->amazonRequestService->signaturePayload($payload);
        $parameters['payload'] = $payload;
        $parameters['signature'] = $signature;
        $parameters['buttonColor'] = $Config->getMypageLoginButtonColor();
        $parameters['AmazonPay4Config'] = $Config;
        if ($Config->getEnv() == $this->eccubeConfig['amazon_pay4']['env']['prod']) {
            $parameters['AmazonPay4Api'] = $this->eccubeConfig['amazon_pay4']['api']['prod'];
        }else{
            $parameters['AmazonPay4Api'] = $this->eccubeConfig['amazon_pay4']['api']['sandbox'];
        }
        $event->setParameters($parameters);
        if ($Config->getMypageLoginButtonPlace() == $this->eccubeConfig['amazon_pay4']['button_place']['auto']) {
            $event->addSnippet('@AmazonPay4/default/Mypage/login_page_button.twig');
        }
        $event->addSnippet('@AmazonPay4/default/Mypage/amazon_login_js.twig');
    }
}
?>