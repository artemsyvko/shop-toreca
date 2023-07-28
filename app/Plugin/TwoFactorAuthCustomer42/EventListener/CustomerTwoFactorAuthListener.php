<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\TwoFactorAuthCustomer42\EventListener;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Request\Context;
use Plugin\TwoFactorAuthCustomer42\Repository\TwoFactorAuthTypeRepository;
use Plugin\TwoFactorAuthCustomer42\Repository\TwoFactorAuthCustomerCookieRepository;
use Plugin\TwoFactorAuthCustomer42\Service\CustomerTwoFactorAuthService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Contracts\EventDispatcher\Event;

class CustomerTwoFactorAuthListener implements EventSubscriberInterface
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;
    /**
     * @var Context
     */
    protected $requestContext;
    /**
     * @var UrlGeneratorInterface
     */
    protected $router;
    /**
     * @var CustomerTwoFactorAuthService
     */
    protected $customerTwoFactorAuthService;
    /**
     * @var TwoFactorAuthTypeRepository
     */
    protected TwoFactorAuthTypeRepository $twoFactorAuthTypeRepository;
    /**
     * @var TwoFactorAuthCustomerCookieRepository
     */
    protected TwoFactorAuthCustomerCookieRepository $twoFactorAuthCustomerCookieRepository;
    /**
     * @var BaseInfo|object|null
     */
    protected $baseInfo;
    /**
     * @var Session
     */
    protected $session;
    /**
     * 通常（ログイン・マイページ）ルート.
     */
    protected $default_routes;
    /**
     * 重要操作ルート.
     */
    protected $include_routes;

    /**
     * @param Context $requestContext
     * @param UrlGeneratorInterface $router
     * @param CustomerTwoFactorAuthService $customerTwoFactorAuthService
     * @param TwoFactorAuthTypeRepository $twoFactorAuthTypeRepository
     * @param TwoFactorAuthCustomerCookieRepository $twoFactorAuthCustomerCookieRepository
     * @param BaseInfoRepository $baseInfoRepository
     * @param SessionInterface $session
     */
    public function __construct(
        Context $requestContext,
        UrlGeneratorInterface $router,
        CustomerTwoFactorAuthService $customerTwoFactorAuthService,
        TwoFactorAuthTypeRepository $twoFactorAuthTypeRepository,
        TwoFactorAuthCustomerCookieRepository $twoFactorAuthCustomerCookieRepository,
        BaseInfoRepository $baseInfoRepository,
        SessionInterface $session
    ) {
        $this->requestContext = $requestContext;
        $this->router = $router;
        $this->customerTwoFactorAuthService = $customerTwoFactorAuthService;
        $this->baseInfo = $baseInfoRepository->find(1);
        $this->twoFactorAuthTypeRepository = $twoFactorAuthTypeRepository;
        $this->twoFactorAuthCustomerCookieRepository = $twoFactorAuthCustomerCookieRepository;
        $this->session = $session;

        $this->default_routes = $this->customerTwoFactorAuthService->getDefaultAuthRoutes();
        $this->include_routes = $this->customerTwoFactorAuthService->getIncludeRoutes();
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => ['onKernelController', 7],
            LoginSuccessEvent::class => ['onLoginSuccess'],
            LogoutEvent::class => 'logoutEvent',
        ];
    }

    /**
     * リクエスト受信時イベントハンドラ.
     *
     * @param ControllerArgumentsEvent $event
     */
    public function onKernelController(ControllerArgumentsEvent $event)
    {
        if (!$event->isMainRequest()) {
            // サブリクエストの場合、処理なし
            return;
        }

        if ($this->requestContext->isAdmin()) {
            // バックエンドURLの場合、処理なし
            return;
        }

        if (!$this->baseInfo->isTwoFactorAuthUse()) {
            // 2段階認証使用しない場合は処理なし
            return;
        }

        $route = $event->getRequest()->attributes->get('_route');
        $uri = $event->getRequest()->getRequestUri();

        $Customer = $this->requestContext->getCurrentUser();

        if ($Customer instanceof Customer) {
            if ($Customer->getStatus()->getId() !== CustomerStatus::REGULAR) {
                // ログインしていない場合、処理なし
                return;
            }

            if (!$this->isDefaultRoute($route, $uri) && !$this->isIncludeRoute($route, $uri)) {
                // 重要操作指定ではなく、マイページ系列ではない場合、処理なし
                return;
            }

            $this->multiFactorAuth($event, $Customer, $route);
        }
    }

    /**
     * ログイン完了 イベントハンドラ.
     *
     * @param LoginSuccessEvent $event
     *
     * @return RedirectResponse|void
     */
    public function onLoginSuccess(LoginSuccessEvent $event)
    {
        if ($this->requestContext->isAdmin()) {
            // バックエンドURLの場合処理なし
            return;
        }

        if (!$this->baseInfo->isTwoFactorAuthUse()) {
            // 2段階認証使用しない場合は処理なし
            return;
        }

        if ($this->requestContext->getCurrentUser() === null) {
            // ログインしていない場合は処理なし
            return;
        }

        if ($this->requestContext->getCurrentUser()->getTwoFactorAuthType() !== null &&
            $this->requestContext->getCurrentUser()->getTwoFactorAuthType()->isDisabled()) {
            // ユーザーが選択した２段階認証方式は無効になっている場合、ログアウトさせる。
            return new RedirectResponse($this->router->generate('logout'), 302);
        }

        $this->multiFactorAuth(
            $event,
            $this->requestContext->getCurrentUser(),
            $event->getRequest()->attributes->get('_route'));
    }

    /**
     * ログアウトする前に全ての2FA認証クッキーを消す
     *
     * @param LogoutEvent $logoutEvent ログアウトイベント
     *
     * @return void
     */
    public function logoutEvent(LogoutEvent $logoutEvent)
    {
        $this->customerTwoFactorAuthService->clear2AuthCookies($logoutEvent->getRequest(), $logoutEvent->getResponse());
        $Customer = $this->requestContext->getCurrentUser();
        if ($Customer !== null) {
            $this->twoFactorAuthCustomerCookieRepository->deleteByCustomer($Customer);
        }
    }


    /**
     * ルート・URIが個別認証対象かチェック.
     *
     * @param string $route
     * @param string $uri
     *
     * @return bool
     */
    private function isDefaultRoute(string $route, string $uri): bool
    {
        return $this->isTargetRoute($this->default_routes, $route, $uri);
    }

    /**
     * ルート・URIが対象であるかチェック.
     *
     * @param array $targetRoutes
     * @param string $route
     * @param string $uri
     *
     * @return bool
     */
    private function isTargetRoute(array $targetRoutes, string $route, string $uri): bool
    {
        // ルートで認証
        if (in_array($route, $targetRoutes)) {
            return true;
        }

        // URIで認証
        foreach ($targetRoutes as $r) {
            if ($r != '' && $r !== '/' && strpos($uri, $r) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * ルート・URIが個別認証対象かチェック.
     *
     * @param string $route
     * @param string $uri
     *
     * @return bool
     */
    private function isIncludeRoute(string $route, string $uri): bool
    {
        return $this->isTargetRoute($this->include_routes, $route, $uri);
    }

    /**
     * 多要素認証.
     *
     * @param Event $event
     * @param Customer $Customer
     * @param string $route
     *
     * @return mixed
     */
    private function multiFactorAuth($event, $Customer, $route)
    {
        if (!$this->baseInfo->isTwoFactorAuthUse()) {
            // MFA無効の場合処理なし
            return;
        }

        if (count($this->twoFactorAuthTypeRepository->findBy(['isDisabled' => false])) == 0) {
            // 2段階認証プラグインが有効化されていない場合処理なし
            return;
        }

        // [会員] ログイン時2段階認証状態
        $is_auth = $this->customerTwoFactorAuthService->isAuthed($Customer, $route);

        if (!$is_auth) {
            log_info('[2段階認証] 実施');
            if ($Customer->getTwoFactorAuthType() === null) {
                // 2段階認証未設定
                $this->selectAuthType($event, $route);
            } else {
                // 2段階認証設定済み
                $this->auth($event, $Customer, $route);
            }
        }
    }

    /**
     * 多要素認証方式設定画面へリダイレクト.
     *
     * @param Event $event
     * @param string|null $route
     */
    private function selectAuthType($event, ?string $route)
    {
        // [会員] 2段階認証が未設定の場合
        // コールバックURLをセッションへ設定
        $this->setCallbackRoute($route);
        // 2段階認証選択画面へリダイレクト
        $url = $this->router->generate('plg_customer_2fa_auth_type_select', [], UrlGeneratorInterface::ABSOLUTE_PATH);

        if ($event instanceof ControllerArgumentsEvent) {
            $event->setController(function () use ($url) {
                return new RedirectResponse($url, 302);
            });
        } else {
            $event->setResponse(new RedirectResponse($url, 302));
        }
    }

    /**
     * コールバックルートをセッションへ設定.
     *
     * @param string|null $route
     */
    private function setCallbackRoute(?string $route)
    {
        if ($route) {
            $this->session->set(CustomerTwoFactorAuthService::SESSION_CALL_BACK_URL, $route);
        }
    }

    /**
     * 2段階認証のディスパッチ.
     *
     * @param Event $event
     * @param Customer $Customer
     * @param string|null $route
     */
    private function auth($event, Customer $Customer, ?string $route)
    {
        // コールバックURLをセッションへ設定
        $this->setCallbackRoute($route);
        // 選択された多要素認証方式で指定されているルートへリダイレクト
        if ($Customer->getTwoFactorAuthType() !== null && $Customer->getTwoFactorAuthType()->isDisabled()) {
            // ユーザーが選択した２段階認証方式は無効になっている場合、ログアウトさせる。
            $event->setController(function () {
                return new RedirectResponse($this->router->generate('logout'), 302);
            });

            return;
        }

        $url = $this->router->generate($Customer->getTwoFactorAuthType()->getRoute());

        if ($event instanceof ControllerArgumentsEvent) {
            $event->setController(function () use ($url) {
                return new RedirectResponse($url, 302);
            });
        } else {
            $event->setResponse(new RedirectResponse($url, 302));
        }
    }

}
