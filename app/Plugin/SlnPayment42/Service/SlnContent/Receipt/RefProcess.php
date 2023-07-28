<?php

namespace Plugin\SlnPayment42\Service\SlnContent\Receipt;

class RefProcess extends \Plugin\SlnPayment42\Service\SlnContent\Basic
{
    public function getIterator() {
        return new RefProcess(get_object_vars($this));
    }
    
    /**
     * 状態
     * @var unknown
     */
    protected $Condition;
    
    /**
     * 支払額
     * @var unknown
     */
    protected $Amount;
    
    /**
     * 受付番号
     * @var unknown
     */
    protected $RecvNum;
    
    /**
     * 収納機関コード
     * @var unknown
     */
    protected $CvsCd;
    
    /**
     * 店舗コード
     * @var unknown
     */
    protected $TenantCd;
    
    /**
     * 入金日時
     * @var unknown
     */
    protected $NyukinDate;
    
    /**
     * 印紙フラグ
     * @var unknown
     */
    protected $StampFlag;
    
    /**
     * MD5 ハッシュ値
     * @var unknown
     */
    protected $HashCd;
    
    /**
     * メール通知利用フラグ
     * @var unknown
     */
    protected $is_payment_mail;
    
    /**
     * メール通知登録アドレス
     * @var unknown
     */
    protected $payment_mail;
    
    /**
     * 貴社登録名 
     * @var unknown
     */
    protected $MerchantName;
    
    /**
     * @return the $Condition
     */
    public function getCondition()
    {
        return $this->Condition;
    }
    
    /**
     * @return the $Amount
     */
    public function getAmount()
    {
        return $this->Amount;
    }
    
    /**
     * @return the $RecvNum
     */
    public function getRecvNum()
    {
        return $this->RecvNum;
    }
    
    /**
     * @return the $CvsCd
     */
    public function getCvsCd()
    {
        return $this->CvsCd;
    }
    
    /**
     * @return the $TenantCd
     */
    public function getTenantCd()
    {
        return $this->TenantCd;
    }
    
    /**
     * @return the $NyukinDate
     */
    public function getNyukinDate()
    {
        return $this->NyukinDate;
    }
    
    /**
     * @return the $StampFlag
     */
    public function getStampFlag()
    {
        return $this->StampFlag;
    }
    
    /**
     * @return the $HashCd
     */
    public function getHashCd()
    {
        return $this->HashCd;
    }
    
    /**
     * @return the $is_payment_mail
     */
    public function getIs_payment_mail()
    {
        return $this->is_payment_mail;
    }
    
    /**
     * @return the $payment_mail
     */
    public function getPayment_mail()
    {
        return $this->payment_mail;
    }
    
    /**
     * @return the $MerchantName
     */
    public function getMerchantName()
    {
        return $this->MerchantName;
    }
    
    /**
     * @param unknown $Condition
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\RefProcess
     */
    public function setCondition($Condition)
    {
        $this->Condition = $Condition;
        return $this;
    }
    
    /**
     * @param unknown $Amount
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\RefProcess
     */
    public function setAmount($Amount)
    {
        $this->Amount = $Amount;
        return $this;
    }
    
    /**
     * @param unknown $RecvNum
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\RefProcess
     */
    public function setRecvNum($RecvNum)
    {
        $this->RecvNum = $RecvNum;
        return $this;
    }
    
    /**
     * @param unknown $CvsCd
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\RefProcess
     */
    public function setCvsCd($CvsCd)
    {
        $this->CvsCd = $CvsCd;
        return $this;
    }
    
    /**
     * @param unknown $TenantCd
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\RefProcess
     */
    public function setTenantCd($TenantCd)
    {
        $this->TenantCd = $TenantCd;
        return $this;
    }
    
    /**
     * @param unknown $NyukinDate
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\RefProcess
     */
    public function setNyukinDate($NyukinDate)
    {
        $this->NyukinDate = $NyukinDate;
        return $this;
    }
    
    /**
     * @param unknown $StampFlag
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\RefProcess
     */
    public function setStampFlag($StampFlag)
    {
        $this->StampFlag = $StampFlag;
        return $this;
    }
    
    /**
     * @param unknown $HashCd
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\RefProcess
     */
    public function setHashCd($HashCd)
    {
        $this->HashCd = $HashCd;
        return $this;
    }
    
    /**
     * @param unknown $is_payment_mail
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\RefProcess
     */
    public function setIs_payment_mail($is_payment_mail)
    {
        $this->is_payment_mail = $is_payment_mail;
        return $this;
    }
    
    /**
     * @param unknown $payment_mail
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\RefProcess
     */
    public function setPayment_mail($payment_mail)
    {
        $this->payment_mail = $payment_mail;
        return $this;
    }
    
    /**
     * @param unknown $MerchantName
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\RefProcess
     */
    public function setMerchantName($MerchantName)
    {
        $this->MerchantName = $MerchantName;
        return $this;
    }

}