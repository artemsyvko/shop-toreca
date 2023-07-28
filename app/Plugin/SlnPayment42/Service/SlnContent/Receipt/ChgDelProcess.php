<?php

namespace Plugin\SlnPayment42\Service\SlnContent\Receipt;

class ChgDelProcess extends \Plugin\SlnPayment42\Service\SlnContent\Basic
{
    public function getIterator() {
        return new ChgDelProcess(get_object_vars($this));
    }
    
    /**
     * 支払期限
     * @var unknown
     */
    protected $PayLimit;
    
    /**
     * 支払額
     * @var unknown
     */
    protected $Amount;
    
    /**
     * 決済番号
     * @var unknown
     */
    protected $KessaiNumber;
    
    /**
     *  暗号化決済番号
     * @var unknown
     */
    protected $FreeArea;
    
    /**
     * @return the $PayLimit
     */
    public function getPayLimit()
    {
        return $this->PayLimit;
    }
    
    /**
     * @return the $Amount
     */
    public function getAmount()
    {
        return $this->Amount;
    }
    
    /**
     * @return the $KessaiNumber
     */
    public function getKessaiNumber()
    {
        return $this->KessaiNumber;
    }
    
    /**
     * @return the $FreeArea
     */
    public function getFreeArea()
    {
        return $this->FreeArea;
    }
    
    /**
     * @param unknown $PayLimit
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\ChgDelProcess
     */
    public function setPayLimit($PayLimit)
    {
        $this->PayLimit = $PayLimit;
        return $this;
    }
    
    /**
     * @param unknown $Amount
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\ChgDelProcess
     */
    public function setAmount($Amount)
    {
        $this->Amount = $Amount;
        return $this;
    }
    
    /**
     * @param unknown $KessaiNumber
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\ChgDelProcess
     */
    public function setKessaiNumber($KessaiNumber)
    {
        $this->KessaiNumber = $KessaiNumber;
        return $this;
    }
    
    /**
     * @param unknown $FreeArea
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\ChgDelProcess
     */
    public function setFreeArea($FreeArea)
    {
        $this->FreeArea = trim($FreeArea);
        return $this;
    }

}
