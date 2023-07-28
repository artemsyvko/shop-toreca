<?php

namespace Plugin\SlnPayment42\Entity;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\NoopWordInflector;

class ConfigSubData implements \ArrayAccess,\IteratorAggregate
{
    public function getIterator() {
        return new ConfigSubData(get_object_vars($this));
    }
    
    public function offsetExists($offset)
    {
        $inflector = new Inflector(new NoopWordInflector(), new NoopWordInflector());
        $method = $inflector->classify($offset);
    
        return method_exists($this, "get$method") || method_exists($this, "is$method");
    }
    
    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }
    
    public function offsetGet($offset)
    {
        $inflector = new Inflector(new NoopWordInflector(), new NoopWordInflector());
        $method = $inflector->classify($offset);
    
        if (method_exists($this, "get$method")) {
            return $this->{"get$method"}();
        } elseif (method_exists($this, "is$method")) {
            return $this->{"is$method"}();
        }
    }
    
    public function offsetUnset($offset)
    {
    }
    
    protected $MerchantId;
    
    protected $MerchantPass;
    
    protected $TenantId;
    
    protected $creditConnectionDestination;

    protected $threedConnectionDestination;

    protected $cvsConnectionDestination;

    protected $creditConnectionPlace1;
    
    protected $creditConnectionPlace2;
    
    protected $creditConnectionPlace6;
    
    protected $tokenNinsyoCode;
    
    protected $creditConnectionPlace7;
    
    protected $creditAesKey;
    
    protected $creditAesIv;
    
    protected $payKbnKaisu;
    
    protected $SecCd;
    
    protected $attestationAssistance;
    
    protected $OperateId;
    
    protected $memberRegist;
    
    protected $quickAccounts;

    protected $creditConnectionPlace5;
    
    protected $creditConnectionPlace3;
    
    protected $OnlinePaymentMethod;
    
    protected $Free1;
    
    protected $Free2;
    
    protected $Free3;
    
    protected $Free4;
    
    protected $Free5;
    
    protected $Free6;
    
    protected $Free7;
    
    protected $Comment;
    
    protected $Free8;
    
    protected $Free9;
    
    protected $Free10;
    
    protected $Free11;
    
    protected $Free12;
    
    protected $Free13;
    
    protected $Free14;
    
    protected $Free15;
    
    protected $Free16;
    
    protected $Free17;
    
    protected $Free18;
    
    protected $Free19;
    
    protected $Title;
        
    protected $cvsOrderMailId;

    protected $isSendMail;
    
    protected $threedPay;
    
    protected $cardOrderPreEnd;
    
    public function ToSaveData()
    {
        return json_encode(get_object_vars($this));
    }
    
    public function ToData($str) 
    {
        $configData = json_decode($str, 1);
        foreach ($configData as $key => $value) {
            $this->{$key} = $value;
        }
    }
    
    public function getCvsOrderMailId()
    {
        return $this->cvsOrderMailId;
    }
    
    public function setCvsOrderMailId($mailId)
    {
        $this->cvsOrderMailId = $mailId;
        return $this;
    }
    
    public function getMerchantId()
    {
        return $this->MerchantId;
    }
    
    public function setMerchantId($MerchantId)
    {
        $this->MerchantId = $MerchantId;
        return $this;
    }
    
    public function getMerchantPass()
    {
        return $this->MerchantPass;
    }
    
    public function setMerchantPass($MerchantPass)
    {
        $this->MerchantPass = $MerchantPass;
        return $this;
    }
    
    public function getTenantId()
    {
        return $this->TenantId;
    }
    
    public function setTenantId($TenantId)
    {
        $this->TenantId = $TenantId;
        return $this;
    }

    public function getCreditConnectionDestination()
    {
        return $this->creditConnectionDestination;
    }

    public function setCreditConnectionDestination($creditConnectionDestination)
    {
        $this->creditConnectionDestination = $creditConnectionDestination;
    }

    public function getThreedConnectionDestination()
    {
        return $this->threedConnectionDestination;
    }

    public function setThreedConnectionDestination($threedConnectionDestination)
    {
        $this->threedConnectionDestination = $threedConnectionDestination;
    }

    public function getCvsConnectionDestination()
    {
        return $this->cvsConnectionDestination;
    }

    public function setCvsConnectionDestination($cvsConnectionDestination)
    {
        $this->cvsConnectionDestination = $cvsConnectionDestination;
    }
    
    public function getCreditConnectionPlace1()
    {
        return $this->creditConnectionPlace1;
    }
    
    public function setCreditConnectionPlace1($credit_connection_place1)
    {
        $this->creditConnectionPlace1 = $credit_connection_place1;
        return $this;
    }

    public function getCreditConnectionPlace2()
    {
        return $this->creditConnectionPlace2;
    }
    
    public function setCreditConnectionPlace2($credit_connection_place2)
    {
        $this->creditConnectionPlace2 = $credit_connection_place2;
        return $this;
    }
    
    public function getCreditConnectionPlace6()
    {
        return $this->creditConnectionPlace6;
    }
    
    public function setCreditConnectionPlace6($creditConnectionPlace6)
    {
        $this->creditConnectionPlace6 = $creditConnectionPlace6;
        return $this;
    }
    
    public function getTokenNinsyoCode()
    {
        return $this->tokenNinsyoCode;
    }
    
    public function setTokenNinsyoCode($tokenNinsyoCode)
    {
        $this->tokenNinsyoCode = $tokenNinsyoCode;
        return $this;
    }
    
    public function getPayKbnKaisu()
    {
        return $this->payKbnKaisu;
    }
    
    public function setPayKbnKaisu($payKbnKaisu)
    {
        $this->payKbnKaisu = $payKbnKaisu;
        return $this;
    }
    
    public function getSecCd()
    {
        return $this->SecCd;
    }
    
    public function setSecCd($SecCd)
    {
        $this->SecCd = $SecCd;
        return $this;
    }
    
    public function getAttestationAssistance()
    {
        return $this->attestationAssistance;
    }
    
    public function setAttestationAssistance($attestation_assistance)
    {
        $this->attestationAssistance = $attestation_assistance;
        return $this;
    }
    
    public function getOperateId()
    {
        return $this->OperateId;
    }
    
    public function setOperateId($OperateId)
    {
        $this->OperateId = $OperateId;
        return $this;
    }
    
    public function getMemberRegist()
    {
        return $this->memberRegist;
    }
    
    public function setMemberRegist($member_regist)
    {
        $this->memberRegist = $member_regist;
        return $this;
    }
    
    public function getQuickAccounts()
    {
        return $this->quickAccounts;
    }
    
    public function setQuickAccounts($quick_accounts)
    {
        $this->quickAccounts = $quick_accounts;
        return $this;
    }
    
    public function getCreditConnectionPlace5()
    {
        return $this->creditConnectionPlace5;
    }
    
    public function setCreditConnectionPlace5($credit_connection_place5)
    {
        $this->creditConnectionPlace5 = $credit_connection_place5;
        return $this;
    }
    
    public function getCreditConnectionPlace3()
    {
        return $this->creditConnectionPlace3;
    }
    
    public function setCreditConnectionPlace3($credit_connection_place3)
    {
        $this->creditConnectionPlace3 = $credit_connection_place3;
        return $this;
    }
    
    public function getOnlinePaymentMethod()
    {
        return $this->OnlinePaymentMethod;
    }
    
    public function setOnlinePaymentMethod($OnlinePaymentMethod)
    {
        $this->OnlinePaymentMethod = $OnlinePaymentMethod;
        return $this;
    }
    
    public function getFree1()
    {
        return $this->Free1;
    }
    
    public function setFree1($Free1)
    {
        $this->Free1 = $Free1;
        return $this;
    }
    
    public function getFree2()
    {
        return $this->Free2;
    }
    
    public function setFree2($Free2)
    {
        $this->Free2 = $Free2;
        return $this;
    }
    
    public function getFree3()
    {
        return $this->Free3;
    }
    
    public function setFree3($Free3)
    {
        $this->Free3 = $Free3;
        return $this;
    }
    
    public function getFree4()
    {
        return $this->Free4;
    }
    
    public function setFree4($Free4)
    {
        $this->Free4 = $Free4;
        return $this;
    }
    
    public function getFree5()
    {
        return $this->Free5;
    }
    
    public function setFree5($Free5)
    {
        $this->Free5 = $Free5;
        return $this;
    }
    
    public function getFree6()
    {
        return $this->Free6;
    }
    
    public function setFree6($Free6)
    {
        $this->Free6 = $Free6;
        return $this;
    }
    
    public function getFree7()
    {
        return $this->Free7;
    }
    
    public function setFree7($Free7)
    {
        $this->Free7 = $Free7;
        return $this;
    }
    
    public function getComment()
    {
        return $this->Comment;
    }
    
    public function setComment($Comment)
    {
        $this->Comment = $Comment;
        return $this;
    }
    
    public function getFree8()
    {
        return $this->Free8;
    }
    
    public function setFree8($Free8)
    {
        $this->Free8 = $Free8;
        return $this;
    }
    
    public function getFree9()
    {
        return $this->Free9;
    }
    
    public function setFree9($Free9)
    {
        $this->Free9 = $Free9;
        return $this;
    }
    
    public function getFree10()
    {
        return $this->Free10;
    }
    
    public function setFree10($Free10)
    {
        $this->Free10 = $Free10;
        return $this;
    }
    
    public function getFree11()
    {
        return $this->Free11;
    }
    
    public function setFree11($Free11)
    {
        $this->Free11 = $Free11;
        return $this;
    }
    
    public function getFree12()
    {
        return $this->Free12;
    }
    
    public function setFree12($Free12)
    {
        $this->Free12 = $Free12;
        return $this;
    }
    
    public function getFree13()
    {
        return $this->Free13;
    }
    
    public function setFree13($Free13)
    {
        $this->Free13 = $Free13;
        return $this;
    }
    
    public function getFree14()
    {
        return $this->Free14;
    }
    
    public function setFree14($Free14)
    {
        $this->Free14 = $Free14;
        return $this;
    }
    
    public function getFree15()
    {
        return $this->Free15;
    }
    
    public function setFree15($Free15)
    {
        $this->Free15 = $Free15;
        return $this;
    }
    
    public function getFree16()
    {
        return $this->Free16;
    }
    
    public function setFree16($Free16)
    {
        $this->Free16 = $Free16;
        return $this;
    }
    
    public function getFree17()
    {
        return $this->Free17;
    }
    
    public function setFree17($Free17)
    {
        $this->Free17 = $Free17;
        return $this;
    }
    
    public function getFree18()
    {
        return $this->Free18;
    }
    
    public function setFree18($Free18)
    {
        $this->Free18 = $Free18;
        return $this;
    }
    
    public function getFree19()
    {
        return $this->Free19;
    }
    
    public function setFree19($Free19)
    {
        $this->Free19 = $Free19;
        return $this;
    }
    
    public function getTitle()
    {
        return $this->Title;
    }
    
    public function setTitle($Title)
    {
        $this->Title = $Title;
        return $this;
    }
    
    public function getIsSendMail()
    {
        return $this->isSendMail;
    }
    
    public function setIsSendMail($isSendMail)
    {
        $this->isSendMail = $isSendMail;
        return $this;
    }
    
    public function getCreditConnectionPlace7()
    {
        return $this->creditConnectionPlace7;
    }

    public function getCreditAesKey()
    {
        return $this->creditAesKey;
    }

    public function getCreditAesIv()
    {
        return $this->creditAesIv;
    }

    public function setCreditConnectionPlace7($creditConnectionPlace7)
    {
        $this->creditConnectionPlace7 = $creditConnectionPlace7;
        return $this;
    }

    public function setCreditAesKey($creditAesKey)
    {
        $this->creditAesKey = $creditAesKey;
        return $this;
    }

    public function setCreditAesIv($creditAesIv)
    {
        $this->creditAesIv = $creditAesIv;
        return $this;
    }

    public function setThreedPay($threedPay)
    {
        $this->threedPay = $threedPay;
        return $this;
    }
    
    public function getThreedPay()
    {
        return $this->threedPay;
    }
    
    public function setCardOrderPreEnd($cardOrderPreEnd)
    {
        $this->cardOrderPreEnd = $cardOrderPreEnd;
        return $this;
    }
    
    public function getCardOrderPreEnd()
    {
        return $this->cardOrderPreEnd;
    }
}