<?php

namespace Plugin\SlnPayment42\Service\SlnAction;

use Eccube\Exception\ShoppingException;
use Symfony\Component\HttpFoundation\Request;
use Plugin\SlnPayment42\Repository\OrderPaymentHistoryRepository;
use Plugin\SlnPayment42\Entity\OrderPaymentHistory;
use Plugin\SlnPayment42\SlnException;
use Plugin\SlnPayment42\Service\Util;
use Plugin\SlnPayment42\Service\SlnAction\HttpSend;
use Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Request\Add;
use Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster;
use Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Response\Add as RespAdd;
use Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Response\RefNotice;
use Plugin\SlnPayment42\Service\SlnContent\Receipt\RefProcess;
use Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Request\Ref;
use Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Response\Ref as RefRep;
use Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Request\Chg;
use Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Response\Chg as ChgRep;
use Plugin\SlnPayment42\Service\SlnContent\Receipt\ChgDelProcess;
use Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Request\Del;
use Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Response\Del as DelRep;
use Plugin\SlnPayment42\Exception\SlnShoppingException;

/**
 * オンライン収納決済取引
 */
class Cvs
{
    /**
     * @var Util
     */
    protected $util;

    /**
     * HttpSend
     */
    protected $httpSend;

    /**
     * @var OrderPaymentHistoryRepository
     */
    protected $orderPaymentHistoryRepository;

    public function __construct(
        Util $util,
        HttpSend $httpSend,
        OrderPaymentHistoryRepository $orderPaymentHistoryRepository
    ) {
        $this->util = $util;
        $this->httpSend = $httpSend;
        $this->orderPaymentHistoryRepository = $orderPaymentHistoryRepository;
    }

    /**
     * オンライン収納決済情報を送ります。
     * @param \Eccube\Entity\Order $Order
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param unknown $returnUrl
     * @throws ShoppingException
     * @return string[]|\Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Request\Add[]
     */
    public function Add(
        \Eccube\Entity\Order $Order,
        \Plugin\SlnPayment42\Entity\ConfigSubData $config,
        $returnUrl)
    {
        $sendUrl = $config->getCreditConnectionPlace5();
        $reUrl = $config->getCreditConnectionPlace3();

        if (!strlen($sendUrl)) {
            throw new ShoppingException("オンライン収納代行の取引接続先が設定されていません。");
        }
        
        if (!strlen($reUrl)) {
            throw new ShoppingException("オンライン収納代行のリダイレクト先が設定されていません。");
        }
        
        $addMaster = new Add(new AddMaster());
        $this->util->setActionBasic($addMaster, $config);
        $this->util->setActionMerchantFree($addMaster, $Order);
        $addMaster['Amount'] = floor($Order->getPaymentTotal());
        $addMaster['PayLimit'] = date("YmdHi", strtotime("+7 day"));
        $addMaster['NameKanji'] = $Order->getName01() . $Order->getName02();
        $addMaster['NameKana'] = $Order->getKana01() . $Order->getKana02();
        $addMaster['TelNo'] = $Order->getPhoneNumber();
        $addMaster['ShouhinName'] = $Order->getOrderItems()->get(0)->getProductName();
        $addMaster['Free1'] = $config->getFree1();
        $addMaster['Free2'] = $config->getFree2();
        $addMaster['Free3'] = $config->getFree3();
        $addMaster['Free4'] = $config->getFree4();
        $addMaster['Free5'] = $config->getFree5();
        $addMaster['Free6'] = $config->getFree6();
        $addMaster['Free7'] = $config->getFree7();
        $addMaster['Comment'] = $config->getComment();
        $addMaster['Free8'] = $config->getFree8();
        $addMaster['Free9'] = $config->getFree9();
        $addMaster['Free10'] = $config->getFree10();
        $addMaster['Free11'] = $config->getFree11();
        $addMaster['Free12'] = $config->getFree12();
        $addMaster['Free13'] = $config->getFree13();
        $addMaster['Free14'] = $config->getFree14();
        $addMaster['Free15'] = $config->getFree15();
        $addMaster['Free16'] = $config->getFree16();
        $addMaster['Free17'] = $config->getFree17();
        $addMaster['Free18'] = $config->getFree18();
        $addMaster['Free19'] = $config->getFree19();
        $addMaster['ReturnURL'] = $returnUrl;
        $addMaster['Title'] = $config->getTitle();
        
        $httpResponse = $this->httpSend->sendData($sendUrl, $addMaster, $Order->getId(), false);
        if ($httpResponse) {
            $RespAdd = $this->DeCodeResponse($httpResponse, $Order->getId());
            if ($RespAdd['FreeArea']) {
                return array($config->getCreditConnectionPlace3() .  sprintf("?code=%s&rkbn=1", $RespAdd['FreeArea']), $addMaster);
            } else {
                throw new ShoppingException("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。FreeAreaのレスポンスがありません。");
            }
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new ShoppingException("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
    }
    
    /**
     * 返信情報を解析する
     * @param unknown $response_body
     * @param unknown $orderId
     * @throws ShoppingException
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Response\Add|mixed
     */
    public function DeCodeResponse($response_body, $orderId)
    {
        $RespAdd = new RespAdd(new AddMaster());
        $this->util->deCodeRespData($RespAdd, $response_body);

        //返信内容をDBに記録する
        $this->orderPaymentHistoryRepository->addSendResponseLog($orderId, $RespAdd);
        
        if($RespAdd['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespAdd['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespAdd['ResponseCd'];
            throw new SlnShoppingException("購入が失敗しました:" . $error, null, null, 
                array_merge((array)$RespAdd['ResponseCd'], (array)$arrErr));
        }
        
        return $RespAdd;
    }
    
    /**
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param Request $request
     * @throws ShoppingException
     * @return \Plugin\SlnPayment42\Service\SlnContent\Basic[]|unknown[]
     */
    public function Recv(\Plugin\SlnPayment42\Entity\ConfigSubData $config, Request $request)
    {
        $all = $request->request->all();
        if ($config->getMerchantId() != $all['MerchantId']) {
            throw new ShoppingException("MerchantIdが一致していません。");
        }
        
        //受注ID存在チェック
        $orderId = $this->orderPaymentHistoryRepository->getRevOrderIdForTid($all['TransactionId']);

        if ($orderId) {
            //解析内容を返信
            $RefNotice = $this->DeCodeRequest($all, $orderId);
        } else {
            throw new ShoppingException("受注情報が存在していません。");
        }
        
        return array($orderId, $RefNotice->getContent());
    }
    
    /**
     * 受信情報を解析する
     * @param unknown $request_body
     * @param unknown $orderId
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Response\RefNotice
     */
    public function DeCodeRequest($all, $orderId)
    {
        $RefNotice = new RefNotice(new RefProcess());
        foreach ($all as $key => $value) {
            $RefNotice[$key] = $value;
        }
        
        //通信内容DBに記録する
        $this->orderPaymentHistoryRepository->addReavResponseLog($orderId, $RefNotice);
        
        return $RefNotice;
    }
    
    /**
     * 結果照会
     * @param \Eccube\Entity\Order $Order
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param OrderPaymentHistory $history
     * @throws ShoppingException
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\RefProcess
     */
    public function Ref(
        \Eccube\Entity\Order $Order, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config, 
        OrderPaymentHistory $history)
    {
        $sendUrl = $config->getCreditConnectionPlace5();
        
        if (!strlen($sendUrl)) {
            throw new ShoppingException("オンライン収納代行の取引接続先が設定されていません。");
        }
        
        $body = $history->getBody();
        $data = json_decode($body, 1);
        
        $refProcess = new Ref(new RefProcess());
        $this->util->setActionBasic($refProcess, $config);
        $this->util->setActionMerchantFree($refProcess, $Order);
        $refProcess['ProcessId'] = $history->getProcessId();
        $refProcess['ProcessPass'] = $data['ProcessPass'];
        
        $httpResponse = $this->httpSend->sendData($sendUrl, $refProcess, $Order->getId(), false);
        
        if ($httpResponse) {
            $ref = $this->DeCodeRef($httpResponse, $Order->getId());
            return $ref->getContent();
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("返信データは空です");
        }
    }
    
    /**
     * 返信情報を解析する
     * @param unknown $response_body
     * @param unknown $orderId
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Response\Ref|mixed
     */
    public function DeCodeRef($response_body, $orderId)
    {
        $RefRep = new RefRep(new RefProcess());
        $this->util->deCodeRespData($RefRep, $response_body);
    
        //返信内容をDBに記録する
        $this->orderPaymentHistoryRepository->addSendResponseLog($orderId, $RefRep);
    
        if($RefRep['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RefRep['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[2] : $RefRep['ResponseCd'];
            
            throw new SlnShoppingException($error, null, null,
                        array_merge((array)$RefRep['ResponseCd'], (array)$arrErr));
        }
    
        return $RefRep;
    }
    
    /**
     * 金額と支払い期限を変更する
     * @param \Eccube\Entity\Order $Order
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param OrderPaymentHistory $history
     * @throws ShoppingException
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\ChgDelProcess
     */
    public function Chg(
        \Eccube\Entity\Order $Order, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config, 
        OrderPaymentHistory $history)
    {
        $sendUrl = $config->getCreditConnectionPlace5();
        
        if (!strlen($sendUrl)) {
            throw new ShoppingException("オンライン収納代行の取引接続先が設定されていません。");
        }
        
        $body = $history->getBody();
        $data = json_decode($body, 1);

        $refChg = new Chg(new ChgDelProcess());
        $this->util->setActionBasic($refChg, $config);
        $this->util->setActionMerchantFree($refChg, $Order);
        $refChg['ProcessId'] = $history->getProcessId();
        $refChg['ProcessPass'] = $data['ProcessPass'];
        $refChg['PayLimit'] = date("YmdHi", strtotime("+7 day"));
        $refChg['Amount'] = floor($Order->getPaymentTotal());
        
        $httpResponse = $this->httpSend->sendData($sendUrl, $refChg, $Order->getId(), false);
        
        if ($httpResponse) {
            $ref = $this->DeCodeChg($httpResponse, $Order->getId());
            return $ref->getContent();
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("返信データは空です");
        }
    }
    
    /**
     * 金額と支払い期限を変更する
     * @param unknown $response_body
     * @param unknown $orderId
     * @throws SlnException
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Response\Chg|mixed
     */
    public function DeCodeChg($response_body, $orderId)
    {
        $ChgRep = new ChgRep(new ChgDelProcess());
        $this->util->deCodeRespData($ChgRep, $response_body);
        
        //返信内容をDBに記録する
        $this->orderPaymentHistoryRepository->addSendResponseLog($orderId, $ChgRep);
        
        if($ChgRep['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($ChgRep['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[2] : $ChgRep['ResponseCd'];
            throw new SlnShoppingException($error, null, null,
                        array_merge((array)$ChgRep['ResponseCd'], (array)$arrErr));
        }
        
        return $ChgRep;
    }
    
    /**
     * 決済を削除する
     * @param \Eccube\Entity\Order $Order
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param OrderPaymentHistory $history
     * @throws ShoppingException
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\ChgDelProcess
     */
    public function Del(
        \Eccube\Entity\Order $Order, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config, 
        OrderPaymentHistory $history)
    {
        $sendUrl = $config->getCreditConnectionPlace5();
    
        if (!strlen($sendUrl)) {
            throw new ShoppingException("オンライン収納代行の取引接続先が設定されていません。");
        }
    
        $body = $history->getBody();
        $data = json_decode($body, 1);
    
        $refDel = new Del(new ChgDelProcess());
        $this->util->setActionBasic($refDel, $config);
        $this->util->setActionMerchantFree($refDel, $Order);
        $refDel['ProcessId'] = $history->getProcessId();
        $refDel['ProcessPass'] = $data['ProcessPass'];
    
        $httpResponse = $this->httpSend->sendData($sendUrl, $refDel, $Order->getId(), false);
    
        if ($httpResponse) {
            $ref = $this->DeCodeDel($httpResponse, $Order->getId());
            return $ref->getContent();
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("返信データは空です");
        }
    }
    
    /**
     * 決済を削除する
     * @param unknown $response_body
     * @param unknown $orderId
     * @throws SlnException
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Response\Del|mixed
     */
    public function DeCodeDel($response_body, $orderId)
    {
        $DelRep = new DelRep(new ChgDelProcess());
        $this->util->deCodeRespData($DelRep, $response_body);
    
        //返信内容をDBに記録する
        $this->orderPaymentHistoryRepository->addSendResponseLog($orderId, $DelRep);
    
        if($DelRep['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($DelRep['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[2] : $DelRep['ResponseCd'];
            throw new SlnShoppingException($error, null, null,
                        array_merge((array)$DelRep['ResponseCd'], (array)$arrErr));
        }
    
        return $DelRep;
    }
}