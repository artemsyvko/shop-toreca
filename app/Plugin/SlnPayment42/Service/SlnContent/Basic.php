<?php

namespace Plugin\SlnPayment42\Service\SlnContent;

abstract class Basic implements \ArrayAccess,\IteratorAggregate
{
    public function offsetExists($offset)
    {
        $method = $offset;
        return method_exists($this, "get$method") || method_exists($this, "is$method");
    }
    
    public function offsetSet($offset, $value)
    {
        $method = $offset;
        
        if (method_exists($this, "set$method")) {
            $this->{"get$method"}($value);
        }
    }
    
    public function offsetGet($offset)
    {
        $method = $offset;
    
        if (method_exists($this, "get$method")) {
            return $this->{"get$method"}();
        } elseif (method_exists($this, "is$method")) {
            return $this->{"is$method"}();
        }
    }
    
    public function offsetUnset($offset)
    {
    }
    
    /**
     * 全サービス共通の本システムが貴社を識別するためのID
     * @var string
     */
    protected $MerchantId;
    
    /**
     * 全サービス共通の本システムが貴社を識別するためのパスワード
     * @var string
     */
    protected $MerchantPass;
    
    /**
     * 処理通番(半角数字)
     * @var string
     */
    protected $TransactionId;
    
    /**
     * 取引の処理日付(YYYYMMDD)
     * @var string
     */
    protected $TransactionDate;
    
    /**
     * 取引の処理内容(半角英数字)
     * @var string
     */
    protected $OperateId;
    
    /**
     * 貴社自由領域
     * @var string
     */
    protected $MerchantFree1;
    
    /**
     * 貴社自由領域
     * @var string
     */
    protected $MerchantFree2;
    
    /**
     * 貴社自由領域(全半角文字)
     * @var string
     */
    protected $MerchantFree3;
    
    /**
     * 処理が許可された場合に応答時セットされる本システム発行の処理ID(半角英数字)
     * @var string
     */
    protected $ProcessId;
    
    /**
     * 処理が許可された場合に応答時セットされる本システム発行の処理Password(半角英数字)
     * @var string
     */
    protected $ProcessPass;
    
    /**
     * 処理結果コードが応答時にセット(半角英数字)
     * @var string
     */
    protected $ResponseCd;
    
    /**
     * @return the $MerchantId
     */
    public function getMerchantId()
    {
        return $this->MerchantId;
    }
    
    /**
     * @return the $MerchantPass
     */
    public function getMerchantPass()
    {
        return $this->MerchantPass;
    }
    
    /**
     * @return the $TransactionId
     */
    public function getTransactionId()
    {
        return $this->TransactionId;
    }
    
    /**
     * @return the $TransactionDate
     */
    public function getTransactionDate()
    {
        return $this->TransactionDate;
    }
    
    /**
     * @return the $OperateId
     */
    public function getOperateId()
    {
        return $this->OperateId;
    }
    
    /**
     * @return the $MerchantFree1
     */
    public function getMerchantFree1()
    {
        return $this->MerchantFree1;
    }
    
    /**
     * @return the $MerchantFree2
     */
    public function getMerchantFree2()
    {
        return $this->MerchantFree2;
    }
    
    /**
     * @return the $MerchantFree3
     */
    public function getMerchantFree3()
    {
        return $this->MerchantFree3;
    }
    
    /**
     * @return the $ProcessId
     */
    public function getProcessId()
    {
        return $this->ProcessId;
    }
    
    /**
     * @return the $ProcessPass
     */
    public function getProcessPass()
    {
        return $this->ProcessPass;
    }
    
    /**
     * @return the $ResponseCd
     */
    public function getResponseCd()
    {
        return $this->ResponseCd;
    }
    
    /**
     * @param unknown $MerchantId
     * @return \Plugin\SlnPayment42\Service\SlnContent\Basic
     */
    public function setMerchantId($MerchantId)
    {
        $this->MerchantId = $MerchantId;
        return $this;
    }
    
    /**
     * @param unknown $MerchantPass
     * @return \Plugin\SlnPayment42\Service\SlnContent\Basic
     */
    public function setMerchantPass($MerchantPass)
    {
        $this->MerchantPass = $MerchantPass;
        return $this;
    }
    
    /**
     * @param unknown $TransactionId
     * @return \Plugin\SlnPayment42\Service\SlnContent\Basic
     */
    public function setTransactionId($TransactionId)
    {
        $this->TransactionId = $TransactionId;
        return $this;
    }
    
    /**
     * @param unknown $TransactionDate
     * @return \Plugin\SlnPayment42\Service\SlnContent\Basic
     */
    public function setTransactionDate($TransactionDate)
    {
        $this->TransactionDate = $TransactionDate;
        return $this;
    }
    
    /**
     * @param unknown $OperateId
     * @return \Plugin\SlnPayment42\Service\SlnContent\Basic
     */
    public function setOperateId($OperateId)
    {
        $this->OperateId = $OperateId;
        return $this;
    }
    
    /**
     * @param unknown $MerchantFree1
     * @return \Plugin\SlnPayment42\Service\SlnContent\Basic
     */
    public function setMerchantFree1($MerchantFree1)
    {
        $this->MerchantFree1 = $MerchantFree1;
        return $this;
    }
    
    /**
     * @param unknown $MerchantFree2
     * @return \Plugin\SlnPayment42\Service\SlnContent\Basic
     */
    public function setMerchantFree2($MerchantFree2)
    {
        $this->MerchantFree2 = $MerchantFree2;
        return $this;
    }
    
    /**
     * @param unknown $MerchantFree3
     * @return \Plugin\SlnPayment42\Service\SlnContent\Basic
     */
    public function setMerchantFree3($MerchantFree3)
    {
        $this->MerchantFree3 = $MerchantFree3;
        return $this;
    }
    
    /**
     * @param unknown $ProcessId
     * @return \Plugin\SlnPayment42\Service\SlnContent\Basic
     */
    public function setProcessId($ProcessId)
    {
        $this->ProcessId = $ProcessId;
        return $this;
    }
    
    /**
     * @param unknown $ProcessPass
     * @return \Plugin\SlnPayment42\Service\SlnContent\Basic
     */
    public function setProcessPass($ProcessPass)
    {
        $this->ProcessPass = $ProcessPass;
        return $this;
    }
    
    /**
     * @param unknown $ResponseCd
     * @return \Plugin\SlnPayment42\Service\SlnContent\Basic
     */
    public function setResponseCd($ResponseCd)
    {
        $this->ResponseCd = $ResponseCd;
        return $this;
    }

    
}