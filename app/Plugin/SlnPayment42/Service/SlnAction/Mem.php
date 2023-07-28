<?php

namespace Plugin\SlnPayment42\Service\SlnAction;

use Eccube\Common\EccubeConfig;
use Plugin\SlnPayment42\Repository\MemCardIdRepository;
use Plugin\SlnPayment42\Repository\PluginConfigRepository;
use Plugin\SlnPayment42\Service\Util;
use Plugin\SlnPayment42\Service\SlnAction\HttpSend;
use Plugin\SlnPayment42\Service\SlnContent\Credit\Member;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Request\MemAdd;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemAdd as RespMemAdd;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Request\MemChg;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemChg as RespMemChg;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Request\MemInval;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemInval as RespMemInval;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Request\MemRef;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemRef as RespMemRef;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Request\MemRefM;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemRefM as RespMemRefM;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Request\MemUnInval;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemUnInval as RespMemUnInval;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Request\MemDel;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemDel as RespMemDel;
use Plugin\SlnPayment42\Exception\SlnShoppingException;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Request\ThreeDMemAdd;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\ThreeDMemAdd as RespThreeDMemAdd;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Request\ThreeDMemChg;
use Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\ThreeDMemChg as RespThreeDMemChg;
use Plugin\SlnPayment42\Service\CryptAES;
use Plugin\SlnPayment42\Service\SlnContent\Credit\ThreeDMember;

/**
 * 会員取引
 */
class Mem
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var Util
     */
    protected $util;

    /**
     * @var HttpSend
     */
    protected $httpSend;

    /**
     * @var MemCardIdRepository
     */
    private $memCardIdRepository;

    /**
     * @var PluginConfigRepository
     */
    protected $configRepository;

    public function __construct(
        EccubeConfig $eccubeConfig,
        Util $util,
        HttpSend $httpSend,
        MemCardIdRepository $memCardIdRepository,
        PluginConfigRepository $configRepository
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->util = $util;
        $this->httpSend = $httpSend;
        $this->memCardIdRepository = $memCardIdRepository;
        $this->configRepository = $configRepository;
    }

    /**
     * 会員新規登録を行う
     * @param \Eccube\Entity\Customer $Customer
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param Member $member
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemAdd
     */
    public function MemAdd(
        \Eccube\Entity\Customer $Customer, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config, 
        Member $member)
    {
        $sendUrl = $config->getCreditConnectionPlace2();
        
        if (!strlen($sendUrl)) {
            throw new \Exception("会員情報の取引接続先が設定されていません。");
        }
        
        $MemAdd = new MemAdd($member);
        $this->util->setActionBasic($MemAdd, $config);
        $this->util->setActionNotOrderMerchantFree($MemAdd, $Customer);
        $MemAdd['TenantId'] = $config->getTenantId();
        $this->util->setActionNewKaiin($this->memCardIdRepository, $MemAdd, $Customer, $this->eccubeConfig->get('eccube_auth_magic'));
        
        $httpResponse = $this->httpSend->sendData($sendUrl, $MemAdd, null);
        if ($httpResponse) {
            return $this->DeCodeMemAddResponse($httpResponse);
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
    }
    
    /**
     * 会員新規登録返信情報を解析する
     * @param unknown $response_body
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemAdd
     */
    public function DeCodeMemAddResponse($response_body)
    {
        $RespMemAdd = new RespMemAdd(new Member());
        $this->util->deCodeRespData($RespMemAdd, $response_body);
        
        //返信内容をDBに記録する
        //$this->orderPaymentHistoryRepository->addSendResponseLog(null, $RespMemAdd);
        
        if($RespMemAdd['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespMemAdd['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespMemAdd['ResponseCd'];
            throw new SlnShoppingException("クレジットカードの登録に失敗しました:" . $error, null, null,
                        array_merge((array)$RespMemAdd['ResponseCd'], (array)$arrErr));
        }
        
        return $RespMemAdd;
    }
    
    /**
     * 会員変更を行う
     * @param \Eccube\Entity\Customer $Customer
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param Member $member
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemAdd
     */
    public function MemChg(
        \Eccube\Entity\Customer $Customer, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config, 
        Member $member)
    {
        $sendUrl = $config->getCreditConnectionPlace2();
        
        if (!strlen($sendUrl)) {
            throw new \Exception("会員情報の取引接続先が設定されていません。");
        }
        
        $MemChg = new MemChg($member);
        $this->util->setActionBasic($MemChg, $config);
        $this->util->setActionNotOrderMerchantFree($MemChg, $Customer);
        $MemChg['TenantId'] = $config->getTenantId();
        $this->util->setActionNewKaiin($this->memCardIdRepository, $MemChg, $Customer, $this->eccubeConfig->get('eccube_auth_magic'));
        
        $httpResponse = $this->httpSend->sendData($sendUrl, $MemChg, null);
        if ($httpResponse) {
            return $this->DeCodeMemAddResponse($httpResponse);
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
    }
    
    /**
     * 会員更新返信情報を解析する
     * @param unknown $response_body
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemChg
     */
    public function DeCodeMemChgResponse($response_body)
    {
        $RespMemChg = new RespMemChg(new Member());
        $this->util->deCodeRespData($RespMemChg, $response_body);
        
        //返信内容をDBに記録する
        //$this->orderPaymentHistoryRepository->addSendResponseLog(null, $RespMemChg);
        
        if($RespMemChg['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespMemChg['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespMemChg['ResponseCd'];
            throw new SlnShoppingException("会員の更新に失敗しました:" . $error, null, null,
                        array_merge((array)$RespMemChg['ResponseCd'], (array)$arrErr));
        }
        
        return $RespMemChg;
    }
    
    /**
     * 会員無効処理を行う
     * @param \Eccube\Entity\Customer $Customer
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemInval
     */
    public function MemInval(
        \Eccube\Entity\Customer $Customer, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config)
    {
        $sendUrl = $config->getCreditConnectionPlace2();
        
        if (!strlen($sendUrl)) {
            throw new \Exception("会員情報の取引接続先が設定されていません。");
        }
        
        $MemInval = new MemInval(new Member());
        $this->util->setActionBasic($MemInval, $config);
        $this->util->setActionNotOrderMerchantFree($MemInval, $Customer);
        $MemInval['TenantId'] = $config->getTenantId();
        $this->util->setActionNewKaiin($this->memCardIdRepository, $MemInval, $Customer, $this->eccubeConfig->get('eccube_auth_magic'));
        
        $httpResponse = $this->httpSend->sendData($sendUrl, $MemInval, null);
        if ($httpResponse) {
            $reData = $this->DeCodeMemInvalResponse($httpResponse);
            $this->util->delKaiin($this->memCardIdRepository, $Customer);
            return $reData;
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
    }
    
    /**
     * 会員無効返信情報を解析する
     * @param unknown $response_body
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemInval
     */
    public function DeCodeMemInvalResponse($response_body)
    {
        $RespMemInval = new RespMemInval(new Member());
        $this->util->deCodeRespData($RespMemInval, $response_body);
        
        //返信内容をDBに記録する
        //$this->orderPaymentHistoryRepository->addSendResponseLog(null, $RespMemInval);
        
        if($RespMemInval['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespMemInval['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespMemInval['ResponseCd'];
            throw new SlnShoppingException("会員情報の削除が失敗しました:" . $error, null, null,
                        array_merge((array)$RespMemInval['ResponseCd'], (array)$arrErr));
        }
        
        return $RespMemInval;
    }
    
    /**
     * 会員照会を行う
     * @param \Eccube\Entity\Customer $Customer
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemRef
     */
    public function MemRef(
        \Eccube\Entity\Customer $Customer, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config)
    {
        $sendUrl = $config->getCreditConnectionPlace2();
        
        if (!strlen($sendUrl)) {
            throw new \Exception("会員情報の取引接続先が設定されていません。");
        }
        
        $MemRef = new MemRefM(new Member());
        $this->util->setActionBasic($MemRef, $config);
        $this->util->setActionNotOrderMerchantFree($MemRef, $Customer);
        $MemRef['TenantId'] = $config->getTenantId();
        $this->util->setActionNewKaiin($this->memCardIdRepository, $MemRef, $Customer, $this->eccubeConfig->get('eccube_auth_magic'));
        
        $httpResponse = $this->httpSend->sendData($sendUrl, $MemRef, null);
        if ($httpResponse) {
            return $this->DeCodeMemRefResponse($httpResponse);
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
    }
    
    /**
     * 会員参照返信情報を解析する
     * @param unknown $response_body
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemRef
     */
    public function DeCodeMemRefResponse($response_body)
    {
        $RespMemRef = new RespMemRefM(new Member());
        $this->util->deCodeRespData($RespMemRef, $response_body);
        
        //返信内容をDBに記録する
        //$this->orderPaymentHistoryRepository->addSendResponseLog(null, $RespMemRef);
        
        if($RespMemRef['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespMemRef['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespMemRef['ResponseCd'];
            throw new SlnShoppingException("会員情報の参照が失敗しました:" . $error, null, null,
                        array_merge((array)$RespMemRef['ResponseCd'], (array)$arrErr));
        }
        
        return $RespMemRef;
    }
    
    /**
     * 会員照会を行う
     * @param \Eccube\Entity\Customer $Customer
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param unknown $KaiinId
     * @param unknown $KaiinPass
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemRef
     */
    public function MemRefM(
        \Eccube\Entity\Customer $Customer, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config)
    {
        $sendUrl = $config->getCreditConnectionPlace2();
    
        if (!strlen($sendUrl)) {
            throw new \Exception("会員情報の取引接続先が設定されていません。");
        }
    
        $MemRefM = new MemRefM(new Member());
        $this->util->setActionBasic($MemRefM, $config);
        $this->util->setActionNotOrderMerchantFree($MemRefM, $Customer);
        $MemRefM['TenantId'] = $config->getTenantId();
        $this->util->setActionNewKaiin($this->memCardIdRepository, $MemRefM, $Customer, $this->eccubeConfig->get('eccube_auth_magic'));
    
        $httpResponse = $this->httpSend->sendData($sendUrl, $MemRefM, null);
        if ($httpResponse) {
            return $this->DeCodeMemRefMResponse($httpResponse);
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
    }
    
    /**
     * 会員参照返信情報を解析する
     * @param unknown $response_body
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemRef
     */
    public function DeCodeMemRefMResponse($response_body)
    {
        $RespMemRefM = new RespMemRefM(new Member());
        $this->util->deCodeRespData($RespMemRefM, $response_body);
    
        //返信内容をDBに記録する
        //$this->orderPaymentHistoryRepository->addSendResponseLog(null, $RespMemRefM);
    
        if($RespMemRefM['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespMemRefM['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespMemRefM['ResponseCd'];
            throw new SlnShoppingException("会員情報の参照が失敗しました:" . $error, null, null,
                        array_merge((array)$RespMemRefM['ResponseCd'], (array)$arrErr));
        }
    
        return $RespMemRefM;
    }
    
    /**
     * 会員無効解除を行う
     * @param \Eccube\Entity\Customer $Customer
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param unknown $KaiinId
     * @param unknown $KaiinPass
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemUnInval
     */
    public function MemUnInval(
        \Eccube\Entity\Customer $Customer, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config)
    {
        $sendUrl = $config->getCreditConnectionPlace2();
        
        if (!strlen($sendUrl)) {
            throw new \Exception("会員情報の取引接続先が設定されていません。");
        }
        
        $MemUnInval = new MemUnInval(new Member());
        $this->util->setActionBasic($MemUnInval, $config);
        $this->util->setActionNotOrderMerchantFree($MemUnInval, $Customer);
        $MemUnInval['TenantId'] = $config->getTenantId();
        $this->util->setActionNewKaiin($this->memCardIdRepository, $MemUnInval, $Customer, $this->eccubeConfig->get('eccube_auth_magic'));
        
        $httpResponse = $this->httpSend->sendData($sendUrl, $MemUnInval, null);
        if ($httpResponse) {
            return $this->DeCodeMemUnInvalResponse($httpResponse);
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
    }
    
    /**
     * 会員無効解除返信情報を解析する
     * @param unknown $response_body
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemUnInval
     */
    public function DeCodeMemUnInvalResponse($response_body)
    {
        $RespMemUnInval = new RespMemUnInval(new Member());
        $this->util->deCodeRespData($RespMemUnInval, $response_body);
        
        //返信内容をDBに記録する
        //$this->orderPaymentHistoryRepository->addSendResponseLog(null, $RespMemUnInval);
        
        if($RespMemUnInval['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespMemUnInval['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespMemUnInval['ResponseCd'];
            throw new SlnShoppingException("会員情報の取引接続先が設定していません。:" . $error, null, null,
                        array_merge((array)$RespMemUnInval['ResponseCd'], (array)$arrErr));
        }
        
        return $RespMemUnInval;
    }
    
    /**
     * 会員削除処理を行う
     * @param \Eccube\Entity\Customer $Customer
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemDel
     */
    public function MemDel(
        \Eccube\Entity\Customer $Customer, 
        \Plugin\SlnPayment42\Entity\ConfigSubData $config)
    {
        $sendUrl = $config->getCreditConnectionPlace2();
        
        if (!strlen($sendUrl)) {
            throw new \Exception("会員情報の取引接続先が設定されていません。");
        }
        
        $MemDel = new MemDel(new Member());
        $this->util->setActionBasic($MemDel, $config);
        $this->util->setActionNotOrderMerchantFree($MemDel, $Customer);
        $MemDel['TenantId'] = $config->getTenantId();
        $this->util->setActionNewKaiin($this->memCardIdRepository, $MemDel, $Customer, $this->eccubeConfig->get('eccube_auth_magic'));
        
        $httpResponse = $this->httpSend->sendData($sendUrl, $MemDel, null);
        if ($httpResponse) {
            return $this->DeCodeDelResponse($httpResponse);
        } else {//通信エラーカードページに戻してエラー情報を表示する
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
    }
    
    /**
     * 会員削除返信情報を解析する
     * @param unknown $response_body
     * @throws \Exception
     * @return \Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response\MemDel
     */
    public function DeCodeDelResponse($response_body)
    {
        $RespMemDel = new RespMemDel(new Member());
        $this->util->deCodeRespData($RespMemDel, $response_body);
    
        //返信内容をDBに記録する
        //$this->orderPaymentHistoryRepository->addSendResponseLog(null, $RespMemDel);
    
        if($RespMemDel['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespMemDel['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespMemDel['ResponseCd'];
            throw new SlnShoppingException("会員の無効処理が失敗しました:" . $error, null, null,
                        array_merge((array)$RespMemDel['ResponseCd'], (array)$arrErr));
        }
    
        return $RespMemDel;
    }
    
    /**
     * 会員新規登録を行う
     * @param \Eccube\Entity\Customer $Customer
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param ThreeDMember $member
     * @throws \Exception
     * @return string
     */
    public function ThreeMemAdd(
        \Eccube\Entity\Customer $Customer,
        \Plugin\SlnPayment42\Entity\ConfigSubData $config,
        ThreeDMember $member, $RedirectUrl)
    {
        $sendUrl = $config->getCreditConnectionPlace7();
        
        if (!strlen($sendUrl)) {
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
    
        $MemAdd = new ThreeDMemAdd($member);
        $this->util->setActionBasic($MemAdd, $config);
        $this->util->setActionNotOrderMerchantFree($MemAdd, $Customer);
        $MemAdd['TenantId'] = $config->getTenantId();
        $this->util->setActionNewKaiin($this->memCardIdRepository, $MemAdd, $Customer, $this->eccubeConfig->get('eccube_auth_magic'));
        
        $MemAdd['RedirectUrl'] = $RedirectUrl;
        $MemAdd['ProcNo'] = "0000000";
        $MemAdd['OperateId'] = "4MemAdd";
  
        $cryptAES = new CryptAES();
        $cryptAES->setKey($config->getCreditAesKey());
        $cryptAES->setIv($config->getCreditAesIv());
        
        $encryptValue = $this->util->aesEnCode($MemAdd, $cryptAES);
        
        $this->util->addCardNotice("redirect_url: {$sendUrl} send_data:" . json_encode($this->util->logDataReset($MemAdd->getPostData())));
        
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
     * 会員新規登録を行う
     * @param \Eccube\Entity\Customer $Customer
     * @param \Plugin\SlnPayment42\Entity\ConfigSubData $config
     * @param ThreeDMember $member
     * @throws \Exception
     * @return string
     */
    public function ThreeMemChg(
        \Eccube\Entity\Customer $Customer,
        \Plugin\SlnPayment42\Entity\ConfigSubData $config,
        ThreeDMember $member, $RedirectUrl)
    {
        $sendUrl = $config->getCreditConnectionPlace7();
    
        if (!strlen($sendUrl)) {
            throw new \Exception("決済モジュールの通信エラーが発生しました。詳しくは管理者にご連絡してください。");
        }
    
        $MemChg = new ThreeDMemChg($member);
        $this->util->setActionBasic($MemChg, $config);
        $this->util->setActionNotOrderMerchantFree($MemChg, $Customer);
        $MemChg['TenantId'] = $config->getTenantId();
        $this->util->setActionNewKaiin($this->memCardIdRepository, $MemChg, $Customer, $this->eccubeConfig->get('eccube_auth_magic'));
    
        $MemChg['RedirectUrl'] = $RedirectUrl;
        $MemChg['ProcNo'] = "0000000";
        $MemChg['OperateId'] = "4MemChg";
    
        $cryptAES = new CryptAES();
        $cryptAES->setKey($config->getCreditAesKey());
        $cryptAES->setIv($config->getCreditAesIv());
    
        $encryptValue = $this->util->aesEnCode($MemChg, $cryptAES);
        
        $this->util->addCardNotice("redirect_url: {$sendUrl} send_data:" . json_encode($this->util->logDataReset($MemChg->getPostData())));
    
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
     * @param unknown $response_body
     * @param string $func
     * @throws SlnShoppingException
     * @return RespThreeDMemAdd
     */
    public function DeCodeThreeDResponse($response_body, $func = '') 
    {
        $config = $this->configRepository->getConfig();
        
        $cryptAES = new CryptAES();
        $cryptAES->setKey($config->getCreditAesKey());
        $cryptAES->setIv($config->getCreditAesIv());
        
        $reData = $this->util->aesDeCode($response_body, $cryptAES);
        
        $this->util->addCardNotice("response_post_data:({$func})" . json_encode($reData));
        
        if ($reData['OperateId'] == "4MemAdd") {
            $RespMem = new RespThreeDMemAdd(new ThreeDMember());
        } else {
            $RespMem = new RespThreeDMemChg(new ThreeDMember());
        }
        
        foreach ($reData as $key => $value) {
            $RespMem[$key] = $value;
        }
        
        $errorMess = null;
        
        if ($RespMem['ResponseCd'] != "OK") {
            switch ($RespMem->getContent()->getSecureResultCode()) {
                case 1:
                    $errorMess = "3D セキュア認証前にエラーが発生したため 3D セキュア認証未実施です。";
                    break;
                case 2://3D　パスワード未設定
                    $errorMess = "お客様カードは 3D セキュアパスワードが未設定のため 3D セキュア認証未実施です。カード会社のセキュアパスワードを設定の上、再度ご購入をお願いいたします。";
                    break;
                case 3://カード発行会社未対応
                    $errorMess = "カード発行会社が 3D セキュアに未対応のため 3D セキュア認証未実施です。";
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
            $arrErr = $this->util->reErrorDecode($RespMem['ResponseCd']);
            throw new SlnShoppingException("カードの登録処理が失敗しました:" . $errorMess, null, null,
                array_merge((array)$RespMem['ResponseCd'], (array)$arrErr));
        }
        
        if($RespMem['ResponseCd'] != "OK") {
            $arrErr = $this->util->reErrorDecode($RespMem['ResponseCd']);
            $error = strlen($arrErr[0]) ? $arrErr[1] : $RespMem['ResponseCd'];
            throw new SlnShoppingException("カードの登録処理が失敗しました:" . $error, null, null,
                array_merge((array)$RespMem['ResponseCd'], (array)$arrErr));
        }
        
        return $RespMem;
        
    }
    
}