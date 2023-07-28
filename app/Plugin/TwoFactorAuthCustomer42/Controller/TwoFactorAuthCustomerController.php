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
use Plugin\TwoFactorAuthCustomer42\Form\Type\TwoFactorAuthTypeCustomer;
use Plugin\TwoFactorAuthCustomer42\Service\CustomerTwoFactorAuthService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class TwoFactorAuthCustomerController extends AbstractController
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

    /**
     * TwoFactorAuthCustomerController constructor.
     *
     * @param CustomerRepository $customerRepository ,
     * @param CustomerTwoFactorAuthService $customerTwoFactorAuthService ,
     * @param Environment $twig
     */
    public function __construct(
        CustomerRepository $customerRepository,
        CustomerTwoFactorAuthService $customerTwoFactorAuthService,
        Environment $twig
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerTwoFactorAuthService = $customerTwoFactorAuthService;
        $this->twig = $twig;
    }

    /**
     * (ログイン時)二段階認証設定（選択）画面.
     *
     * @Route("/mypage/two_factor_auth/select_type", name="plg_customer_2fa_auth_type_select", methods={"GET", "POST"})
     * @Template("TwoFactorAuthCustomer42/Resource/template/default/tfa/select_type.twig")
     */
    public function selectAuthType(Request $request)
    {
        if ($this->isTwoFactorAuthed()) {
            return $this->redirectToRoute($this->getCallbackRoute());
        }

        /** @var Customer $Customer */
        $Customer = $this->getUser();

        // 2段階認証方式が選択されている場合は、その方式の初回認証画面へ遷移
        if ($Customer !== null && $Customer->getTwoFactorAuthType() !== null) {
            return $this->redirectToRoute($Customer->getTwoFactorAuthType()->getRoute());
        }

        $error = null;
        $builder = $this->formFactory->createBuilder(TwoFactorAuthTypeCustomer::class);
        // 入力フォーム生成
        $form = $builder->getForm();
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // 選択された2段階認証方式を更新
                $TwoFactorAuthType = $form->get('two_factor_auth_type')->getData();
                $Customer->setTwoFactorAuthType($TwoFactorAuthType);
                // 2段階認証を有効に更新
                $this->entityManager->persist($Customer);
                $this->entityManager->flush();
                // 初回認証を実施
                return $this->redirectToRoute($TwoFactorAuthType->getRoute());
            } else {
                $error = trans('front.2fa.onetime.invalid_message__reinput');
            }
        }

        return [
            'form' => $form->createView(),
            'Customer' => $Customer,
            'error' => $error,
        ];
    }

    /**
     * 認証済みか否か.
     *
     * @return boolean
     */
    protected function isTwoFactorAuthed(): bool
    {
        /** @var Customer $Customer */
        $Customer = $this->getUser();
        if ($Customer != null && !$this->customerTwoFactorAuthService->isAuthed($Customer, $this->getCallbackRoute())) {
            return false;
        }

        return true;
    }

    /**
     * コールバックルートの取得.
     *
     * @return string
     */
    protected function getCallbackRoute(): string
    {
        $route = $this->session->get(CustomerTwoFactorAuthService::SESSION_CALL_BACK_URL);

        return ($route != null) ? $route : 'mypage';
    }
}
