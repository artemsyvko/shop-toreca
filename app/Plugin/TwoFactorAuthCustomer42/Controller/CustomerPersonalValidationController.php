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

namespace Plugin\TwoFactorAuthCustomer42\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Customer;
use Eccube\Repository\CustomerRepository;
use Plugin\TwoFactorAuthCustomer42\Form\Type\TwoFactorAuthPhoneNumberTypeCustomer;
use Plugin\TwoFactorAuthCustomer42\Form\Type\TwoFactorAuthSmsTypeCustomer;
use Plugin\TwoFactorAuthCustomer42\Service\CustomerTwoFactorAuthService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Api\V2010\Account\MessageInstance;

class CustomerPersonalValidationController extends AbstractController
{
    /**
     * @var CustomerRepository
     */
    protected CustomerRepository $customerRepository;

    /**
     * @var CustomerTwoFactorAuthService
     */
    protected CustomerTwoFactorAuthService $customerTwoFactorAuthService;

    /**
     * @var Environment
     */
    protected Environment $twig;
    private RateLimiterFactory $deviceAuthRequestEmailLimiter;

    /**
     * TwoFactorAuthCustomerController constructor.
     *
     * @param CustomerRepository $customerRepository ,
     * @param CustomerTwoFactorAuthService $customerTwoFactorAuthService ,
     * @param Environment $twig
     */
    public function __construct(
        RateLimiterFactory $deviceAuthRequestEmailLimiter,
        CustomerRepository $customerRepository,
        CustomerTwoFactorAuthService $customerTwoFactorAuthService,
        Environment $twig
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerTwoFactorAuthService = $customerTwoFactorAuthService;
        $this->twig = $twig;
        $this->deviceAuthRequestEmailLimiter = $deviceAuthRequestEmailLimiter;
    }

    /**
     * (デバイス認証時)デバイス認証ワンタイムトークン入力画面.
     *
     * @Route("/two_factor_auth/device_auth/input_onetime/{secret_key}", name="plg_customer_2fa_device_auth_input_onetime", requirements={"secret_key" = "^[a-zA-Z0-9]+$"}, methods={"GET", "POST"})
     * @Template("TwoFactorAuthCustomer42/Resource/template/default/device_auth/input.twig")
     *
     * @param Request $request
     * @param $secret_key
     *
     * @return array|RedirectResponse
     */
    public function deviceAuthInputOneTime(Request $request, $secret_key)
    {
        if ($this->isGranted('ROLE_USER')) {
            // 認証済みならばマイページへ
            return $this->redirectToRoute('mypage');
        }

        $error = null;
        /** @var Customer $Customer */
        $Customer = $this->customerRepository->getProvisionalCustomerBySecretKey($secret_key);

        if ($Customer === null) {
            throw $this->createNotFoundException();
        }

        $builder = $this->formFactory->createBuilder(TwoFactorAuthSmsTypeCustomer::class);
        // 入力フォーム生成
        $form = $builder->getForm();
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // メールでスロットリングをかける
                $limiter = $this->deviceAuthRequestEmailLimiter->create($Customer->getEmail());
                if (!$limiter->consume()->isAccepted()) {
                    throw new TooManyRequestsHttpException();
                }

                $token = $form->get('one_time_token')->getData();
                if (!$this->checkDeviceToken($Customer, $token)) {
                    // ワンタイムトークン不一致 or 有効期限切れ
                    $error = trans('front.2fa.onetime.invalid_message__reinput');
                } else {
                    // ワンタイムトークン一致
                    // 送信電話番号をセッションより取得
                    $phoneNumber = $this->session->get(CustomerTwoFactorAuthService::SESSION_AUTHED_PHONE_NUMBER);
                    // 認証済みの電話番号でないかチェック
                    if ($this->customerRepository->count(['device_authed_phone_number' => $phoneNumber]) === 0) {
                        // 未認証であれば登録
                        $Customer->setDeviceAuthed(true);
                        $Customer->setDeviceAuthedPhoneNumber($phoneNumber);
                        $Customer->setDeviceAuthOneTimeToken(null);
                        $Customer->setDeviceAuthOneTimeTokenExpire(null);
                        $this->entityManager->persist($Customer);
                        $this->entityManager->flush();
                        $this->session->remove(CustomerTwoFactorAuthService::SESSION_AUTHED_PHONE_NUMBER);

                        // アクティベーション実行
                        return $this->redirectToRoute(
                            'entry_activate',
                            ['secret_key' => $secret_key]
                        );
                    } else {
                        // 認証済の場合はスキップ
                        log_warning('[デバイス認証(SMS)] 既に認証済みの電話番号指定');
                        $error = trans('front.2fa.onetime.invalid_message__reinput');
                    }
                }
            } else {
                $error = trans('front.2fa.onetime.invalid_message__reinput');
            }
        }

        return [
            'form' => $form->createView(),
            'secret_key' => $secret_key,
            'Customer' => $Customer,
            'error' => $error,
        ];
    }

    /**
     * (デバイス認証時)デバイス認証 送信先入力画面.
     *
     * @Route("/two_factor_auth/device_auth/send_onetime/{secret_key}", name="plg_customer_2fa_device_auth_send_onetime", requirements={"secret_key" = "^[a-zA-Z0-9]+$"}, methods={"GET", "POST"})
     * @Template("TwoFactorAuthCustomer42/Resource/template/default/device_auth/send.twig")
     *
     * @param Request $request
     * @param $secret_key
     *
     * @return array|RedirectResponse
     *
     * @throws ConfigurationException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws TwilioException
     */
    public function deviceAuthSendOneTime(Request $request, $secret_key)
    {
        if ($this->isGranted('ROLE_USER')) {
            // 認証済みならばマイページへ
            return $this->redirectToRoute('mypage');
        }

        $error = null;
        /** @var Customer $Customer */
        $Customer = $this->customerRepository->getProvisionalCustomerBySecretKey($secret_key);
        if ($Customer === null) {
            throw $this->createNotFoundException();
        }
        $builder = $this->formFactory->createBuilder(TwoFactorAuthPhoneNumberTypeCustomer::class);
        // 入力フォーム生成
        $form = $builder->getForm();
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // メールでスロットリングをかける
                $limiter = $this->deviceAuthRequestEmailLimiter->create($Customer->getEmail());
                if (!$limiter->consume()->isAccepted()) {
                    throw new TooManyRequestsHttpException();
                }
                // 認証済みの電話番号でないかチェック
                $phoneNumber = $form->get('phone_number')->getData();
                if ($this->customerRepository->count(['device_authed_phone_number' => $phoneNumber]) === 0) {
                    // 未認証の場合、入力電話番号へワンタイムコードを送信
                    $this->sendDeviceToken($Customer, $phoneNumber);
                    // 送信電話番号をセッションへ一時格納
                    $this->session->set(
                        CustomerTwoFactorAuthService::SESSION_AUTHED_PHONE_NUMBER,
                        $phoneNumber
                    );
                } else {
                    // 認証済の場合はスキップ
                    log_warning('[デバイス認証(SMS)] 既に認証済みの電話番号指定');
                }

                return $this->redirectToRoute(
                    'plg_customer_2fa_device_auth_input_onetime',
                    ['secret_key' => $secret_key]
                );
            } else {
                $error = trans('front.2fa.sms.send.failure_message');
            }
        }

        return [
            'form' => $form->createView(),
            'secret_key' => $secret_key,
            'Customer' => $Customer,
            'error' => $error,
        ];
    }

    /**
     * デバイス認証用のワンタイムトークンチェック.
     *
     * @param $Customer
     * @param $token
     *
     * @return boolean
     */
    private function checkDeviceToken($Customer, $token): bool
    {
        $now = new \DateTime();

        // フォームからのハッシュしたワンタイムパスワードとDBに保存しているワンタイムパスワードのハッシュは一致しているかどうか
        if (
            $Customer->getDeviceAuthOneTimeToken() !== $this->customerTwoFactorAuthService->hashOneTimeToken($token) ||
            $Customer->getDeviceAuthOneTimeTokenExpire() < $now) {
            return false;
        }

        return true;
    }

    /**
     * デバイス認証用のワンタイムトークンを送信.
     *
     * @param Customer $Customer
     * @param string $phoneNumber
     *
     * @return MessageInstance
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws ConfigurationException
     * @throws TwilioException
     * @throws \Exception
     */
    private function sendDeviceToken(Customer $Customer, string $phoneNumber)
    {
        // ワンタイムトークン生成・保存
        $token = $this->customerTwoFactorAuthService->generateOneTimeTokenValue();

        $Customer->setDeviceAuthOneTimeToken($this->customerTwoFactorAuthService->hashOneTimeToken($token));
        $Customer->setDeviceAuthOneTimeTokenExpire($this->customerTwoFactorAuthService->generateExpiryDate());

        $this->entityManager->persist($Customer);
        $this->entityManager->flush();

        // ワンタイムトークン送信メッセージをレンダリング
        $twig = 'TwoFactorAuthCustomer42/Resource/template/default/sms/onetime_message.twig';
        $body = $this->twig->render($twig, [
            'Customer' => $Customer,
            'token' => $token,
        ]);

        // SMS送信
        return $this->customerTwoFactorAuthService->sendBySms($phoneNumber, $body);
    }
}
