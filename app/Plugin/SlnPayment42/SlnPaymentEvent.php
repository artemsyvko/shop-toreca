<?php

namespace Plugin\SlnPayment42;

use Eccube\Common\Constant;
use Eccube\Common\EccubeConfig;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Event\TemplateEvent;
use Eccube\Event\EventArgs;
use Eccube\Event\EccubeEvents;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Exception\ShoppingException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Plugin\SlnPayment42\Repository\MemCardIdRepository;
use Plugin\SlnPayment42\Repository\OrderPaymentHistoryRepository;
use Plugin\SlnPayment42\Repository\OrderPaymentStatusRepository;
use Plugin\SlnPayment42\Repository\PluginConfigRepository;
use Plugin\SlnPayment42\Service\Method\CreditCard;
use Plugin\SlnPayment42\Service\Method\RegisteredCreditCard;
use Plugin\SlnPayment42\Service\Method\MethodUtils;
use Plugin\SlnPayment42\Service\SlnMailService;
use Plugin\SlnPayment42\Service\BasicItem;
use Plugin\SlnPayment42\Service\Util;
use Plugin\SlnPayment42\Service\SlnAction\Cvs;
use Plugin\SlnPayment42\Service\SlnAction\Credit;
use Plugin\SlnPayment42\Exception\SlnShoppingException;
use Plugin\SlnPayment42\Service\SlnAction\Mem;
use Plugin\SlnPayment42\Repository\SlnAgreementRepository;

class SlnPaymentEvent implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;
    
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * @var SlnMailService
     */
    private $mailService;

    /**
     * @var BasicItem
     */
    private $basicItem;

    /**
     * @var Util
     */
    private $util;

    /**
     * @var MemCardIdRepository
     */
    private $memCardIdRepository;

    /**
     * @var OrderPaymentHistoryRepository
     */
    private $orderPaymentHistoryRepository;

    /**
     * @var OrderPaymentStatusRepository
     */
    private $orderPaymentStatusRepository;

    /**
     * @var PluginConfigRepository
     */
    private $configRepository;

    /**
     * @var Mem
     */
    private $mem;

    /**
     * @var SlnAgreementRepository
     */
    private $SlnAgreementRepository;

    public function __construct(
        ContainerInterface $container,
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authorizationChecker,
        EccubeConfig $eccubeConfig,
        EventDispatcherInterface $eventDispatcher,
        PaymentRepository $paymentRepository,
        OrderStatusRepository $orderStatusRepository,
        SlnMailService $mailService,
        BasicItem $basicItem,
        Util $util,
        MemCardIdRepository $memCardIdRepository,
        OrderPaymentStatusRepository $orderPaymentStatusRepository,
        OrderPaymentHistoryRepository $orderPaymentHistoryRepository,
        PluginConfigRepository $configRepository,
        Mem $mem,
        Credit $credit,
        Cvs $cvs,
        SlnAgreementRepository $SlnAgreementRepository
    ) {
        $this->container = $container;
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->eccubeConfig = $eccubeConfig;
        $this->eventDispatcher = $eventDispatcher;
        $this->paymentRepository = $paymentRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->mailService = $mailService;
        $this->basicItem = $basicItem;
        $this->util = $util;
        $this->memCardIdRepository = $memCardIdRepository;
        $this->orderPaymentStatusRepository = $orderPaymentStatusRepository;
        $this->orderPaymentHistoryRepository = $orderPaymentHistoryRepository;
        $this->configRepository = $configRepository;
        $this->mem = $mem;
        $this->credit = $credit;
        $this->cvs = $cvs;
        $this->slnAgreementRepository = $SlnAgreementRepository;
    }

    /**
     * リッスンしたいサブスクライバのイベント名の配列を返します。
     * 配列のキーはイベント名、値は以下のどれかをしてします。
     * - 呼び出すメソッド名
     * - 呼び出すメソッド名と優先度の配列
     * - 呼び出すメソッド名と優先度の配列の配列
     * 優先度を省略した場合は0
     *
     * 例：
     * - array('eventName' => 'methodName')
     * - array('eventName' => array('methodName', $priority))
     * - array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Order/index.twig' => 'onAdminOrderIndexTwig',
            EccubeEvents::ADMIN_ORDER_INDEX_INITIALIZE => 'onAdminOrderIndexInitialize',
            EccubeEvents::ADMIN_ORDER_INDEX_SEARCH => 'onAdminOrderIndexSearch',
            '@admin/Order/edit.twig' => 'onAdminOrderEditTwig',
            EccubeEvents::ADMIN_ORDER_EDIT_INDEX_INITIALIZE => 'onAdminOrderEditIndexInitialize',
            'Cart/index.twig' => 'onCartIndexTwig',
            'Shopping/confirm.twig' => 'onShoppingConfirmTwig',
            'Mypage/index.twig' => 'onMypageTwig',
            'Mypage/history.twig' => 'onMypageTwig',
            'Mypage/favorite.twig' => 'onMypageTwig',
            'Mypage/change.twig' => 'onMypageTwig',
            'Mypage/change_complete.twig' => 'onMypageTwig',
            'Mypage/delivery.twig' => 'onMypageTwig',
            'Mypage/delivery_edit.twig' => 'onMypageTwig',
            'Mypage/withdraw.twig' => 'onMypageTwig',
            '@SlnRegular4/Mypage/regular_order.twig' => 'onMypageTwig',
            '@SlnRegular4/Mypage/regular_history.twig' => 'onMypageTwig',
            '@SlnPayment42/sln_edit_card.twig' => 'onMypageTwig',
            EccubeEvents::FRONT_CART_BUYSTEP_INITIALIZE => 'onFrontCartBuystepInitialize',
            'Shopping/index.twig' => 'onShoppingIndexTwig',
            'sln.service.regular.nextorder.complete' => 'onSlnServiceRegularNextorderComplete',
            'sln.service.regular.mypage_history.change_payids' => 'onSlnServiceRegularMypageHistoryChangePayids',
            EccubeEvents::ADMIN_CUSTOMER_EDIT_INDEX_INITIALIZE => 'onAdminCustomerEditIndexInitialize',
            EccubeEvents::ADMIN_CUSTOMER_DELETE_COMPLETE => 'onAdminCustomerDeleteComplete',
            EccubeEvents::FRONT_MYPAGE_WITHDRAW_INDEX_COMPLETE => 'onFrontMypageWithdrawComplete',
        ];
    }

    /**
     * 受注一覧 - 検索画面介入
     */
    public function onAdminOrderIndexTwig(TemplateEvent $event) {
        $pData = $event->getParameters();
        $viewPayStatus = array();
        if ($pData['pagination']) {
            $orderIds = array();
            foreach ($pData['pagination'] as $order) {
                $orderIds[] = $order->getId();
            }
            if (count($orderIds)) {
                $payStatuses = $this->orderPaymentStatusRepository->findBy(array('id' => $orderIds));
                if (count($payStatuses)) {
                    foreach ($payStatuses as $payStatus) {
                        $viewPayStatus[$payStatus->getId()] = $payStatus;
                    }
                }
            }
        }
        $pData['viewPayStatus'] = $viewPayStatus;
        $pData['pay_status'] = array_flip($this->container->getParameter('arrPayStatusNames'));
        $token = $this->container->get('security.csrf.token_manager')->getToken(Constant::TOKEN_NAME)->getValue();
        $pData["pay_token"] = $token;
        $event->setParameters($pData);
        $event->addSnippet('@SlnPayment42/admin/order_index.twig');
    }

    /**
     * 受注一覧 - 検索項目追加
     */
    public function onAdminOrderIndexInitialize(EventArgs $event)
    {
        $arrPayStatusNames = $this->container->getParameter('arrPayStatusNames');
        $builder = $event->getArgument('builder');
        $builder->add('sln_pay_status', ChoiceType::class, [
            'label' => '決済状況',
            'choices' => $arrPayStatusNames,
            'expanded' => true,
            'multiple' => true,
        ]);
    }

    /**
     * 受注一覧 - 検索実行
     */
    public function onAdminOrderIndexSearch(EventArgs $event)
    {
        $searchData = $event->getArgument('searchData');
        $qb = $event->getArgument('qb');
        $pyStatus = $searchData['sln_pay_status'];
        if (count($pyStatus)) {
            $qb2 = $this->entityManager->createQueryBuilder();
            $qb2->select('s')
                ->from('\Plugin\SlnPayment42\Entity\OrderPaymentStatus', 'sln_status')
                ->andWhere('o.id = sln_status.id')
                ->andWhere($qb2->expr()->in('sln_status.paymentStatus', $pyStatus));
            $qb->andWhere($qb->expr()->exists($qb2->getDQL()));
        }
    }

    /**
     * 受注登録編集 - 画面介入
     */
    public function onAdminOrderEditTwig(TemplateEvent $event)
    {
        $pData = $event->getParameters();
        $Order = $pData['Order'];

        if (MethodUtils::isSlnPaymentMethodByOrder($Order)) {
            //決済情報をとる
            $paymentStatus = $this->orderPaymentStatusRepository->getStatus($Order);
            //決済ステータスを表示する
            if ($paymentStatus) {
                $pData['payStatusId'] = $paymentStatus->getPaymentStatus();
                
                $payStatus = $this->orderPaymentStatusRepository->getPayStatusName($paymentStatus->getPaymentStatus());
                if (empty($payStatus)) {
                    // ステータス発見できず
                    return;
                }
                $pData['payStatus'] = $payStatus;
                
                $pData['payAmount'] = $paymentStatus->getAmount();
                
                $pData['isCard'] = true;

                $agreementStatus = $this->slnAgreementRepository->getAgreementStatus($Order);

                //個人情報同意状況を表示する
                if ($agreementStatus == 1) {
                    $pData['agreement'] = "同意します";
                } else {
                    $pData['agreement'] = "";
                }
            
                if (MethodUtils::isCvsMethod($Order->getPayment()->getMethodClass())) {
                    // コンビニ支払い
                    $pData['isCard'] = false;
                    
                    $cvsName = "";
                    
                    if ($paymentStatus->getPayee()) {
                        //支払い先
                        $arrCvsCd = $this->basicItem->getCvsCd();
                    
                        $cvsName = $arrCvsCd[$paymentStatus->getPayee()];
                        if (!$cvsName) {
                            $cvsName = $paymentStatus->getPayee();
                        }
                    
                        $pData['payCvsName'] = $cvsName;
                    }
                    
                    $FreeAreaHistory = $this->orderPaymentHistoryRepository
                                            ->findOneBy(
                                                array('orderId' => $Order->getId(),
                                                    'operateId' => array('2Add', '2Chg'),
                                                    'sendFlg' => 1,
                                                    'requestFlg' => 0,
                                                    'responseCd' => 'OK',
                                                ),
                                                array('id' => 'DESC')
                                            );
                    if ($FreeAreaHistory) {
                        $FreeAreabody = $FreeAreaHistory->getBody();
                        $FreeAreadata = json_decode($FreeAreabody, 1);
                    
                        //コンビニ支払いリンクをとる
                        $pData['payLink'] = $this->configRepository->getConfig()->getCreditConnectionPlace3() . sprintf("?code=%s&rkbn=2", $FreeAreadata['FreeArea']);
                    }
                }
                //通信ログをとる
                $pData['payHistorys'] = $this->orderPaymentHistoryRepository->findBy(array('orderId' => $Order->getId()));
            }
        }
        $event->setParameters($pData);
        $event->addSnippet('@SlnPayment42/admin/order_edit.twig');
    }

    /**
     * redirectToRouteのレスポンスを取得する
     */
    public function redirectToRouteResponse($route, $params = array()) {
        $router = $this->container->get('router');
        return new RedirectResponse($router->generate($route, $params), 302);
    }

    /**
     * 受注登録編集 - 初期化
     */
    public function onAdminOrderEditIndexInitialize(EventArgs $event) {
        $request = $event->getRequest();
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            
            $Order = $event->getArgument('TargetOrder');
            $orderId = $Order->getId();
            
            if (!$orderId) {
                return ;
            }
            
            $history = $this->orderPaymentHistoryRepository
                ->findOneBy(
                    array('orderId' => $orderId,
                        'operateId' => array('2Add', '1Auth', '1Gathering', '1ReAuth'),
                        'sendFlg' => 1,
                        'requestFlg' => 0
                    ),
                    array('id' => 'DESC')
                );
            
            if (!$history) {
                return ;
            }
            
            $mode = $request->get('mode');

            if (!$mode || substr($mode, 0, 3) != "sln" || $request->getMethod() != "POST") {
                return ;
            }
            
            $cvs = $this->cvs;
            $card = $this->credit;
            
            try {
                //決済に関するボタン操作
                switch ($mode) {
                    case 'sln_cvs_ref'://結果照会
                        $ref = $cvs->Ref($Order, $this->configRepository->getConfig(), $history);

                        if (!is_null($ref->getAmount())) {//回答する場合
                            if ($Order->getPaymentTotal() == $ref->getAmount()) {//支払い情報を確認
            
                                //決済ステータスを変更する
                                $this->orderPaymentStatusRepository->paySuccess($Order, $ref->getCvsCd());
            
                                //受注ステータスを更新する
                                $Order->setOrderStatus($this->orderStatusRepository->find(OrderStatus::PAID));
                                $Order->setPaymentDate(new \DateTime());
                                $this->entityManager->persist($Order);
                                $this->entityManager->flush();
                                
                                $this->util->addSuccess($request, 'admin.common.save_complete', 'admin');
                                return;
                            } else {
                                throw new SlnException("受注金額と決済金額が一致していません。");
                            }
                        }
                        break;
                    case "sln_cvs_chg"://決済金額変更
                        $cvs->Chg($Order, $this->configRepository->getConfig(), $history);
                        $this->orderPaymentStatusRepository->requestSuccess($Order, $Order->getPaymentTotal());
                        //決済金額情報を変更する
                        $this->orderPaymentStatusRepository->change($Order, $Order->getPaymentTotal());
                        $this->util->addSuccess($request, '決済金額を変更しました', 'admin');
                        return;
                    case "sln_cvs_del":
                        //決済削除
                        $cvs->Del($Order, $this->configRepository->getConfig(), $history);
                        
                        $this->orderPaymentStatusRepository->cancel($Order);
                        $this->util->addSuccess($request, '決済を削除しました。', 'admin');
                        return;
                    case "sln_cvs_add":
                        list($link, $add) = $cvs->Add($Order, $this->configRepository->getConfig(), '');

                        //決済ステータスを変更する
                        $this->orderPaymentStatusRepository
                                ->requestSuccess($Order, $add->getContent()->getAmount());
                    
                        //決済金額情報を変更する
                        $this->orderPaymentStatusRepository->change($Order, $add->getContent()->getAmount());
                                
                        //受注ステータスを更新する
                        $Order->setOrderStatus($this->orderStatusRepository->find(OrderStatus::NEW));
                        $this->entityManager->persist($Order);
                        $this->entityManager->flush();
                    
                        $this->util->addSuccess($request, '再決済を行いました', 'admin');
                        return;
                    case "sln_card_commit":
                        $card->Capture($Order, $this->configRepository->getConfig(), $history);
                        
                        //決済ステータスを変更する
                        $this->orderPaymentStatusRepository->commit($Order, $Order->getPaymentTotal());
                        $this->util->addSuccess($request, '売上確定処理を実行しました', 'admin');
                        return;
                    case "sln_card_cancel":
                        $card->Delete($Order, $this->configRepository->getConfig(), $history);
                        
                        //決済ステータスを変更する
                        $this->orderPaymentStatusRepository->void($Order);
                        $this->util->addSuccess($request, '取消(返品)処理が完了しました', 'admin');
                        return;
                    case "sln_card_change":
                        $card->Change($Order, $this->configRepository->getConfig(), $history);
                    
                        //決済金額情報を変更する
                        $this->orderPaymentStatusRepository->change($Order, $Order->getPaymentTotal());
                        $this->util->addSuccess($request, '決済金額変更処理を実行しました', 'admin');
                        return;
                    case "sln_card_reauth":
                        
                        /* @var $kaiinHistory \Plugin\SlnPayment42\Entity\PlgSlnOrderPaymentHistory */
                        $kaiinHistory = $this->orderPaymentHistoryRepository
                                            ->findOneBy(
                                                array('orderId' => $Order->getId(),
                                                    'operateId' => array('1Auth', '1Gathering'),
                                                ),
                                                array('id' => 'ASC')
                                            );
                        
                        $card->ReAuth($Order, $this->configRepository->getConfig(), $history);
                        
                        if ($kaiinHistory->getOperateId() == '1Gathering') {
                            //再オーソリ
                            $this->orderPaymentStatusRepository->capture($Order, $Order->getPaymentTotal());
                        } else {
                            //再オーソリ
                            $this->orderPaymentStatusRepository->auth($Order, $Order->getPaymentTotal());
                        }
                        $this->util->addSuccess($request, '再オーソリの取得処理を実行しました', 'admin');
                        return;
                    default:
                        throw new SlnException("ボタン例外処理。");
                        break;
                }
            } catch (SlnShoppingException $e) {
                log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
                if (substr($mode, 4, 4) == 'card') {
                    $this->util->addCardNotice(sprintf("card order edit error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine()));
                } else {
                    $this->util->addCvsNotice(sprintf("cvs order edit error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine()));
                }
                $this->util->addErrorLog($e->getSlnErrorName() . $e->getSlnErrorDetail() . 'order_id:' . $Order->getId() . " " . $e->getFile() . $e->getLine());
                $this->ErrMss = $e->getSlnErrorDetail();
                $this->util->addWarning($request, $this->ErrMss, 'admin');
            } catch (SlnException $e) {
                log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
                if (substr($mode, 4, 4) == 'card') {
                    $log = sprintf("card order edit error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
                    $this->util->addCardNotice($log);
                } else {
                    $log = sprintf("cvs order edit error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
                    $this->util->addCvsNotice($log);
                }
                $this->util->addErrorLog($log);
                $this->ErrMss = $e->getMessage();
                $this->util->addWarning($request, $this->ErrMss, 'admin');
            } catch (\Exception $e) {
                log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
                if (substr($mode, 4, 4) == 'card') {
                    $log = sprintf("card order edit error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
                    $this->util->addCardNotice($log);
                } else {
                    $log = sprintf("cvs order edit error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
                    $this->util->addCvsNotice($log);
                }
                $this->util->addErrorLog($log);
                throw new \Exception($e->getMessage() . " " . $e->getFile() . $e->getLine());
            }
        }
    }

    public function onCartIndexTwig(TemplateEvent $event) {
        $isQuick = $this->configRepository->getConfig()->getQuickAccounts();
        if ($isQuick == 1) {
            $event->addSnippet('@SlnPayment42/sln_cart_quick_pay.twig', true);
        }
    }

    /**
     * 注文ボタンの文字列を変更する
     */
    public function onShoppingConfirmTwig(TemplateEvent $event) {
        // 注文ボタンタイトルを変更する
        $Order = $event->getParameter('Order');
        if ($Order) {
            $methodClass = $Order->getPayment()->getMethodClass();
            if (MethodUtils::isSlnPaymentMethodByOrder($Order)){
                $event->addSnippet('@SlnPayment42/sln_shopping_confirm.twig');
            }
        }

        //3Dセキュア判定
        $isThreedPay = $this->configRepository->getConfig()->getThreedPay();
        $event->setParameter('Is3DPay', $isThreedPay == 1);
        
        //クレジットカード決済判定
        $methodClass = $Order->getPayment()->getMethodClass();
        $isCcCard = MethodUtils::isCreditCardMethod($methodClass);
        $event->setParameter('IsCcCard', $isCcCard);
        
    }

    public function onMypageTwig(TemplateEvent $event) {
        $event->addSnippet('@SlnPayment42/sln_mypage_add_item.twig');
    }

    public function onFrontCartBuystepInitialize(EventArgs $event) {
        $session = $event->getRequest()->getSession();
        $session->remove('eccube.sln.pay.slClink');
        if (array_key_exists('slClink', $_GET) && $_GET['slClink'] == 1) {
            $session->set('eccube.sln.pay.slClink', 1);
        }
    }

    /**
     * ご注文手続き画面介入
     */
    public function onShoppingIndexTwig(TemplateEvent $event) {
        $slClink = false;
        $isCreditCardRegistered = false;
        $isEnabledQuickPay = false;

        // クイック決済選択判定
        $session = new Session();
        if ($session->get('eccube.sln.pay.slClink') == 1) {
            $session->remove('eccube.sln.pay.slClink');
            $slClink = true;
        }

        // クレジットカード登録判定
        try {
            $Customer = $event->getParameter('Order')->getCustomer();
            if ($Customer != null) {
                $ReMemRef = $this->mem->MemRef($Customer, $this->configRepository->getConfig());
                if ($ReMemRef->getContent()->getKaiinStatus() == 0) {
                    $isCreditCardRegistered = true;
                }
            }
        } catch(\Exception $e) {
            log_info($e->getMessage());
        }

        // クイック決済選択時かつクレジットカード登録済みの場合は登録済み
        if ($slClink && $isCreditCardRegistered) {
            $isEnabledQuickPay = true;
        }

        // クレジットカード決済ID取得
        $ccPayId = 0;
        $payment = $this->paymentRepository->findOneBy(['method_class' => CreditCard::class]);
        if ($payment) {
            $ccPayId = $payment->getId();
        }

        // 登録済みクレジットカード決済ID取得
        $rcPayId = 0;
        $payment = $this->paymentRepository->findOneBy(['method_class' => RegisteredCreditCard::class]);
        if ($payment) {
            $rcPayId = $payment->getId();
        }

        $event->setParameter('slClink', $slClink);
        $event->setParameter('isEnabledQuickPay', $isEnabledQuickPay);
        $event->setParameter('isCreditCardRegistered', $isCreditCardRegistered);
        $event->setParameter('ccPayId', $ccPayId);
        $event->setParameter('rcPayId', $rcPayId);
        
        $event->addSnippet('@SlnPayment42/sln_shopping_quick_pay.twig');
    }

    /**
     * 定期受注により受注変換完了時
     * @param EventArgs $event
     * @throws \Exception
     */
    public function onSlnServiceRegularNextorderComplete(EventArgs $event)
    {
        /* @var $Order \Plugin\SlnRegular4\Entity\SlnRegularOrder */
        $Order = $event->getArgument('Order');
        
        //プラグイン決済方法判断
        $methodClass = $Order->getPayment()->getMethodClass();
        if (!MethodUtils::isSlnPaymentMethod($methodClass)) {
            return;
        }
        
        $event->setArgument('isSendMail', false);
        
        $cvs = $this->cvs;
        
        // トランザクション制御
        $em = $this->entityManager;
        
        $reUrl = "";
        
        try {
            
            if (MethodUtils::isCvsMethod($methodClass)) {
                //決済状況を記録する
                $this->orderPaymentStatusRepository->unsettled($Order);
                
                //通信処理を行う
                list($reUrl, $add) = $cvs->Add(
                    $Order,
                    $this->configRepository->getConfig(),
                    $event->getRequest()->getSchemeAndHttpHost() . $this->util->generateUrl('shopping_complete'));
                
                $this->orderPaymentStatusRepository->requestSuccess($Order, $add->getContent()->getAmount());
            } else {
                
                $method = $em->getRepository('\Plugin\SlnRegular4\Entity\SlnRegularPluginConfig')->getConfig()->getNextCreditMethod();
                
                //決済状況を記録する
                $this->orderPaymentStatusRepository->unsettled($Order);
                
                $master = new \Plugin\SlnPayment42\Service\SlnContent\Credit\Master();
                $card = $this->credit;
                
                list($KaiinId, $KaiinPass) = $this->util->getNewKaiin($this->memCardIdRepository, $Order->getCustomer(), $this->eccubeConfig->get('eccube_auth_magic'));
                $master->setKaiinId($KaiinId);
                $master->setKaiinPass($KaiinPass);
                
                $master->setPayType("01");
                
                if ($method == 1) {
                    $card->Auth($Order, $this->configRepository->getConfig(), $master);
                    $this->orderPaymentStatusRepository->auth($Order, $master->getAmount());
                } else {
                    $card->Gathering($Order, $this->configRepository->getConfig(), $master);
                    $this->orderPaymentStatusRepository->capture($Order, $master->getAmount());
                }
            }
        
        } catch (SlnShoppingException $e) {
            log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
        
            if (MethodUtils::isCvsMethod($methodClass)) {
                $log = sprintf("cvs shopping error:%s order_id(%s)", $e->getSlnErrorCode() . '|' . $e->getSlnErrorName() . '|' . $e->getSlnErrorDetail(), $Order->getId());
                $this->util->addCvsNotice($log);
            } else {
                $log = sprintf("card shopping error:%s order_id(%s)", $e->getSlnErrorCode() . '|' . $e->getSlnErrorName() . '|' . $e->getSlnErrorDetail(), $Order->getId());
                $this->util->addCardNotice($log);
            }
            
            if ($e->checkSystemError()) {
                $this->util->addErrorLog($e->getSlnErrorName() . $e->getSlnErrorDetail() . 'order_id:' . $Order->getId() . " " . $e->getFile() . $e->getLine());
            }
        
            $this->orderPaymentStatusRepository->fail($Order);
            $event->setArgument('errorMess', sprintf('受注id:(%s) 決済処理が失敗しました(%s)', $Order->getId(), $log));
            
            return ;
        
        } catch (ShoppingException $e) {
            log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
        
            if (MethodUtils::isCvsMethod($methodClass)) {
                $log = sprintf("cvs shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
                $this->util->addCvsNotice($log);
            } else {
                $log = sprintf("card shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
                $this->util->addCardNotice($log);
            }
            
            $this->util->addErrorLog($log);
            
            $this->orderPaymentStatusRepository->fail($Order);
            $event->setArgument('errorMess', sprintf('受注id:(%s) 決済処理失敗しました.(%s)', $Order->getId(), $log));
            
            return ;
        } catch (\Exception $e) {
            log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
        
            if (MethodUtils::isCvsMethod($Order->getPayment()->getMethodClass())) {
                $log = sprintf("cvs shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
                $this->util->addCvsNotice($log);
            } else {
                $log = sprintf("card shopping error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
                $this->util->addCardNotice($log);
            }
            
            $this->util->addErrorLog($log);
            
            $this->orderPaymentStatusRepository->fail($Order);
            
            throw new \Exception($e->getMessage());
        }
        
        $event->setArgument('errorMess', null);
        // メール送信
        $this->mailService->sendOrderMail($Order, $reUrl);
    }
    
    /**
     * 定期受注プラグインより支払い方法変更可能かの通知
     */
    public function onSlnServiceRegularMypageHistoryChangePayids(EventArgs $EventArgs)
    {
        $changePayIds = $EventArgs->getArgument('changePayIds');
        $cardType = $this->paymentRepository->findOneBy(['method_class' => CreditCard::class]);
        $cardRegistType = $this->paymentRepository->findOneBy(['method_class' => RegisteredCreditCard::class]);
        $changePayIds[] = $cardType->getId();
        $changePayIds[] = $cardRegistType->getId();
        $EventArgs->setArgument('changePayIds', $changePayIds);
    }

    /**
     * 会員退会時にe-SCOTT会員無効化処理(Admin/CustomerEditController)
     */
    public function onAdminCustomerEditIndexInitialize(EventArgs $event)
    {
        $form = $event->getArgument("builder")->getForm();
        $oldStatusId = $form->getData()
            ->getStatus()
            ->getId();
        
        // 削除完了時に実行
        $this->eventDispatcher->addListener(EccubeEvents::ADMIN_CUSTOMER_EDIT_INDEX_COMPLETE, function (EventArgs $event) use ($oldStatusId) {
            $config = $this->configRepository->getConfig();
            $user = $event->getArgument("Customer");
            $form = $event->getArgument("form");
            $newStatusId = $form->getData()
                ->getStatus()
                ->getId();
            
            if ($oldStatusId != $newStatusId && $newStatusId == CustomerStatus::WITHDRAWING) {
                try {
                    // 会員無効化のみで会員削除はしない
                    $this->mem->MemInval($user, $config);
                } catch (\Exception $e) {
                    // エラーが発生しても正常にEC-CUBE会員退会処理を完了させるためキャッチ
                }
            }
        });
    }
    
    /**
     * 会員退会時にe-SCOTT会員無効化処理(Admin/CustomerController)
     */
    public function onAdminCustomerDeleteComplete(EventArgs $event){
        //削除失敗時は処理を実行しない
        if ($this->entityManager->isOpen()) {
            $config = $this->configRepository->getConfig();
            $id = $event->getRequest()->attributes->get("id");
            
            // 物理削除したIDをもつエンティティを再現
            $user = new Customer();
            $user->setPropertiesFromArray(["id" => $id]);
            
            try {
                // 会員無効化のみで会員削除はしない
                $this->mem->MemInval($user, $config);
            } catch (\Exception $e) {
                // エラーが発生しても正常にEC-CUBE会員退会処理を完了させるためキャッチ
            }
        }
    }
    
    /**
     * 会員退会時にe-SCOTT会員無効化処理(Mypage/WithdrawController)
     */
    public function onFrontMypageWithdrawComplete(EventArgs $event){
        //削除失敗時は処理を実行しない
        if ($this->entityManager->isOpen()) {
            $config = $this->configRepository->getConfig();
            $user = $this->container->get('security.token_storage')->getToken()->getUser();
            
            try {
                // 会員無効化のみで会員削除はしない
                $this->mem->MemInval($user, $config);
            } catch (\Exception $e) {
                // エラーが発生しても正常にEC-CUBE会員退会処理を完了させるためキャッチ
            }
        }
    }
}
