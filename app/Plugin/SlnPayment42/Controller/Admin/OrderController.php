<?php

namespace Plugin\SlnPayment42\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Common\Constant;
use Eccube\Entity\Shipping;
use Eccube\Entity\Order;
use Eccube\Repository\OrderRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Plugin\SlnPayment42\Repository\OrderPaymentStatusRepository;
use Plugin\SlnPayment42\Repository\OrderPaymentHistoryRepository;
use Plugin\SlnPayment42\Repository\PluginConfigRepository;
use Plugin\SlnPayment42\Service\Util;
use Plugin\SlnPayment42\Service\SlnAction\Credit;
use Plugin\SlnPayment42\Service\SlnAction\Cvs;
use Plugin\SlnPayment42\Service\Method\CvsMethod;
use Plugin\SlnPayment42\Service\Method\MethodUtils;
use Plugin\SlnPayment42\Exception\SlnShoppingException;
use Plugin\SlnPayment42\SlnException;


class OrderController extends AbstractController
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var OrderPaymentStatusRepository
     */
    private $orderPaymentStatusRepository;

    /**
     * @var OrderPaymentHistoryRepository
     */
    private $orderPaymentHistoryRepository;

    /**
     * @var PluginConfigRepository
     */
    private $configRepository;

    /**
     * @var Util
     */
    private $util;

    public function __construct(
        OrderRepository $orderRepository,
        OrderPaymentStatusRepository $orderPaymentStatusRepository,
        OrderPaymentHistoryRepository $orderPaymentHistoryRepository,
        PluginConfigRepository $configRepository,
        Util $util,
        Credit $credit,
        Cvs $cvs
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderPaymentStatusRepository = $orderPaymentStatusRepository;
        $this->orderPaymentHistoryRepository = $orderPaymentHistoryRepository;
        $this->configRepository = $configRepository;
        $this->util = $util;
        $this->credit = $credit;
        $this->cvs = $cvs;
    }

    /**
     * @Route("/%eccube_admin_route%/order/{id}/sln_pay_command", requirements={"id" = "\d+"}, name="admin_sln_pay_command", methods={"PUT"})
     * 
     * @param Request $request
     * @param Shipping $Shipping
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function payCommand(Request $request, Shipping $Shipping)
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            return $this->json(['status' => 'NG'], 400);
        }

        $Order = $Shipping->getOrder();
        $payCommand = $request->get('pay_command');
        if (!$payCommand) {
            return $this->json(['status' => 'NG'], 400);
        }

        switch ($payCommand) {
            case 1:
                $result = $this->payCommandCommit($Order);
                break;
            case 2:
                $result = $this->payCommandCancel($Order);
                break;
            default:
                return $this->json(['status' => 'NG'], 400);
        }

        return $this->json(array_merge(['status' => 'OK'], $result));
    }

    /**
     * クレジット決済売上げ
     */
    public function payCommandCommit(Order $Order)
    {
        try {
            $methodClass = $Order->getPayment()->getMethodClass();
            if (!MethodUtils::isCreditCardMethod($methodClass)) {
                return ['message' => sprintf('対応していない支払い方法のため処理をスキップしました。受注ID:%s 支払い方法:%s', $Order->getId(), $Order->getPaymentMethod()), ];
            }

            $card = $this->credit;

            /* @var $payStatus \Plugin\SlnPayment42\Entity\PlgSlnOrderPaymentStatus */
            $payStatus = $this->orderPaymentStatusRepository->findOneBy(array('id' => $Order->getId()));
            if ($payStatus) {
                if ($payStatus->getPaymentStatus() == 11) {
                    $history = $this->orderPaymentHistoryRepository
                                ->findOneBy(
                                    array('orderId' => $Order->getId(),
                                        'operateId' => array('1Auth', '1ReAuth'),
                                        'sendFlg' => 1,
                                        'requestFlg' => 0
                                    ),
                                    array('id' => 'DESC')
                                );
                    if (!is_null($history)) {
                        $card->Capture($Order, $this->configRepository->getConfig(), $history);
                        
                        //決済ステータスを変更する
                        $this->orderPaymentStatusRepository->commit($Order, $Order->getPaymentTotal());
                        $result = ['message' => sprintf('売上確定処理を実行しました。受注ID:%s', $Order->getId())];
                    } else {
                        $result = ['message' => sprintf('決済取引履歴が存在していないため処理をスキップしました。受注ID:%s', $Order->getId())];
                    }           
                } else {
                    $arrPayStatusNames = array_flip($this->getParameter('arrPayStatusNames'));
                    $result = ['message' => sprintf('対応していない決済ステータスのため処理をスキップしました。受注ID:%s 決済ステータス:%s', 
                        $Order->getId(), 
                        $arrPayStatusNames[$payStatus->getPaymentStatus()])];
                }
            } else {
                $result = ['message' => sprintf('対応していない決済ステータスのため処理をスキップしました。受注ID:%s 決済ステータス:なし', $Order->getId())];
            }
        } catch (SlnShoppingException $e) {
            $this->util->addCardNotice(sprintf("card order edit error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine()));
            $this->util->addErrorLog($e->getSlnErrorName() . $e->getSlnErrorDetail() . 'order_id:' . $Order->getId() . " " . $e->getFile() . $e->getLine());
            $result = ['message' => sprintf('決済取引が失敗しました。%s 受注ID:%s', $e->getSlnErrorDetail(), $Order->getId())];
        } catch (SlnException $e) {
            $log = sprintf("card order edit error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
            $this->util->addCardNotice($log);
            $this->util->addErrorLog($log);
            $result = ['message' => sprintf('決済取引が失敗しました。%s 受注ID:%s', $e->getMessage(), $Order->getId())];
        } catch (\Exception $e) {
            $log = sprintf("card order edit error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
            $this->util->addCardNotice($log);
            $this->util->addErrorLog($log);
            //$result = ['message' => sprintf('%s (Line:%s) 受注ID:%s', $e->getMessage(), $e->getFile(), $e->getLine(), $Order->getId())]; // for debug
            $result = ['message' => '不明なエラーです'];
        }
        return $result;
    }

    /**
     * 決済取消
     */
    public function payCommandCancel(Order $Order)
    {
        try {
            $methodClass = $Order->getPayment()->getMethodClass();
            if (!MethodUtils::isSlnPaymentMethod($methodClass)) {
                return ['message' => sprintf('対応していない支払い方法のため処理をスキップしました。受注ID:%s 支払い方法:%s', $Order->getId(), $Order->getPaymentMethod()), ];
            }
            $history = $this->orderPaymentHistoryRepository->findOneBy(
                array('orderId' => $Order->getId(),
                    'operateId' => array('2Add', '1Auth', '1Gathering', '1ReAuth'),
                    'sendFlg' => 1,
                    'requestFlg' => 0
                ),
                array('id' => 'DESC')
            );

            $cvs = $this->cvs;
            $card = $this->credit;
            
            /* @var $payStatus \Plugin\SlnPayment42\Entity\PlgSlnOrderPaymentStatus */
            $payStatus = $this->orderPaymentStatusRepository->getStatus($Order);
            if ($payStatus) {
                if ($payStatus->getPaymentStatus() != 4 && $payStatus->getPaymentStatus() != 14 && $payStatus->getPaymentStatus() != 99) {
                    if (!is_null($history)) {
                        if ($methodClass == CvsMethod::class) {
                            $cvs->Del($Order, $this->configRepository->getConfig(), $history);
                            $this->orderPaymentStatusRepository->cancel($Order);
                        } else {
                            $card->Delete($Order, $this->configRepository->getConfig(), $history);
                            //決済ステータスを変更する
                            $this->orderPaymentStatusRepository->void($Order);
                        }
                        $result = ['message' => sprintf('取消(返品)処理が完了しました。受注ID:%s', $Order->getId())];
                    } else {
                        $result = ['message' => sprintf('決済取引履歴が存在していないため処理をスキップしました。受注ID:%s', $Order->getId())];
                    }
                } else {
                    $arrPayStatusNames = array_flip($this->getParameter('arrPayStatusNames'));
                    $result = ['message' => sprintf('対応していない決済ステータスのため処理をスキップしました。受注ID:%s 決済ステータス:%s',
                        $Order->getId(),
                        $arrPayStatusNames[$payStatus->getPaymentStatus()]
                        )];
                }
            } else {
                $result = ['message' => sprintf('対応していない決済ステータスのため処理をスキップしました。受注ID:%s 決済ステータス:なし', $Order->getId())];
            }
            
        } catch (SlnShoppingException $e) {
            $this->util->addCardNotice(sprintf("card order edit error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine()));
            $this->util->addErrorLog($e->getSlnErrorName() . $e->getSlnErrorDetail() . 'order_id:' . $Order->getId() . " " . $e->getFile() . $e->getLine());
            $result = ['message' => sprintf('決済取引が失敗しました。%s 受注ID:%s', $e->getSlnErrorDetail(), $Order->getId())];
        } catch (SlnException $e) {
            $log = sprintf("card order edit error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
            $this->util->addCardNotice($log);
            $this->util->addErrorLog($log);
            $result = ['message' => sprintf('決済取引が失敗しました。%s 受注ID:%s', $e->getMessage(), $Order->getId())];
        } catch (\Exception $e) {
            $log = sprintf("card order edit error:%s order_id(%s)", $e->getMessage(), $Order->getId() . " " . $e->getFile() . $e->getLine());
            $this->util->addCardNotice($log);
            $this->util->addErrorLog($log);
            // $result = ['message' => sprintf('%s (File:%s Line:%s) 受注ID:%s', $e->getMessage(), $e->getFile(), $e->getLine(), $Order->getId())]; // for debug
            $result = ['message' => '不明なエラーです'];
        }
        return $result;
    }
}