<?php

namespace Plugin\SlnPayment42\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Entity\Order;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Exception\ShoppingException;
use Eccube\Event\EventArgs;
use Eccube\Common\Constant;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Plugin\SlnPayment42\Repository\MemCardIdRepository;
use Plugin\SlnPayment42\Repository\OrderPaymentStatusRepository;
use Plugin\SlnPayment42\Repository\PluginConfigRepository;
use Plugin\SlnPayment42\Form\Type\CardType;
use Plugin\SlnPayment42\Service\SlnMailService;
use Plugin\SlnPayment42\Service\BasicItem;
use Plugin\SlnPayment42\Service\Util;
use Plugin\SlnPayment42\Service\Method\CreditCard;
use Plugin\SlnPayment42\Service\Method\RegisteredCreditCard;
use Plugin\SlnPayment42\Service\Method\MethodUtils;
use Plugin\SlnPayment42\Service\SlnAction\Cvs;
use Plugin\SlnPayment42\Service\SlnAction\Credit;
use Plugin\SlnPayment42\Service\SlnAction\Mem;
use Plugin\SlnPayment42\Exception\SlnShoppingException;


class PaymentController extends AbstractController
{

    /**
     * @var MemCardIdRepository
     */
    private $memCardIdRepository;

    /**
     * @var OrderPaymentStatusRepository
     */
    private $orderPaymentStatusRepository;

    /**
     * @var PluginConfigRepository
     */
    private $configRepository;

    /**
     * @var SlnMailService
     */
    private $slnMailService;

    /**
     * @var BasicItem
     */
    private $basicItem;

    /**
     * @var Util
     */
    private $util;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var MailService
     */
    private $mailService;

    /**
     * @var PurchaseFlow;
     */
    private $purchaseFlow;

    /**
     * @var string 受注IDキー
     */
    private $sessionOrderKey = 'eccube.front.shopping.order.id';

    /**
     * @var string 3D決済場合、支払い回数を記録する
     */
    private $sessionPayType = 'sln.eccube.front.shopping.paytype';


    public function __construct(
        MemCardIdRepository $memCardIdRepository,
        PluginConfigRepository $configRepository,
        SlnMailService $slnMailService,
        BasicItem $basicItem,
        Util $util,
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository,
        OrderPaymentStatusRepository $orderPaymentStatusRepository,
        CartService $cartService,
        MailService $mailService,
        PurchaseFlow $shoppingPurchaseFlow,
        Mem $mem,
        Credit $credit,
        Cvs $cvs
    ) {
        $this->memCardIdRepository = $memCardIdRepository;
        $this->configRepository = $configRepository;
        $this->slnMailService = $slnMailService;
        $this->basicItem = $basicItem;
        $this->util = $util;
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->orderPaymentStatusRepository = $orderPaymentStatusRepository;
        $this->cartService = $cartService;
        $this->mailService = $mailService;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->mem = $mem;
        $this->credit = $credit;
        $this->cvs = $cvs;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\RedirectResponse|\Eccube\Entity\Order
     */
    protected function checkOrder()
    {
        $Order = null;
        $preOrderId = $this->cartService->getPreOrderId();
        if ($preOrderId) {
            $Order = $this->orderRepository->findOneBy(
                ['pre_order_id' => $preOrderId]
            );
        }

        if (is_null($Order)) {
            $error_title = 'エラー';
            $error_message = "注文情報の取得が出来ませんでした。この手続きは無効となりました。";
            return $this->render('error.twig', array('error_message' => $error_message, 'error_title' => $error_title));
        }

        return $Order;
    }

    /**
     * @Route("/shopping/sln_card_payment", name="sln_card_payment")
     * @Template("@SlnPayment42/sln_card_payment.twig")
     */
    public function cardIndex(Request $request)
    {
        $isReCard = false;

        $Order = $this->checkOrder();
        if (!($Order instanceof Order)) {
            $this->addError("受注情報は存在していません。");
            return $this->redirectToRoute('shopping_error');
        }

        $isUser = $Order->getCustomer();

        $config = $this->configRepository->getConfig();

        //クレジットカード支払い判断
        $methodClass = $Order->getPayment()->getMethodClass();
        if (!MethodUtils::isCreditCardMethod($methodClass)) {
            $this->addError("支払い方法を再度選択ください。");
            return $this->redirectToRoute('shopping_error');
        }

        $cardNo = "";
        $form = $this->createForm(CardType::class);

        if ($methodClass == RegisteredCreditCard::class) {
            $isReCard = true;
            $mem = $this->mem;

            //登録カード情報をとる
            try {
                //カード登録判断
                //登録済クレジットカード存在判断
                $ReMemRef = $mem->MemRef($Order->getCustomer(), $config);

                if ($ReMemRef->getContent()->getKaiinStatus() == 0) { //カード登録済
                    $cardNo = $ReMemRef->getContent()->getCardNo();
                } else {
                    $this->addError("登録済カード情報は存在していません。支払い方法を再度選択ください。");
                    return $this->redirectToRoute('shopping_error');
                }
            } catch (\Exception $e) {
                // echo "$methodClass == RegisteredCreditCard::class";
                log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
            }

            if (!$cardNo) {
                $this->addError("通信エラーが発生しました。支払い方法を再度選択ください。");
                return $this->redirectToRoute('shopping_error');
            }

            $form->remove('CardNo')
                ->remove('CardExpYear')
                ->remove('CardExpMonth')
                ->remove('KanaSei')
                ->remove('KanaMei')
                ->remove('BirthDay')
                ->remove('TelNo')
                ->remove('AddMem')
                ->remove('Token');
        }

        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {

                $card = $this->credit;
                $connection = $this->entityManager->getConnection();

                $flowResult = $this->purchaseFlow->validate($Order, new PurchaseContext($Order, $Order->getCustomer()));
                if ($flowResult->hasWarning() || $flowResult->hasError()) {
                    // 購入バリデーション失敗
                    $this->addError('購入手続きに失敗しました。商品を購入できない状態です。');
                    return $this->redirectToRoute('shopping_error');
                }

                try {
                    // 仮購入の状態にする
                    $this->purchaseFlow->prepare($Order, new PurchaseContext());
                    $this->entityManager->flush();

                    //決済状況を記録する
                    $this->orderPaymentStatusRepository->unsettled($Order);
                    if ($methodClass == RegisteredCreditCard::class) {
                        if ($config->getThreedPay() == 1) { //3D決済
                            $master = new \Plugin\SlnPayment42\Service\SlnContent\Credit\ThreeDMaster();
                        } else {
                            $master = new \Plugin\SlnPayment42\Service\SlnContent\Credit\Master();
                        }

                        if ($Order->getCustomer()) {
                            list($KaiinId, $KaiinPass) = $this->util->getNewKaiin($this->memCardIdRepository, $Order->getCustomer(), $this->eccubeConfig->get('eccube_auth_magic'));

                            // セキュリティコード削除暫定対応
                            // if ($config->getSecCd() == 1) {
                            //     $master->setSecCd($form->get('SecCd')->getData());
                            // }

                            $master->setKaiinId($KaiinId);
                            $master->setKaiinPass($KaiinPass);
                        } else {
                            $this->orderPaymentStatusRepository->fail($Order);

                            $this->addError("支払い方法を再度選択ください。");
                            return $this->redirectToRoute('shopping_error');
                        }
                    } else {
                        $master = new \Plugin\SlnPayment42\Service\SlnContent\Credit\ThreeDMaster();

                        $AddMem = $form->get('AddMem')->getData();

                        $event = new EventArgs(
                            array(
                                'AddMem' => $AddMem,
                                'Order' => $Order,
                            ),
                            $request
                        );
                        $this->eventDispatcher->dispatch($event, 'sln.payment.shopping.add_mem');
                        $AddMem = $event->getArgument('AddMem');

                        if (count($AddMem) && $AddMem[0] == 1 && $Order->getCustomer()) {
                            if ($form->get('Token')->getData()) {
                                //トークン決済
                                $member = new \Plugin\SlnPayment42\Service\SlnContent\Credit\Member();
                                $member->setToken($form->get('Token')->getData());

                                $mem = $this->mem;
                                $this->util->changeMemCard($mem, $this->configRepository, $Order->getCustomer(), $member);

                                //会員決済
                                list($KaiinId, $KaiinPass) = $this->util->getNewKaiin($this->memCardIdRepository, $Order->getCustomer(), $this->eccubeConfig->get('eccube_auth_magic'));
                                $master->setKaiinId($KaiinId);
                                $master->setKaiinPass($KaiinPass);
                            }
                        } else {
                            if ($config->getThreedPay() == 1) {
                                //3D決済を行う
                                $data = $form->getData();
                                if (!empty($data['CardNo'])) {
                                    $master->setCardNo($data['CardNo']);

                                    $master->setCardExp(substr($data['CardExpYear'], 2) . sprintf("%02d", $data['CardExpMonth']));
                                    if ($config->getSecCd() == 1) {
                                        $master->setSecCd($data['SecCd']);
                                    }

                                    $attAss = $config->getAttestationAssistance();
                                    if (in_array('KanaSei', $attAss)) {
                                        $master->setKanaSei($data['KanaSei']);
                                        $master->setKanaMei($data['KanaMei']);
                                    }

                                    if (in_array('BirthDay', $attAss)) {
                                        $master->setBirthDay($data['BirthDay']);
                                    }

                                    if (in_array('TelNo', $attAss)) {
                                        $master->setTelNo($data['TelNo']);
                                    }
                                } else {
                                    $master->setToken($form->get('Token')->getData());
                                }
                            } else if ($form->get('Token')->getData()) {
                                //トークン決済
                                $master->setToken($form->get('Token')->getData());
                            }
                        }
                    }

                    $master->setPayType($form->get('PayType')->getData());

                    if ($config->getThreedPay() == 1) {
                        // 3D決済処理

                        //強制金額を記録する
                        $this->orderPaymentStatusRepository->setOrderStatus($Order, 0, $Order->getPaymentTotal());

                        if ($config->getOperateId() == "1Auth") {
                            $html = $card->ThreeDAuth($Order, $config, $master);
                        } else {
                            $html = $card->ThreeDGathering($Order, $config, $master);
                        }

                        return new Response($html);
                    } else {
                        if ($config->getOperateId() == "1Auth") {
                            $card->Auth($Order, $config, $master);
                            $this->orderPaymentStatusRepository->auth($Order, $master->getAmount());
                        } else {
                            $card->Gathering($Order, $config, $master);
                            $this->orderPaymentStatusRepository->capture($Order, $master->getAmount());
                        }
                    }

                    // トランザクション制御
                    $connection->beginTransaction();

                    // 購入処理
                    $Order->setOrderDate(new \DateTime());
                    $this->purchaseFlow->commit($Order, new PurchaseContext());

                    if ($config->getCardOrderPreEnd() == 2 && $config->getOperateId() == "1Gathering") {
                        $this->orderRepository->changeStatus($Order->getId(), $this->orderStatusRepository->find(OrderStatus::PAID));
                    } else {
                        $this->orderRepository->changeStatus($Order->getId(), $this->orderStatusRepository->find(OrderStatus::NEW));
                    }

                    $connection->commit();
                } catch (SlnShoppingException $e) {
                    $connection->rollBack();

                    log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
                    $this->addWarning($e->getMessage(), 'sln_payment');

                    $log = sprintf("card shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
                    $this->util->addCardNotice($log);

                    if ($e->checkSystemError()) {
                        $this->util->addErrorLog($e->getSlnErrorName() . $e->getSlnErrorDetail() . 'order_id:' . $Order->getId());
                        $this->slnMailService->sendErrorMail($e->getSlnErrorName() . $e->getSlnErrorDetail());
                    }

                    $this->orderPaymentStatusRepository->fail($Order);

                    return $this->redirectToRoute('sln_card_payment');
                } catch (ShoppingException $e) {
                    $connection->rollBack();

                    log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
                    $this->addError($e->getMessage());

                    $log = sprintf("card shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
                    $this->util->addCardNotice($log);
                    $this->util->addErrorLog($log);

                    $this->slnMailService->sendErrorMail("在庫不足エラーが発生した可能性があるため、ログのご確認の上、払戻処理をお願いいたします。");

                    $this->orderPaymentStatusRepository->fail($Order);

                    return $this->redirectToRoute('shopping_error');
                } catch (\Exception $e) {
                    $connection->rollBack();

                    log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
                    $this->addError('front.shopping.system_error');

                    $log = sprintf("card shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
                    $this->util->addCardNotice($log);
                    $this->util->addErrorLog($log);

                    $this->slnMailService->sendErrorMail("購入時にシステムエラーが発生しました。ログのご確認の上、払戻処理をお願いいたします。");

                    $this->orderPaymentStatusRepository->fail($Order);

                    return $this->redirectToRoute('shopping_error');
                }

                // カート削除
                $this->cartService->clear()->save();

                $event = new EventArgs(
                    array(
                        'Order' => $Order,
                    ),
                    $request
                );
                $this->eventDispatcher->dispatch($event, 'sln.front.shopping.confirm.processing');

                if ($event->getResponse() !== null) {
                    return $event->getResponse();
                }

                // 受注IDをセッションにセット
                $request->getSession()->set($this->sessionOrderKey, $Order->getId());

                // メール送信
                $mail = $this->mailService->sendOrderMail($Order);
                $this->entityManager->flush();

                // 完了画面表示
                return $this->redirectToRoute('shopping_complete');
            } else {
                $this->addWarning('入力項目をご確認ください。', 'sln_payment');
            }
        }

        $IsAddMemView = true;

        $event = new EventArgs(
            array(
                'IsAddMemView' => $IsAddMemView,
                'Order' => $Order,
            ),
            $request
        );
        $this->eventDispatcher->dispatch($event, 'sln.payment.shopping.add_mem_view');

        return [
            'TokenJsUrl' => $config->getCreditConnectionPlace6(),
            'TokenNinsyoCode' => $config->getTokenNinsyoCode(),
            'form' => $form->createView(),
            'config' => $config,
            'Order' => $Order,
            'IsReCard' => $isReCard,
            'IsUser' => $isUser,
            'IsAddMemView' => $event->getArgument('IsAddMemView'),
            'cardNo' => $cardNo,
            'Is3DPay' => $config->getThreedPay() == 1,
        ];
    }

    /**
     * @Route("/sln_payment/sln_3d_card", name="sln_3d_card")
     */
    public function threeCard(Request $request)
    {
        $config = $this->configRepository->getConfig();
        $connection = $this->entityManager->getConnection();

        try {
            $EncryptValue = $_POST['EncryptValue'];
            if (!strlen($EncryptValue)) {
                $this->addError("受注情報は存在していません。");
                return $this->redirectToRoute('shopping_error');
            }

            $Order = $this->checkOrder();
            if (!($Order instanceof \Eccube\Entity\Order)) {
                $this->addError("受注情報は存在していません。");
                return $this->redirectToRoute('shopping_error');
            }
            $statusId = $Order->getOrderStatus()->getId();

            $card = $this->credit;
            $ReThreeDAuth = $card->DeCodeThreeDResponse($EncryptValue, $Order->getId(), "threeCard");
            if ($ReThreeDAuth->getContent()->getMerchantFree1() != $Order->getId()) {
                $this->addError("受注情報は存在していません。");
                return $this->redirectToRoute('shopping_error');
            }

            /* @var $paymentStatus \Plugin\SlnPayment42\Entity\OrderPaymentStatus */
            $paymentStatus = $this->orderPaymentStatusRepository->getStatus($Order);

            if ($ReThreeDAuth->getContent()->getOperateId() == "1Auth") {
                $this->orderPaymentStatusRepository->auth($Order, $paymentStatus->getAmount());
            } else {
                $this->orderPaymentStatusRepository->capture($Order, $paymentStatus->getAmount());
            }

            $connection->beginTransaction();

            // 購入処理
            $Order->setOrderDate(new \DateTime());
            $this->purchaseFlow->commit($Order, new PurchaseContext());

            if ($config->getCardOrderPreEnd() == 2 && $config->getOperateId() == "1Gathering") {
                $this->orderRepository->changeStatus($Order->getId(), $this->orderStatusRepository->find(OrderStatus::PAID));
            } else {
                $this->orderRepository->changeStatus($Order->getId(), $this->orderStatusRepository->find(OrderStatus::NEW));
            }

            $connection->commit();
        } catch (SlnShoppingException $e) {
            $connection->rollBack();

            // 仮購入解除
            $this->purchaseFlow->rollback($Order, new PurchaseContext());
            $this->entityManager->flush();

            log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
            $this->addWarning($e->getMessage(), 'sln_payment');

            $log = sprintf("card shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
            $this->util->addCardNotice($log);

            if ($e->checkSystemError()) {
                $this->util->addErrorLog($e->getSlnErrorName() . $e->getSlnErrorDetail() . 'order_id:' . $Order->getId())  . " " . $e->getFile() . $e->getLine();
                $this->slnMailService->sendErrorMail($e->getSlnErrorName() . $e->getSlnErrorDetail());
            }

            $this->orderPaymentStatusRepository->fail($Order);

            return $this->redirectToRoute('sln_card_payment');
        } catch (ShoppingException $e) {
            $connection->rollBack();

            // 仮購入解除
            $this->purchaseFlow->rollback($Order, new PurchaseContext());
            $this->entityManager->flush();

            log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
            $this->addError($e->getMessage());

            $log = sprintf("card shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
            $this->util->addCardNotice($log);
            $this->util->addErrorLog($log);

            $this->slnMailService->sendErrorMail("在庫不足エラーが発生した可能性があるため、ログのご確認の上、払戻処理をお願いいたします。");

            $this->orderPaymentStatusRepository->fail($Order);

            return $this->redirectToRoute('shopping_error');
        } catch (\Exception $e) {
            $connection->rollBack();

            // 仮購入解除
            $this->purchaseFlow->rollback($Order, new PurchaseContext());
            $this->entityManager->flush();

            log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
            $this->addError('front.shopping.system_error');

            $log = sprintf("card shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
            $this->util->addCardNotice($log);
            $this->util->addErrorLog($log);

            $this->slnMailService->sendErrorMail("購入時にシステムエラーが発生しました。ログのご確認の上、払戻処理をお願いいたします。");

            $this->orderPaymentStatusRepository->fail($Order);

            return $this->redirectToRoute('shopping_error');
        }

        // カート削除
        $this->cartService->clear()->save();

        // 受注IDをセッションにセット
        $request->getSession()->set($this->sessionOrderKey, $Order->getId());

        // メール送信
        // 二重送信防止のため、すでに購入処理後のステータスになっている受注には処理を行わない
        if (
            $statusId != OrderStatus::NEW //新規受付
            && $statusId != OrderStatus::PAID //入金済み
        ) {
            $event = new EventArgs(
                array(
                    'Order' => $Order,
                ),
                $request
            );
            $this->eventDispatcher->dispatch($event, 'sln.front.shopping.confirm.processing');

            if ($event->getResponse() !== null) {
                return $event->getResponse();
            }

            $this->mailService->sendOrderMail($Order);
            $this->entityManager->flush();
        }

        // 完了画面表示
        return $this->redirectToRoute('shopping_complete');
    }

    /**
     * @Route("/sln_payment/sln_3d_mem", name="sln_3d_mem_card")
     */
    public function threeMemCard(Request $request)
    {
        $EncryptValue = $_POST['EncryptValue'];
        if (!strlen($EncryptValue)) {
            $this->addError("受注情報は存在していません。");
            return $this->redirectToRoute('shopping_error');
        }

        $Order = $this->checkOrder();
        if (!($Order instanceof \Eccube\Entity\Order)) {
            $this->addError("受注情報は存在していません。");
            return $this->redirectToRoute('shopping_error');
        }

        $mem = $this->mem;
        $ReThreeDAuth = $mem->DeCodeThreeDResponse($EncryptValue, "threeMemCard");

        if ($ReThreeDAuth->getContent()->getMerchantFree1() != $Order->getId()) {
            $this->addError("受注情報は存在していません。");
            return $this->redirectToRoute('shopping_error');
        }

        /* @var $paymentStatus \Plugin\SlnPayment42\Entity\OrderPaymentStatus */
        $paymentStatus = $this->orderPaymentStatusRepository->getStatus($Order);

        $master = new \Plugin\SlnPayment42\Service\SlnContent\Credit\Master();
        list($KaiinId, $KaiinPass) = $this->util->getNewKaiin($this->memCardIdRepository, $Order->getCustomer(), $this->eccubeConfig->get('eccube_auth_magic'));
        $master->setKaiinId($KaiinId);
        $master->setKaiinPass($KaiinPass);

        $master->setPayType($request->getSession()->get($this->sessionPayType));
        $request->getSession()->remove($this->sessionPayType);

        $card = $this->credit;

        if ($config->getOperateId() == "1Auth") {
            $card->Auth($Order, $this->configRepository->getConfig(), $master);
            $this->orderPaymentStatusRepository->auth($Order, $master->getAmount());
        } else {
            $card->Gathering($Order, $this->configRepository->getConfig(), $master);
            $this->orderPaymentStatusRepository->capture($Order, $paymentStatus->getAmount());
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            // 購入処理
            $Order->setOrderDate(new \DateTime());
            $this->purchaseFlow->commit($Order, new PurchaseContext());

            if ($config->getCardOrderPreEnd() == 2 && $config->getOperateId() == "1Gathering") {
                $this->orderRepository->changeStatus($Order->getId(), $this->orderStatusRepository->find(OrderStatus::PAID));
            } else {
                $this->orderRepository->changeStatus($Order->getId(), $this->orderStatusRepository->find(OrderStatus::NEW));
            }

            $connection->commit();
        } catch (SlnShoppingException $e) {
            $connection->rollBack();

            // 仮購入解除
            $this->purchaseFlow->rollback($Order, new PurchaseContext());
            $this->entityManager->flush();

            log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
            $this->addWarning($e->getMessage(), 'sln_payment');

            if ($Order->getCustomer()->getId()) {
                try {
                    $this->util->delMemCard($Order->getCustomer());
                } catch (\Exception $e) {
                    log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
                }
            }

            $log = sprintf("card shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
            $this->util->addCardNotice($log);

            if ($e->checkSystemError()) {
                $this->util->addErrorLog($e->getSlnErrorName() . $e->getSlnErrorDetail() . 'order_id:' . $Order->getId())  . " " . $e->getFile() . $e->getLine();
                $this->slnMailService->sendErrorMail($e->getSlnErrorName() . $e->getSlnErrorDetail());
            }

            $this->orderPaymentStatusRepository->fail($Order);

            return $this->redirectToRoute('sln_card_payment');
        } catch (ShoppingException $e) {
            $connection->rollBack();

            // 仮購入解除
            $this->purchaseFlow->rollback($Order, new PurchaseContext());
            $this->entityManager->flush();

            log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
            $this->addError($e->getMessage());

            $log = sprintf("card shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
            $this->util->addCardNotice($log);
            $this->util->addErrorLog($log);

            $this->slnMailService->sendErrorMail("在庫不足エラーが発生した可能性があるため、ログのご確認の上、払戻処理をお願いいたします。");

            $this->orderPaymentStatusRepository->fail($Order);

            return $this->redirectToRoute('shopping_error');
        } catch (\Exception $e) {
            $connection->rollBack();

            // 仮購入解除
            $this->purchaseFlow->rollback($Order, new PurchaseContext());
            $this->entityManager->flush();

            log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
            $this->addError('front.shopping.system_error');

            $log = sprintf("card shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
            $this->util->addCardNotice($log);
            $this->util->addErrorLog($log);

            $this->slnMailService->sendErrorMail("購入時にシステムエラーが発生しました。ログのご確認の上、払戻処理をお願いいたします。");

            $this->orderPaymentStatusRepository->fail($Order);

            return $this->redirectToRoute('shopping_error');
        }

        // カート削除
        $this->cartService->clear()->save();

        $event = new EventArgs(
            array(
                'Order' => $Order,
            ),
            $request
        );
        $this->eventDispatcher->dispatch($event, 'sln.front.shopping.confirm.processing');

        if ($event->getResponse() !== null) {
            return $event->getResponse();
        }

        // 受注IDをセッションにセット
        $request->getSession()->set($this->sessionOrderKey, $Order->getId());

        // メール送信
        $this->mailService->sendOrderMail($Order);
        $this->entityManager->flush();

        // 完了画面表示
        return $this->redirectToRoute('shopping_complete');
    }

    /**
     * @Route("/sln_payment/sln_3d_card_post", name="sln_3d_card_post")
     * @Template("@SlnPayment42/sln_recv.twig")
     */
    public function threeCardPost(Request $request)
    {
        sleep(10); // threeCardと同時に処理されないように念のため時間をずらす
        $EncryptValue = $_POST['EncryptValue'];

        if (!strlen($EncryptValue)) {
            exit;
        }

        try {
            $card = $this->credit;
            $ReThreeDAuth = $card->DeCodeThreeDResponse($EncryptValue, null, "threeCardPost");

            /**
             * @var \Eccube\Entity\Order $Order
             */
            $Order = $this->orderRepository->findOneBy(array('id' => $ReThreeDAuth->getContent()->getMerchantFree1()));

            $statusId = $Order->getOrderStatus()->getId();

            /* @var $paymentStatus \Plugin\SlnPayment42\Entity\OrderPaymentStatus */
            $paymentStatus = $this->orderPaymentStatusRepository->getStatus($Order);

            if ($ReThreeDAuth->getContent()->getOperateId() == "1Auth") {
                $this->orderPaymentStatusRepository->auth($Order, $paymentStatus->getAmount());
            } else {
                $this->orderPaymentStatusRepository->capture($Order, $paymentStatus->getAmount());
            }

            $connection = $this->entityManager->getConnection();
            $connection->beginTransaction();

            // 購入処理
            $Order->setOrderDate(new \DateTime());
            $this->purchaseFlow->commit($Order, new PurchaseContext());

            $config = $this->configRepository->getConfig();
            if ($config->getCardOrderPreEnd() == 2 && $config->getOperateId() == "1Gathering") {
                $this->orderRepository->changeStatus($Order->getId(), $this->orderStatusRepository->find(OrderStatus::PAID));
            } else {
                $this->orderRepository->changeStatus($Order->getId(), $this->orderStatusRepository->find(OrderStatus::NEW));
            }

            $connection->commit();
        } catch (SlnShoppingException $e) {
            // $connection->rollBack();

            // 仮購入解除
            // $this->purchaseFlow->rollback($Order, new PurchaseContext());
            // $this->entityManager->flush();

            log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
            // $this->addWarning($e->getMessage(), 'sln_payment');

            $log = sprintf("card shopping error:%s order_id(%s)", $e->getMessage(), $e->getSlnErrorOrderId())  . " " . $e->getFile() . $e->getLine();
            $this->util->addCardNotice($log);

            if ($e->checkSystemError()) {
                $this->util->addErrorLog($e->getSlnErrorName() . $e->getSlnErrorDetail() . 'order_id:' . $e->getSlnErrorOrderId() . " " . $e->getFile() . $e->getLine());
                // $this->slnMailService->sendErrorMail($e->getSlnErrorName() . $e->getSlnErrorDetail());
            }

            // $this->orderPaymentStatusRepository->fail($Order);

            exit;
        } catch (ShoppingException $e) {
            $connection->rollBack();

            // 仮購入解除
            $this->purchaseFlow->rollback($Order, new PurchaseContext());
            $this->entityManager->flush();

            log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
            $this->addError($e->getMessage());

            $log = sprintf("card shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
            $this->util->addCardNotice($log);
            $this->util->addErrorLog($log);

            $this->slnMailService->sendErrorMail("在庫不足エラーが発生した可能性があるため、ログのご確認の上、払戻処理をお願いいたします。");

            $this->orderPaymentStatusRepository->fail($Order);

            exit;
        } catch (\Exception $e) {
            $connection->rollBack();

            // 仮購入解除
            $this->purchaseFlow->rollback($Order, new PurchaseContext());
            $this->entityManager->flush();

            log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
            $this->addError('front.shopping.system_error');

            $log = sprintf("card shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
            $this->util->addCardNotice($log);
            $this->util->addErrorLog($log);

            $this->slnMailService->sendErrorMail("購入時にシステムエラーが発生しました。ログのご確認の上、払戻処理をお願いいたします。");

            $this->orderPaymentStatusRepository->fail($Order);

            exit;
        }

        // メール送信
        // 二重送信防止のため、すでに購入処理後のステータスになっている受注には処理を行わない
        if (
            $statusId != OrderStatus::NEW //新規受付
            && $statusId != OrderStatus::PAID //入金済み
        ) {
            $event = new EventArgs(
                array(
                    'Order' => $Order,
                ),
                $request
            );
            $this->eventDispatcher->dispatch($event, 'sln.front.shopping.confirm.processing');

            if ($event->getResponse() !== null) {
                return $event->getResponse();
            }

            $this->mailService->sendOrderMail($Order);
            $this->entityManager->flush();
        }

        return ['re_code' => ""];
    }

    /**
     * @Route("/shopping/sln_cvs_payment", name="sln_cvs_payment")
     */
    public function cvsIndex(Request $request)
    {
        $Order = $this->checkOrder();
        if (!($Order instanceof \Eccube\Entity\Order)) {
            $this->addError("受注情報は存在していません。");
            return $this->redirectToRoute('shopping_error');
        }

        $methodClass = $Order->getPayment()->getMethodClass();
        if (!MethodUtils::isCvsMethod($methodClass)) {
            $this->addError("支払い方法を再度選択ください。");
            return $this->redirectToRoute('shopping_error');
        }

        $cvs = $this->cvs;

        // 仮購入の状態にする
        $this->purchaseFlow->prepare($Order, new PurchaseContext());
        $this->entityManager->flush();

        // トランザクション制御
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            //決済状況を記録する
            $this->orderPaymentStatusRepository->unsettled($Order);

            //通信処理を行う
            list($reUrl, $add) = $cvs->Add(
                $Order,
                $this->configRepository->getConfig(),
                $request->getSchemeAndHttpHost() . $this->generateUrl('shopping_complete')
            );

            $this->orderPaymentStatusRepository->requestSuccess($Order, $add->getContent()->getAmount());

            // 購入処理
            $Order->setOrderDate(new \DateTime());
            $this->purchaseFlow->commit($Order, new PurchaseContext());
            $this->orderRepository->changeStatus($Order->getId(), $this->orderStatusRepository->find(OrderStatus::NEW));

            $connection->commit();
        } catch (SlnShoppingException $e) {
            log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
            $this->addError($e->getMessage());

            $log = sprintf("cvs shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId())  . " " . $e->getFile() . $e->getLine();
            $this->util->addCvsNotice($log);

            if ($e->checkSystemError()) {
                $this->util->addErrorLog($e->getSlnErrorName() . $e->getSlnErrorDetail() . 'order_id:' . $Order->getId())  . " " . $e->getFile() . $e->getLine();
                $this->slnMailService->sendErrorMail($e->getSlnErrorName() . $e->getSlnErrorDetail());
            }

            $this->orderPaymentStatusRepository->fail($Order);
            $connection->commit();

            return $this->redirectToRoute('shopping_error');
        } catch (ShoppingException $e) {
            log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
            $this->addError($e->getMessage());

            $log = sprintf("cvs shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId())  . " " . $e->getFile() . $e->getLine();
            $this->util->addCvsNotice($log);
            $this->util->addErrorLog($log);

            $this->slnMailService->sendErrorMail("在庫不足エラーが発生した可能性があるため、ログのご確認の上、払戻処理をお願いいたします。");

            $this->orderPaymentStatusRepository->fail($Order);
            $connection->commit();

            return $this->redirectToRoute('shopping_error');
        } catch (\Exception $e) {
            log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
            $this->addError('front.shopping.system_error');

            $log = sprintf("cvs shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId())  . " " . $e->getFile() . $e->getLine();
            $this->util->addCvsNotice($log);
            $this->util->addErrorLog($log);

            $this->slnMailService->sendErrorMail("購入時にシステムエラーが発生しました。ログのご確認の上、払戻処理をお願いいたします。");

            $this->orderPaymentStatusRepository->fail($Order);
            $connection->commit();

            return $this->redirectToRoute('shopping_error');
        }

        // カート削除
        $this->cartService->clear()->save();

        $event = new EventArgs(
            array(
                'Order' => $Order,
            ),
            $request
        );
        $this->eventDispatcher->dispatch($event, 'sln.front.shopping.confirm.processing');

        if ($event->getResponse() !== null) {
            return $event->getResponse();
        }

        // 受注IDをセッションにセット
        $request->getSession()->set($this->sessionOrderKey, $Order->getId());

        // メール送信
        $this->slnMailService->sendOrderMail($Order, $reUrl);
        $this->entityManager->flush();

        // オンライン収納画面に移動する
        return $this->redirect($reUrl);
    }

    /**
     * オンライン収納決済通知受信
     * 
     * @Method("POST")
     * @Route("/sln_payment/sln_recv", name="sln_recv")
     * @Template("@SlnPayment42/sln_recv.twig")
     */
    public function recvIndex(Request $request)
    {
        $reCode = 1;
        $paramHash = array();

        $this->util->addCvsNotice("recv request_post_data:" . json_encode($request->request->all()));

        $all = $request->request->all();

        $paramHash['TransactionId'] = $all['TransactionId'];
        $paramHash['TransactionDate'] = $all['TransactionDate'];
        $paramHash['Amount'] = $all['Amount'];

        if ($all['MerchantId'] && $all['TransactionId']) { //必要項目をチェックする

            try {
                $cvs = $this->cvs;
                list($orderId, $RecvContent) = $cvs->Recv($this->configRepository->getConfig(), $request);

                $Order = $this->orderRepository->findOneBy(array('id' => $orderId));

                $paramHash['OrderId'] = $orderId;
                $paramHash['PaymentTotal'] = $Order->getPaymentTotal();

                //状態コードを確認する
                if ($RecvContent->getCondition() == '000') {
                    //通知結果判断
                    if ($RecvContent->getResponseCd() == "OK") {

                        if ($Order->getPaymentTotal() == $RecvContent->getAmount()) { //支払い情報を確認

                            //決済ステータスを変更する
                            $this->orderPaymentStatusRepository->paySuccess($Order, $RecvContent->getCvsCd());

                            //受注ステータスを更新する
                            $Order->setOrderStatus($this->orderStatusRepository->find(OrderStatus::PAID));
                            $Order->setPaymentDate(new \DateTime());
                            $this->entityManager->persist($Order);
                            $this->entityManager->flush();
                            $reCode = 0;
                        } else {
                            $paramHash['message'] = "受注金額と決済金額が一致していません。";
                            $this->slnMailService->sendMailNoOrder($paramHash);
                        }
                    } else {
                        $arrErr = $this->util->reErrorDecode($RecvContent->getResponseCd());
                        $paramHash['message'] = $arrErr[2] . "\nResponseCd:" . $RecvContent->getResponseCd();
                        $this->slnMailService->sendMailNoOrder($paramHash);
                    }
                } else {
                    $paramHash['message'] = sprintf("状態コード対象外です。(Condition:%s)", $RecvContent->getCondition());
                    $this->slnMailService->sendMailNoOrder($paramHash);
                }
                //$reCode = 0;
            } catch (\Exception $e) {
                $mess = $e->getMessage();
                $paramHash['message'] = $mess;

                $this->slnMailService->sendMailNoOrder($paramHash);
            }
        } else {
            $paramHash['message'] = "MerchantId または　TransactionIdが存在していません。";
            $this->slnMailService->sendMailNoOrder($paramHash);
        }

        return ['re_code' => $reCode];
    }
}
