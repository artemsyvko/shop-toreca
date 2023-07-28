<?php

namespace Plugin\SlnPayment42\Service\SlnContent\Credit;

class Process extends \Plugin\SlnPayment42\Service\SlnContent\Basic
{
    public function getIterator() {
        return new Process(get_object_vars($this));
    }
    
    /**
     * 会員ID
     * @var unknown
     */
    protected $KaiinId;
    
    /**
     * 会員パスワード
     * @var unknown
     */
    protected $KaiinPass;
    
    /**
     * 利用金額
     * @var unknown
     */
    protected $Amount;
    
    /**
     * 売上計上日(YYYYMMDD)
     * @var unknown
     */
    protected $SalesDate;
    
    /**
     * カード会社コード
     * @var unknown
     */
    protected $CompanyCd;
    
    /**
     * 承認番号
     * @var unknown
     */
    protected $ApproveNo;
    
    /**
     * @return the $KaiinId
     */
    public function getKaiinId()
    {
        return $this->KaiinId;
    }
    
    /**
     * @return the $KaiinPass
     */
    public function getKaiinPass()
    {
        return $this->KaiinPass;
    }
    
    /**
     * @return the $Amount
     */
    public function getAmount()
    {
        return $this->Amount;
    }
    
    /**
     * @return the $SalesDate
     */
    public function getSalesDate()
    {
        return $this->SalesDate;
    }
    
    /**
     * @return the $CompanyCd
     */
    public function getCompanyCd()
    {
        return $this->CompanyCd;
    }
    
    /**
     * @return the $ApproveNo
     */
    public function getApproveNo()
    {
        return $this->ApproveNo;
    }
    
    /**
     * @param unknown $KaiinId
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Process
     */
    public function setKaiinId($KaiinId)
    {
        $this->KaiinId = $KaiinId;
        return $this;
    }
    
    /**
     * @param unknown $KaiinPass
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Process
     */
    public function setKaiinPass($KaiinPass)
    {
        $this->KaiinPass = $KaiinPass;
        return $this;
    }
    
    /**
     * @param unknown $Amount
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Process
     */
    public function setAmount($Amount)
    {
        $this->Amount = $Amount;
        return $this;
    }
    
    /**
     * @param unknown $SalesDate
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Process
     */
    public function setSalesDate($SalesDate)
    {
        $this->SalesDate = $SalesDate;
        return $this;
    }
    
    /**
     * @param unknown $CompanyCd
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Process
     */
    public function setCompanyCd($CompanyCd)
    {
        $this->CompanyCd = $CompanyCd;
        return $this;
    }
    
    /**
     * @param unknown $ApproveNo
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Process
     */
    public function setApproveNo($ApproveNo)
    {
        $this->ApproveNo = $ApproveNo;
        return $this;
    }

}