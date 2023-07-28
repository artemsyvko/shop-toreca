<?php

namespace Plugin\SlnPayment42\Service\SlnAction;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Repository\OrderRepository;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\CustomerStatus;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Plugin\SlnPayment42\Repository\MemCardIdRepository;
use Plugin\SlnPayment42\Repository\PluginConfigRepository;
use Plugin\SlnPayment42\Repository\OrderPaymentHistoryRepository;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Request\Check;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Check as RespCheck;
use Plugin\SlnPayment42\Service\SlnContent\Credit\Master;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Request\Auth;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Auth as RespAuth;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Request\Gathering;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Gathering as RespGathering;
use Plugin\SlnPayment42\Entity\OrderPaymentHistory;
use Plugin\SlnPayment42\Service\SlnContent\Credit\Process;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Request\Capture;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Capture as RespCapture;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Request\Delete;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Delete as RespDelete;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Request\Change;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Change as RespChange;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Request\Search;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Search as RespSearch;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Request\ReAuth;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\ReAuth as RespReAuth;
use Plugin\SlnPayment42\Exception\SlnShoppingException;
use Plugin\SlnPayment42\Service\Util;
use Plugin\SlnPayment42\Service\CryptAES;
use Plugin\SlnPayment42\Service\SlnContent\Credit\ThreeDMaster;
use Plugin\SlnPayment42\Service\SlnAction\HttpSend;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Request\ThreeDAuth;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Request\ThreeDGathering;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\ThreeDAuth as RespThreeDAuth;
use Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\ThreeDGathering as RespThreeDGathering;

/**
 * クレジットカード取引
 */
class Credit
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var Util
     */
    private $util;

    /**
     * HttpSend
     */
    private $httpSend;

    /**
     * @var MemCardIdRepository
     */
    private $memCardIdRepository;

    /**
     * @var PluginConfigRepository
     */
    private $configRepository;

    /**
     * @var OrderPaymentHistoryRepository
     */
    private $orderPaymentHistoryRepository;


    public function __construct(
        EntityManagerInterface $entityManager,
        EccubeConfig $eccubeConfig,
        OrderRepository $orderRepository,
        Util $util,
        HttpSend $httpSend,
        MemCardIdRepository $memCardIdRepository,
        PluginConfigRepository $configRepository,
        OrderPaymentHistoryRepository $orderPaymentHistoryRepository
    ) {
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;
        $this->orderRepository = $orderRepository;
        $this->util = $util;
        $this->httpSend = $httpSend;
        $this->memCardIdRepository = $memCardIdRepository;
        $this->configRepository = $configRepository;
        $this->orderPaymentHistoryRepository = $orderPaymentHistoryRepository;
    }

    /**
     * カードチェック
     * @param \Eccube\Entity\Customer $customer
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param Master $master
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Check|mixed
     */
    public function Check( 
        \Eccube\Entity\Customer $Customer, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config, 
        Master $master)
    {
        $sendUrl = $config->getCreditConnectionPlace1();
        
        if (!strlen($sendUrl)) {
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください");
        }
        
        $Check = new Check($master);
        $this->util->setActionBasic($Check, $config);
        $this->util->setActionNotOrderMerchantFree($Check, $Customer);
        
        $Check['TenantId'] = $config->getTenantId();
        
        $httpResponse = $this->httpSend->sendData($sendUrl, $Check, null, true);
        if ($httpResponse) {
            return $this->DeCodeCheckResponse($httpResponse);
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
    }
    
    /**
     * カードチェック情報返信解析
     * @param unknown $response_body
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Check|mixed
     */
    public function DeCodeCheckResponse($response_body)
    {
        $RespCheck = new RespCheck(new Master());
        $this->util->deCodeRespData($RespCheck, $response_body);
        
        //返信内容をDBに記録する
        $this->orderPaymentHistoryRepository->addSendResponseLog(null, $RespCheck);
        
        if($RespCheck['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespCheck['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespCheck['ResponseCd'];
            throw new SlnShoppingException("カードチェックが失敗しました:" . $error, null, null,
                        array_merge((array)$RespCheck['ResponseCd'], (array)$arrErr));
        }
        
        return $RespCheck;
    }
    
    /**
     * カード与信処理を行う
     * @param \Eccube\Entity\Order $Order
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param Master $master
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Auth|mixed
     */
    public function Auth(
        \Eccube\Entity\Order $Order, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config, 
        Master $master)
    {
        $sendUrl = $config->getCreditConnectionPlace1();
        
        if (!strlen($sendUrl)) {
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください");
        }
        
        $Auth = new Auth($master);
        $this->util->setActionBasic($Auth, $config);
        $this->util->setActionMerchantFree($Auth, $Order);
        $Auth['TenantId'] = $config->getTenantId();
        $Auth['Amount'] = floor($Order->getPaymentTotal());
        
        $httpResponse = $this->httpSend->sendData($sendUrl, $Auth, $Order->getId(), true);
        if ($httpResponse) {
            return $this->DeCodeAuthResponse($httpResponse, $Order->getId());
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
    }
    
    /**
     * カード与信返信情報を解析する
     * @param unknown $response_body
     * @param unknown $orderId
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Auth|mixed
     */
    public function DeCodeAuthResponse($response_body, $orderId)
    {
        $RespAuth = new RespAuth(new Master());
        $this->util->deCodeRespData($RespAuth, $response_body);
        
        //返信内容をDBに記録する
        $this->orderPaymentHistoryRepository->addSendResponseLog($orderId, $RespAuth);
        
        if($RespAuth['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespAuth['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespAuth['ResponseCd'];
            throw new SlnShoppingException("カードの与信処理が失敗しました:" . $error, null, null,
                            array_merge((array)$RespAuth['ResponseCd'], (array)$arrErr));
        }
        
        return $RespAuth;
    }
    
    /**
     * 与信売上計上処理を行う
     * @param \Eccube\Entity\Order $Order
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param Master $master
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Auth|mixed
     */
    public function Gathering( 
        \Eccube\Entity\Order $Order, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config, 
        Master $master)
    {
        $sendUrl = $config->getCreditConnectionPlace1();
    
        if (!strlen($sendUrl)) {
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください");
        }
    
        $Gathering = new Gathering($master);
        $this->util->setActionBasic($Gathering, $config);
        $this->util->setActionMerchantFree($Gathering, $Order);
        $Gathering['TenantId'] = $config->getTenantId();
        $Gathering['Amount'] = floor($Order->getPaymentTotal());
    
        $httpResponse = $this->httpSend->sendData($sendUrl, $Gathering, $Order->getId(), true);
        if ($httpResponse) {
            return $this->DeCodeGatheringResponse($httpResponse, $Order->getId());
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
    }
    
    /**
     * 与信売上計上返信情報を解析する
     * @param unknown $response_body
     * @param unknown $orderId
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Auth|mixed
     */
    public function DeCodeGatheringResponse($response_body, $orderId)
    {
        $RespGathering = new RespGathering(new Master());
        $this->util->deCodeRespData($RespGathering, $response_body);
        
        //返信内容をDBに記録する
        $this->orderPaymentHistoryRepository->addSendResponseLog($orderId, $RespGathering);
        
        if($RespGathering['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespGathering['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespGathering['ResponseCd'];
            throw new SlnShoppingException("カード与信の売上計上が失敗しました:" . $error, null, null,
                            array_merge((array)$RespGathering['ResponseCd'], (array)$arrErr));
        }
        
        return $RespGathering;
    }
    
    /**
     * 売上計上処理を行う
     * @param \Eccube\Entity\Order $Order
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param OrderPaymentHistory $history
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Capture|mixed
     */
    public function Capture(
        \Eccube\Entity\Order $Order, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config, 
        OrderPaymentHistory $history)
    {
        $sendUrl = $config->getCreditConnectionPlace1();
        
        if (!strlen($sendUrl)) {
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください");
        }
       
        /* @var $kaiinHistory \Plugin\SlnPayment42\Entity\OrderPaymentHistory */
        $q = $this->entityManager->createQuery("SELECT h from \Plugin\SlnPayment42\Entity\OrderPaymentHistory h
            WHERE h.id < :id AND h.orderId = :orderId AND (h.operateId = :operateId1 OR h.operateId = :operateId2 OR h.operateId = :operateId3) AND h.requestFlg = 1 ORDER BY h.id DESC")
            ->setParameter(":orderId", $Order->getId())
            ->setParameter(":operateId1", '1Auth')
            ->setParameter(":operateId2", '1Gathering')
            ->setParameter(":operateId3", '1ReAuth')
            ->setParameter(":id", $history->getId())
            ->setMaxResults(1);
        $kaiinHistory = $q->getResult();
        $kaiinHistory = $kaiinHistory[0];
        $kaiinData = json_decode($kaiinHistory->getBody(), 1);
        
        $body = $history->getBody();
        $data = json_decode($body, 1);
        
        $Capture = new Capture(new Process());
        $this->util->setActionBasic($Capture, $config);
        $this->util->setActionMerchantFree($Capture, $Order);
        $Capture['ProcessId'] = $history->getProcessId();
        $Capture['ProcessPass'] = $data['ProcessPass'];
        
        if (array_key_exists('KaiinId', $kaiinData)) {
            $Capture['KaiinId'] = $kaiinData['KaiinId'];
        }
        
        $KaiinPass = null;
        
        if ($Capture['KaiinId'] && $Order->getCustomer()) {
            if ($Order->getCustomer()->getStatus()->getId() != CustomerStatus::WITHDRAWING) { // 退会済みの場合は後続処理を行わせない
                list($KaiinId, $KaiinPass) = $this->util->getNewKaiin($this->memCardIdRepository, $Order->getCustomer(), $this->eccubeConfig->get('eccube_auth_magic'));
            }
        }
        
        $Capture['KaiinPass'] = $KaiinPass;
        $Capture['SalesDate'] = date("Ymd");
        
        $httpResponse = $this->httpSend->sendData($sendUrl, $Capture, $Order->getId(), true);
        if ($httpResponse) {
            return $this->DeCodeCaptureResponse($httpResponse, $Order->getId());
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
    }
    
    /**
     * 売上計上返信情報を解析する
     * @param unknown $response_body
     * @param unknown $orderId
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Capture|mixed
     */
    public function DeCodeCaptureResponse($response_body, $orderId)
    {
        $RespCapture = new RespCapture(new Process());
        $this->util->deCodeRespData($RespCapture, $response_body);
        
        //返信内容をDBに記録する
        $this->orderPaymentHistoryRepository->addSendResponseLog($orderId, $RespCapture);
        
        if($RespCapture['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespCapture['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespCapture['ResponseCd'];
            throw new SlnShoppingException("カード与信の売上計上が失敗しました:" . $error, null, null,
                        array_merge((array)$RespCapture['ResponseCd'], (array)$arrErr));
        }
        
        return $RespCapture;
    }
    
    /**
     * 取消処理を行う
     * @param \Eccube\Entity\Order $Order
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param OrderPaymentHistory $history
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Delete|mixed
     */
    public function Delete(
        \Eccube\Entity\Order $Order, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config, 
        OrderPaymentHistory $history)
    {
        $sendUrl = $config->getCreditConnectionPlace1();
        
        if (!strlen($sendUrl)) {
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください");
        }
        
        $body = $history->getBody();
        $data = json_decode($body, 1);
        
        $Delete = new Delete(new Process());
        $this->util->setActionBasic($Delete, $config);
        $this->util->setActionMerchantFree($Delete, $Order);
        $Delete['ProcessId'] = $history->getProcessId();
        $Delete['ProcessPass'] = $data['ProcessPass'];
        
        /* @var $kaiinHistory \Plugin\SlnPayment42\Entity\OrderPaymentHistory */
        $q = $this->entityManager->createQuery("SELECT h from \Plugin\SlnPayment42\Entity\OrderPaymentHistory h
            WHERE h.id < :id AND h.orderId = :orderId AND (h.operateId = :operateId1 OR h.operateId = :operateId2 OR h.operateId = :operateId3) AND h.requestFlg = 1 ORDER BY h.id DESC")
            ->setParameter(":orderId", $Order->getId())
            ->setParameter(":operateId1", '1Auth')
            ->setParameter(":operateId2", '1Gathering')
            ->setParameter(":operateId3", '1ReAuth')
            ->setParameter(":id", $history->getId())
            ->setMaxResults(1);
        $kaiinHistory = $q->getResult();
        $kaiinHistory = $kaiinHistory[0];
      
        $kaiinData = json_decode($kaiinHistory->getBody(), 1);
        
        if (array_key_exists('KaiinId', $kaiinData)) {
            $Delete['KaiinId'] = $kaiinData['KaiinId'];
        }
        
        $KaiinPass = null;
        
        if ($Delete['KaiinId'] && $Order->getCustomer()) {
            if ($Order->getCustomer()->getStatus()->getId() != CustomerStatus::WITHDRAWING) { // 退会済みの場合は後続処理を行わせない
                list($KaiinId, $KaiinPass) = $this->util->getNewKaiin($this->memCardIdRepository, $Order->getCustomer(), $this->eccubeConfig->get('eccube_auth_magic'));
            }
        }
        
        $Delete['KaiinPass'] = $KaiinPass;
        
        $httpResponse = $this->httpSend->sendData($sendUrl, $Delete, $Order->getId(), true);
        if ($httpResponse) {
            return $this->DeCodeDeleteResponse($httpResponse, $Order->getId());
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
        
    }
    
    /**
     * 取消処理返信解析
     * @param unknown $response_body
     * @param unknown $orderId
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Delete|mixed
     */
    public function DeCodeDeleteResponse($response_body, $orderId)
    {
        $RespDelete = new RespDelete(new Process());
        $this->util->deCodeRespData($RespDelete, $response_body);
        
        //返信内容をDBに記録する
        $this->orderPaymentHistoryRepository->addSendResponseLog($orderId, $RespDelete);
        
        if($RespDelete['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespDelete['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespDelete['ResponseCd'];
            throw new SlnShoppingException("カード決済の取消が失敗しました:" . $error, null, null,
                        array_merge((array)$RespDelete['ResponseCd'], (array)$arrErr));
        }
        
        return $RespDelete;
    }
    
    /**
     * 利用額変更処理を行う
     * @param \Eccube\Entity\Order $Order
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param OrderPaymentHistory $history
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Change|mixed
     */
    public function Change(
        \Eccube\Entity\Order $Order, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config, 
        OrderPaymentHistory $history)
    {
        $sendUrl = $config->getCreditConnectionPlace1();
        
        if (!strlen($sendUrl)) {
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください");
        }
        
        $body = $history->getBody();
        $data = json_decode($body, 1);
        
        $Change = new Change(new Process());
        $this->util->setActionBasic($Change, $config);
        $this->util->setActionMerchantFree($Change, $Order);
        $Change['ProcessId'] = $history->getProcessId();
        $Change['ProcessPass'] = $data['ProcessPass'];
        
        /* @var $kaiinHistory \Plugin\SlnPayment42\Entity\OrderPaymentHistory */
        $q = $this->entityManager->createQuery("SELECT h from \Plugin\SlnPayment42\Entity\OrderPaymentHistory h
            WHERE h.id < :id AND h.orderId = :orderId AND (h.operateId = :operateId1 OR h.operateId = :operateId2 OR h.operateId = :operateId3) AND h.requestFlg = 1 ORDER BY h.id DESC")
            ->setParameter(":orderId", $Order->getId())
            ->setParameter(":operateId1", '1Auth')
            ->setParameter(":operateId2", '1Gathering')
            ->setParameter(":operateId3", '1ReAuth')
            ->setParameter(":id", $history->getId())
            ->setMaxResults(1);
        $kaiinHistory = $q->getResult();
        $kaiinHistory = $kaiinHistory[0];
        $kaiinData = json_decode($kaiinHistory->getBody(), 1);
        
        if (array_key_exists('KaiinId', $kaiinData)) {
            $Change['KaiinId'] = $kaiinData['KaiinId'];
        }
        
        $KaiinPass = null;
        
        if ($Change['KaiinId'] && $Order->getCustomer()) {
            if ($Order->getCustomer()->getStatus()->getId() != CustomerStatus::WITHDRAWING) { // 退会済みの場合は後続処理を行わせない
                list($KaiinId, $KaiinPass) = $this->util->getNewKaiin($this->memCardIdRepository, $Order->getCustomer(), $this->eccubeConfig->get('eccube_auth_magic'));
            }
        }
        
        $Change['KaiinPass'] = $KaiinPass;
        $Change['Amount'] = floor($Order->getPaymentTotal());
        
        $httpResponse = $this->httpSend->sendData($sendUrl, $Change, $Order->getId(), true);
        if ($httpResponse) {
            return $this->DeCodeChangeResponse($httpResponse, $Order->getId());
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
        
    }
    
    /**
     * 利用額変更返信情報を解析する
     * @param unknown $response_body
     * @param unknown $orderId
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Change|mixed
     */
    public function DeCodeChangeResponse($response_body, $orderId)
    {
        $RespChange = new RespChange(new Process());
        $this->util->deCodeRespData($RespChange, $response_body);
    
        //返信内容をDBに記録する
        $this->orderPaymentHistoryRepository->addSendResponseLog($orderId, $RespChange);
    
        if($RespChange['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespChange['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespChange['ResponseCd'];
            throw new SlnShoppingException("カードの利用額変更に失敗しました:" . $error, null, null,
                        array_merge((array)$RespChange['ResponseCd'], (array)$arrErr));
        }
    
        return $RespChange;
    }
    
    /**
     * 取引参照を行う
     * @param \Eccube\Entity\Order $Order
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param OrderPaymentHistory $history
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Search|mixed
     */
    public function Search(
        \Eccube\Entity\Order $Order, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config, 
        OrderPaymentHistory $history)
    {
        $sendUrl = $config->getCreditConnectionPlace1();
        
        if (!strlen($sendUrl)) {
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください");
        }
        
        $body = $history->getBody();
        $data = json_decode($body, 1);
        
        $Search = new Search(new Process());
        $this->util->setActionBasic($Search, $config);
        $this->util->setActionMerchantFree($Search, $Order);
        $Search['ProcessId'] = $history->getProcessId();
        $Search['ProcessPass'] = $data['ProcessPass'];
        
        /* @var $kaiinHistory \Plugin\SlnPayment42\Entity\OrderPaymentHistory */
        $q = $this->entityManager->createQuery("SELECT h from \Plugin\SlnPayment42\Entity\OrderPaymentHistory h
            WHERE h.id < :id AND h.orderId = :orderId AND (h.operateId = :operateId1 OR h.operateId = :operateId2 OR h.operateId = :operateId3) ORDER BY h.id DESC")
            ->setParameter(":orderId", $Order->getId())
            ->setParameter(":operateId1", '1Auth')
            ->setParameter(":operateId2", '1Gathering')
            ->setParameter(":operateId3", '1ReAuth')
            ->setParameter(":id", $history->getId())
            ->setMaxResults(1);
        $kaiinHistory = $q->getResult();
        $kaiinHistory = $kaiinHistory[0];
        $kaiinData = json_decode($kaiinHistory->getBody(), 1);
        
        if (array_key_exists('KaiinId', $kaiinData)) {
            $Search['KaiinId'] = $kaiinData['KaiinId'];
        }
        
        $KaiinPass = null;
        
        if ($Search['KaiinId'] && $Order->getCustomer()) {
            list($KaiinId, $KaiinPass) = $this->util->getNewKaiin($this->memCardIdRepository, $Order->getCustomer(), $this->eccubeConfig->get('eccube_auth_magic'));
        }
        
        $Search['KaiinPass'] = $KaiinPass;
        
        $httpResponse = $this->httpSend->sendData($sendUrl, $Search, $Order->getId(), true);
        if ($httpResponse) {
            return $this->DeCodeSearchResponse($httpResponse, $Order->getId());
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
    }
    
    /**
     * 取引参照返信情報を解析
     * @param unknown $response_body
     * @param unknown $orderId
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Search|mixed
     */
    public function DeCodeSearchResponse($response_body, $orderId)
    {
        $RespSearch = new RespSearch(new Process());
        $this->util->deCodeRespData($RespSearch, $response_body);
        
        //返信内容をDBに記録する
        $orderPaymentHistoryRepository->addSendResponseLog($orderId, $RespSearch);
        
        if($RespSearch['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespSearch['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespSearch['ResponseCd'];
            throw new SlnShoppingException("取引の参照が失敗しました:" . $error, null, null,
                        array_merge((array)$RespSearch['ResponseCd'], (array)$arrErr));
        }
        
        return $RespSearch;
    }
    
    /**
     * 再オーソリ処理を行う
     * @param \Eccube\Entity\Order $Order
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param OrderPaymentHistory $history
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\ReAuth|mixed
     */
    public function ReAuth(
        \Eccube\Entity\Order $Order, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config, 
        OrderPaymentHistory $history)
    {
        $sendUrl = $config->getCreditConnectionPlace1();
        
        if (!strlen($sendUrl)) {
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください");
        }
        
        $body = $history->getBody();
        $data = json_decode($body, 1);
        
        $ReAuth = new ReAuth(new Process());
        $this->util->setActionBasic($ReAuth, $config);
        $this->util->setActionMerchantFree($ReAuth, $Order);
        $ReAuth['ProcessId'] = $history->getProcessId();
        $ReAuth['ProcessPass'] = $data['ProcessPass'];
        
        /* @var $kaiinHistory \Plugin\SlnPayment42\Entity\OrderPaymentHistory */
        $q = $this->entityManager->createQuery("SELECT h from \Plugin\SlnPayment42\Entity\OrderPaymentHistory h
            WHERE h.id < :id AND h.orderId = :orderId AND (h.operateId = :operateId1 OR h.operateId = :operateId2 OR h.operateId = :operateId3) AND h.requestFlg = 1 ORDER BY h.id DESC")
            ->setParameter(":orderId", $Order->getId())
            ->setParameter(":operateId1", '1Auth')
            ->setParameter(":operateId2", '1Gathering')
            ->setParameter(":operateId3", '1ReAuth')
            ->setParameter(":id", $history->getId())
            ->setMaxResults(1);
        $kaiinHistory = $q->getResult();
        $kaiinHistory = $kaiinHistory[0];
        $kaiinData = json_decode($kaiinHistory->getBody(), 1);
        
        if (array_key_exists('KaiinId', $kaiinData)) {
            $ReAuth['KaiinId'] = $kaiinData['KaiinId'];
        }
        
        $KaiinPass = null;
        
        if ($ReAuth['KaiinId'] && $Order->getCustomer()) {
            if ($Order->getCustomer()->getStatus()->getId() != CustomerStatus::WITHDRAWING) { // 退会済みの場合は後続処理を行わせない
                list($KaiinId, $KaiinPass) = $this->util->getNewKaiin($this->memCardIdRepository, $Order->getCustomer(), $this->eccubeConfig->get('eccube_auth_magic'));
            }
        }
        
        $ReAuth['KaiinPass'] = $KaiinPass;
        if ($kaiinHistory->getOperateId() == '1Gathering') {
            $ReAuth['SalesDate'] = date("Ymd");
        }
        $ReAuth['Amount'] = floor($Order->getPaymentTotal());
        
        $httpResponse = $this->httpSend->sendData($sendUrl, $ReAuth, $Order->getId(), true);
        if ($httpResponse) {
            return $this->DeCodeReAuthResponse($httpResponse, $Order->getId());
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
    }
    
    /**
     * 再オーソリ返信情報を解析する
     * @param unknown $response_body
     * @param unknown $orderId
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\ReAuth|mixed
     */
    public function DeCodeReAuthResponse($response_body, $orderId)
    {
        $RespReAuth = new RespReAuth(new Process());
        $this->util->deCodeRespData($RespReAuth, $response_body);
        
        //返信内容をDBに記録する
        $this->orderPaymentHistoryRepository->addSendResponseLog($orderId, $RespReAuth);
        
        if($RespReAuth['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespReAuth['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespReAuth['ResponseCd'];
            throw new SlnShoppingException("再オーソリに失敗しました:" . $error, null, null,
                        array_merge((array)$RespReAuth['ResponseCd'], (array)$arrErr));
        }
        
        return $RespReAuth;
    }
    
    /**
     * 3D決済
     * @param \Eccube\Entity\Order $Order
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param Master $master
     * @throws \Exception
     */
    public function ThreeDAuth(
        \Eccube\Entity\Order $Order,
        \Plugin\SlnPayment42\Entity\ConfigSubData $config,
        ThreeDMaster $master)
    {
    
        $sendUrl = $config->getCreditConnectionPlace7();
    
        if (!strlen($sendUrl)) {
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください");
        }
    
        $Auth = new ThreeDAuth($master);
        $this->util->setActionBasic($Auth, $config);
        $this->util->setActionMerchantFree($Auth, $Order);
        $Auth['TenantId'] = $config->getTenantId();
        $Auth['Amount'] = floor($Order->getPaymentTotal());
        $Auth['RedirectUrl'] = $this->util->generateUrl('sln_3d_card', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $Auth['PostUrl'] = $this->util->generateUrl('sln_3d_card_post', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $Auth['ProcNo'] = "0000000";
        $Auth['OperateId'] = "1Auth";

        // log_debugはデバッグ時以外コメントアウトすること
        // log_debug('$Auth: '. print_r($Auth, true));
    
        $cryptAES = new CryptAES();
        $cryptAES->setKey($config->getCreditAesKey());
        $cryptAES->setIv($config->getCreditAesIv());
    
        $encryptValue = $this->util->aesEnCode($Auth, $cryptAES);
        
        $this->util->addCardNotice("redirect_url: {$sendUrl} send_data:" . json_encode($this->util->logDataReset($Auth->getPostData())));
        
        $this->orderPaymentHistoryRepository->addSendRequestLog($Order->getId(), $Auth);
    
        return sprintf('<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
                <html>
                <head>
                <title></title>
                </head>
                <body onload="javascript:document.forms[\'redirectForm\'].submit();">
                	<form action="%s" method="post" id="redirectForm">
                		<input type="hidden" name="MerchantId" value="%s" />
                		<input type="hidden" name="EncryptValue" value="%s" />
                	</form>
                </body>
                </html>', $sendUrl, $config->getMerchantId(), $encryptValue);
    }
    
    /**
     * 3D決済
     * @param \Eccube\Entity\Order $Order
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param Master $master
     * @throws \Exception
     */
    public function ThreeDGathering(
        \Eccube\Entity\Order $Order,
        \Plugin\SlnPayment42\Entity\ConfigSubData $config,
        ThreeDMaster $master)
    {
        $sendUrl = $config->getCreditConnectionPlace7();
    
        if (!strlen($sendUrl)) {
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください");
        }
    
        $Auth = new ThreeDGathering($master);
        $this->util->setActionBasic($Auth, $config);
        $this->util->setActionMerchantFree($Auth, $Order);
        $Auth['TenantId'] = $config->getTenantId();
        $Auth['Amount'] = floor($Order->getPaymentTotal());
        
        $Auth['RedirectUrl'] = $this->util->generateUrl('sln_3d_card', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $Auth['PostUrl'] = $this->util->generateUrl('sln_3d_card_post', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $Auth['ProcNo'] = "0000000";
        $Auth['OperateId'] = "1Gathering";
    
        // log_debugはデバッグ時以外コメントアウトすること
        // log_debug('$Auth: '. print_r($Auth, true));

        $cryptAES = new CryptAES();
        $cryptAES->setKey($config->getCreditAesKey());
        $cryptAES->setIv($config->getCreditAesIv());
    
        $encryptValue = $this->util->aesEnCode($Auth, $cryptAES);
    
        $this->util->addCardNotice("redirect_url: {$sendUrl} send_data:" . json_encode($this->util->logDataReset($Auth->getPostData())));
    
        $this->orderPaymentHistoryRepository->addSendRequestLog($Order->getId(), $Auth);
        
        return sprintf('<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
                <html>
                <head>
                <title></title>
                </head>
                <body onload="javascript:document.forms[\'redirectForm\'].submit();">
                	<form action="%s" method="post" id="redirectForm">
                		<input type="hidden" name="MerchantId" value="%s" />
                		<input type="hidden" name="EncryptValue" value="%s" />
                	</form>
                </body>
                </html>', $sendUrl, $config->getMerchantId(), $encryptValue);
    }
    
    /**
     * カード与信返信情報を解析する
     * @param unknown $response_body
     * @param unknown $orderId
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response\Auth|mixed
     */
    public function DeCodeThreeDResponse($response_body, $orderId, $func = '')
    {
        $config = $this->configRepository->getConfig();
        
        $cryptAES = new CryptAES();
        $cryptAES->setKey($config->getCreditAesKey());
        $cryptAES->setIv($config->getCreditAesIv());
        
        $reData = $this->util->aesDeCode($response_body, $cryptAES);
        
        $this->util->addCardNotice("response_post_data:({$func})" . json_encode($reData));
        
        if ($reData['OperateId'] == "1Auth") {
            $RespAuth = new RespThreeDAuth(new ThreeDMaster());
        } else {
            $RespAuth = new RespThreeDGathering(new ThreeDMaster());
        }
        
        foreach ($reData as $key => $value) {
            $RespAuth[$key] = $value;
        }
    
        //返信内容をDBに記録する
        if (!$orderId) {
            $Order = $this->orderRepository->findOneBy(array('id' => $RespAuth['MerchantFree1']));
            if ($Order->getId()) {
                $orderId = $Order->getId();
            } else {
                throw new SlnShoppingException("受注は存在していません。({$RespAuth['MerchantFree1']})", null, null, array(), array());
            }
        }
        
        $this->orderPaymentHistoryRepository->addSendResponseLog($orderId, $RespAuth);
        
        $errorMess = null;
        
        if ($RespAuth['ResponseCd'] != "OK") {
            switch ($RespAuth->getContent()->getSecureResultCode()) {
                case 1:
                    $errorMess = "3D セキュア認証前にエラーが発生したため 3D セキュア認証未実施です。";
                    break;
                case 2://3D　パスワード未設定
                    $errorMess = "お客様カードは 3D セキュアパスワードが未設定のため 3D セキュア認証未実施です。カード会社のセキュアパスワードを設定の上、再度ご購入をお願いいたします。";
                    break;
                case 3://カード発行会社未対応
                    $errorMess = "カード発行会社が 3D セキュアに未対応のため 3D セキュア認証未実施です。";
                    break;
                case 4://アテンプト
                    $errorMess = "3D セキュア認証が正常に完了しませんでした。";
                    break;
                case 8://認証システムメンテナンス中
                    $errorMess = "認証システムがメンテナンス中のため 3D セキュア認証未実施です。後ほど再購入をお願いいたします。";
                    break;
                case 9://認証システムエラー
                    $errorMess = "認証システムでエラーが発生したため 3D セキュア認証未実施です。";
                    break;
                default:
            }   
        }
        
        if ($errorMess) {
            $arrErr = $this->util->reErrorDecode($RespAuth['ResponseCd']);
            throw new SlnShoppingException("カードの与信処理が失敗しました:" . $errorMess, null, null,
                array_merge((array)$RespAuth['ResponseCd'], (array)$arrErr),
                $orderId
            );
        }
        
        if($RespAuth['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespAuth['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespAuth['ResponseCd'];
            throw new SlnShoppingException("カードの与信処理が失敗しました:" . $error, null, null,
                array_merge((array)$RespAuth['ResponseCd'], (array)$arrErr),
                $orderId
            );
        }
    
        return $RespAuth;
    }
}