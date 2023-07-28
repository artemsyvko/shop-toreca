<?php

namespace Plugin\SlnPayment42\Service\SlnContent\Receipt;

class AddMaster extends \Plugin\SlnPayment42\Service\SlnContent\Basic
{
    public function getIterator() {
        return new AddMaster(get_object_vars($this));
    }
    
    /**
     * 利用金額
     * @var unknown
     */
    protected $Amount;
    
    /**
     * 支払期限
     * @var unknown
     */
    protected $PayLimit;
    
    /**
     * ユーザ漢字氏名 
     * @var unknown
     */
    protected $NameKanji;
    
    /**
     * ユーザカナ氏名
     * @var unknown
     */
    protected $NameKana;
    
    /**
     * 電話番号
     * @var unknown
     */
    protected $TelNo;
    
    /**
     * 商品名
     * @var unknown
     */
    protected $ShouhinName;
    
    /**
     * フリーエリア１
     * @var unknown
     */
    protected $Free1;
    
    /**
     * フリーエリア2
     * @var unknown
     */
    protected $Free2;
    
    /**
     * フリーエリア 3 
     * @var unknown
     */
    protected $Free3;
    
    /**
     * フリーエリア 4
     * @var unknown
     */
    protected $Free4;
    
    /**
     * フリーエリア 5
     * @var unknown
     */
    protected $Free5;
    
    /**
     * フリーエリア 6
     * @var unknown
     */
    protected $Free6;
    
    /**
     * フリーエリア 7
     * @var unknown
     */
    protected $Free7;
    
    /**
     * ご案内 1 
     * @var unknown
     */
    protected $Comment;
    
    /**
     * ご案内 2
     * @var unknown
     */
    protected $Free8;
    
    /**
     * ご案内 3 
     * @var unknown
     */
    protected $Free9;
    
    /**
     * ご案内 4 
     * @var unknown
     */
    protected $Free10;
    
    /**
     * ご案内 5
     * @var unknown
     */
    protected $Free11;
    
    /**
     * ご案内 6
     * @var unknown
     */
    protected $Free12;
    
    /**
     * ご案内 7
     * @var unknown
     */
    protected $Free13;
    
    /**
     * ご案内 8
     * @var unknown
     */
    protected $Free14;
    
    /**
     * ご案内 9
     * @var unknown
     */
    protected $Free15;
    
    /**
     * ご案内 10
     * @var unknown
     */
    protected $Free16;
    
    /**
     * 問合せ先
     * @var unknown
     */
    protected $Free17;
    
    /**
     * 問合せ電話
     * @var unknown
     */
    protected $Free18;
    
    /**
     * 問合せ時間 
     * @var unknown
     */
    protected $Free19;
    
    /**
     * 戻り先 URL (URL?code=FreeArea&rkbn=1/2)
     * @var unknown
     */
    protected $ReturnURL;
    
    /**
     * ご案内タイトル
     * @var unknown
     */
    protected $Title;
    
    /**
     * 決済番号
     * @var unknown
     */
    protected $KessaiNum;
    
    /**
     * 暗号化決済番号
     * @var unknown
     */
    protected $FreeArea;
    
    /**
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\unknown
     */
    public function getAmount()
    {
        return $this->Amount;
    }
    
    /**
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\unknown
     */
    public function getPayLimit()
    {
        return $this->PayLimit;
    }
    
    /**
     * @return the $NameKanji
     */
    public function getNameKanji()
    {
        return $this->NameKanji;
    }
    
    /**
     * @return the $NameKana
     */
    public function getNameKana()
    {
        return $this->NameKana;
    }
    
    /**
     * @return the $TelNo
     */
    public function getTelNo()
    {
        return $this->TelNo;
    }
    
    /**
     * @return the $ShouhinName
     */
    public function getShouhinName()
    {
        return $this->ShouhinName;
    }
    
    /**
     * @return the $Free1
     */
    public function getFree1()
    {
        return $this->Free1;
    }
    
    /**
     * @return the $Free2
     */
    public function getFree2()
    {
        return $this->Free2;
    }
    
    /**
     * @return the $Free3
     */
    public function getFree3()
    {
        return $this->Free3;
    }
    
    /**
     * @return the $Free4
     */
    public function getFree4()
    {
        return $this->Free4;
    }
    
    /**
     * @return the $Free5
     */
    public function getFree5()
    {
        return $this->Free5;
    }
    
    /**
     * @return the $Free6
     */
    public function getFree6()
    {
        return $this->Free6;
    }
    
    /**
     * @return the $Free7
     */
    public function getFree7()
    {
        return $this->Free7;
    }
    
    /**
     * @return the $Comment
     */
    public function getComment()
    {
        return $this->Comment;
    }
    
    /**
     * @return the $Free8
     */
    public function getFree8()
    {
        return $this->Free8;
    }
    
    /**
     * @return the $Free9
     */
    public function getFree9()
    {
        return $this->Free9;
    }
    
    /**
     * @return the $Free10
     */
    public function getFree10()
    {
        return $this->Free10;
    }
    
    /**
     * @return the $Free11
     */
    public function getFree11()
    {
        return $this->Free11;
    }
    
    /**
     * @return the $Free12
     */
    public function getFree12()
    {
        return $this->Free12;
    }
    
    /**
     * @return the $Free13
     */
    public function getFree13()
    {
        return $this->Free13;
    }
    
    /**
     * @return the $Free14
     */
    public function getFree14()
    {
        return $this->Free14;
    }
    
    /**
     * @return the $Free15
     */
    public function getFree15()
    {
        return $this->Free15;
    }
    
    /**
     * @return the $Free16
     */
    public function getFree16()
    {
        return $this->Free16;
    }
    
    /**
     * @return the $Free17
     */
    public function getFree17()
    {
        return $this->Free17;
    }
    
    /**
     * @return the $Free18
     */
    public function getFree18()
    {
        return $this->Free18;
    }
    
    /**
     * @return the $Free19
     */
    public function getFree19()
    {
        return $this->Free19;
    }
    
    /**
     * @return the $ReturnURL
     */
    public function getReturnURL()
    {
        return $this->ReturnURL;
    }
    
    /**
     * @return the $Title
     */
    public function getTitle()
    {
        return $this->Title;
    }
    
    /**
     * @return the $KessaiNum
     */
    public function getKessaiNum()
    {
        return $this->KessaiNum;
    }
    
    /**
     * @return the $FreeArea
     */
    public function getFreeArea()
    {
        return $this->FreeArea;
    }
    
    /**
     * @param unknown $Amount
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setAmount($Amount) 
    {
        $this->Amount = $Amount;
        return $this;
    }
    
    /**
     * @param unknown $PayLimit
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setPayLimit($PayLimit)
    {
        $this->PayLimit = $PayLimit;
        return $this;
    }
    
    /**
     * @param unknown $NameKanji
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setNameKanji($NameKanji)
    {
        $this->NameKanji = $NameKanji;
        return $this;
    }
    
    /**
     * @param unknown $NameKana
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setNameKana($NameKana)
    {
        $this->NameKana = $NameKana;
        return $this;
    }
    
    /**
     * @param unknown $TelNo
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setTelNo($TelNo)
    {
        $this->TelNo = $TelNo;
        return $this;
    }
    
    /**
     * @param unknown $ShouhinName
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setShouhinName($ShouhinName)
    {
        $this->ShouhinName = $ShouhinName;
        return $this;
    }
    
    /**
     * @param unknown $Free1
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree1($Free1)
    {
        $this->Free1 = $Free1;
        return $this;
    }
    
    /**
     * @param unknown $Free2
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree2($Free2)
    {
        $this->Free2 = $Free2;
        return $this;
    }
    
    /**
     * @param unknown $Free3
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree3($Free3)
    {
        $this->Free3 = $Free3;
        return $this;
    }
    
    /**
     * @param unknown $Free4
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree4($Free4)
    {
        $this->Free4 = $Free4;
        return $this;
    }
    
    /**
     * @param unknown $Free5
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree5($Free5)
    {
        $this->Free5 = $Free5;
        return $this;
    }
    
    /**
     * @param unknown $Free6
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree6($Free6)
    {
        $this->Free6 = $Free6;
        return $this;
    }
    
    /**
     * @param unknown $Free7
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree7($Free7)
    {
        $this->Free7 = $Free7;
        return $this;
    }
    
    /**
     * @param unknown $Comment
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setComment($Comment)
    {
        $this->Comment = $Comment;
        return $this;
    }
    
    /**
     * @param unknown $Free8
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree8($Free8)
    {
        $this->Free8 = $Free8;
        return $this;
    }
    
    /**
     * @param unknown $Free9
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree9($Free9)
    {
        $this->Free9 = $Free9;
        return $this;
    }
    
    /**
     * @param unknown $Free10
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree10($Free10)
    {
        $this->Free10 = $Free10;
        return $this;
    }
    
    /**
     * @param unknown $Free11
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree11($Free11)
    {
        $this->Free11 = $Free11;
        return $this;
    }
    
    /**
     * @param unknown $Free12
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree12($Free12)
    {
        $this->Free12 = $Free12;
        return $this;
    }
    
    /**
     * @param unknown $Free13
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree13($Free13)
    {
        $this->Free13 = $Free13;
        return $this;
    }
    
    /**
     * @param unknown $Free14
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree14($Free14)
    {
        $this->Free14 = $Free14;
        return $this;
    }
    
    /**
     * @param unknown $Free15
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree15($Free15)
    {
        $this->Free15 = $Free15;
        return $this;
    }
    
    /**
     * @param unknown $Free16
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree16($Free16)
    {
        $this->Free16 = $Free16;
        return $this;
    }
    
    /**
     * @param unknown $Free17
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree17($Free17)
    {
        $this->Free17 = $Free17;
        return $this;
    }
    
    /**
     * @param unknown $Free18
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree18($Free18)
    {
        $this->Free18 = $Free18;
        return $this;
    }
    
    /**
     * @param unknown $Free19
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFree19($Free19)
    {
        $this->Free19 = $Free19;
        return $this;
    }
    
    /**
     * @param unknown $ReturnURL
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setReturnURL($ReturnURL)
    {
        $this->ReturnURL = $ReturnURL;
        return $this;
    }
    
    /**
     * @param unknown $Title
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setTitle($Title)
    {
        $this->Title = $Title;
        return $this;
    }
    
    /**
     * @param unknown $KessaiNum
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setKessaiNum($KessaiNum)
    {
        $this->KessaiNum = $KessaiNum;
        return $this;
    }
    
    /**
     * @param unknown $FreeArea
     * @return \Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function setFreeArea($FreeArea)
    {
        $this->FreeArea = trim($FreeArea);
        return $this;
    }

}