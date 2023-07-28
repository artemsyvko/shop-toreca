<?php

namespace Plugin\SlnPayment42\Service\SlnContent\Credit;

class Master extends \Plugin\SlnPayment42\Service\SlnContent\Basic
{
    public function getIterator() {
        return new Master(get_object_vars($this));
    }
    
    /**
     * 店舗毎に弊社 店舗番号を設定するためのコード
     * @var unknown
     */
    protected $TenantId;
    
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
     * カード番号
     * @var unknown
     */
    protected $CardNo;
    
    /**
     * カード有効期限
     * @var unknown
     */
    protected $CardExp;
    
    /**
     * 支払区分
     * @var unknown
     */
    protected $PayType;
    
    /**
     * 利用金額 設定可能な桁数は7桁
     * @var unknown
     */
    protected $Amount;
    
    /**
     * セキュリティコード
     * (認証アシスト項目)カード券面または裏面に記載される認証用コードです。
     * @var unknown
     */
    protected $SecCd;
    
    /**
     * カナ氏名（姓）
     * @var unknown
     */
    protected $KanaSei;
    
    /**
     * カナ氏名（名）
     * @var unknown
     */
    protected $KanaMei;
    
    /**
     * 生月日(MMDD)
     * @var unknown
     */
    protected $BirthDay;
    
    /**
     * 電話番号
     * (認証アシスト項目)電話番号下4ケタです。
     * @var unknown
     */
    protected $TelNo;
    
    /**
     * eLIO認証
     * ※現在使用しておりません。
     * @var unknown
     */
    protected $eLIO;
    
    /**
     * 3Dメッセージバージョン番号
     * @var unknown
     */
    protected $MessageVersionNo3D;
    
    /**
     * 3DトランザクションID
     * @var unknown
     */
    protected $TransactionId3D;
    
    /**
     * エンコード済XID
     * @var unknown
     */
    protected $EncodeXId3D;
    
    /**
     * ステータス
     * @var unknown
     */
    protected $TransactionStatus3D;
    
    /**
     * CAVVアルゴリズム
     * @var unknown
     */
    protected $CAVVAlgorithm3D;
    
    /**
     * CAVV
     * @var unknown
     */
    protected $CAVV3D;
    
    /**
     * ECI
     * @var unknown
     */
    protected $ECI3D;
    
    /**
     * カード番号（3D）
     * @var unknown
     */
    protected $PANCardNo3D;
    
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
     * 認証結果1
     * @var unknown
     */
    protected $McSecCd;
    
    /**
     * 認証結果2
     * @var unknown
     */
    protected $McKanaSei;
    
    /**
     * 認証結果3
     * @var unknown
     */
    protected $McKanaMei;
    
    /**
     * 認証結果4
     * @var unknown
     */
    protected $McBirthDay;
    
    /**
     * 認証結果5
     * @var unknown
     */
    protected $McTelNo;
    
    /**
     * トークン
     * @var unknown
     */
    protected $Token;
    
    /**
     * @return the $TenantId
     */
    public function getTenantId()
    {
        return $this->TenantId;
    }
    
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
     * @return the $CardNo
     */
    public function getCardNo()
    {
        return $this->CardNo;
    }
    
    /**
     * @return the $CardExp
     */
    public function getCardExp()
    {
        return $this->CardExp;
    }
    
    /**
     * @return the $PayType
     */
    public function getPayType()
    {
        return $this->PayType;
    }
    
    /**
     * @return the $Amount
     */
    public function getAmount()
    {
        return $this->Amount;
    }
    
    /**
     * @return the $SecCd
     */
    public function getSecCd()
    {
        return $this->SecCd;
    }
    
    /**
     * @return the $KanaSei
     */
    public function getKanaSei()
    {
        return $this->KanaSei;
    }
    
    /**
     * @return the $KanaMei
     */
    public function getKanaMei()
    {
        return $this->KanaMei;
    }
    
    /**
     * @return the $BirthDay
     */
    public function getBirthDay()
    {
        return $this->BirthDay;
    }
    
    /**
     * @return the $TelNo
     */
    public function getTelNo()
    {
        return $this->TelNo;
    }
    
    /**
     * @return the $eLIO
     */
    public function getELIO()
    {
        return $this->eLIO;
    }
    
    /**
     * @return the $MessageVersionNo3D
     */
    public function getMessageVersionNo3D()
    {
        return $this->MessageVersionNo3D;
    }
    
    /**
     * @return the $TransactionId3D
     */
    public function getTransactionId3D()
    {
        return $this->TransactionId3D;
    }
    
    /**
     * @return the $EncodeXId3D
     */
    public function getEncodeXId3D()
    {
        return $this->EncodeXId3D;
    }
    
    /**
     * @return the $TransactionStatus3D
     */
    public function getTransactionStatus3D()
    {
        return $this->TransactionStatus3D;
    }
    
    /**
     * @return the $CAVVAlgorithm3D
     */
    public function getCAVVAlgorithm3D()
    {
        return $this->CAVVAlgorithm3D;
    }
    
    /**
     * @return the $CAVV3D
     */
    public function getCAVV3D()
    {
        return $this->CAVV3D;
    }
    
    /**
     * @return the $ECI3D
     */
    public function getECI3D()
    {
        return $this->ECI3D;
    }
    
    /**
     * @return the $PANCardNo3D
     */
    public function getPANCardNo3D()
    {
        return $this->PANCardNo3D;
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
     * @return the $McSecCd
     */
    public function getMcSecCd()
    {
        return $this->McSecCd;
    }
    
    /**
     * @return the $McKanaSei
     */
    public function getMcKanaSei()
    {
        return $this->McKanaSei;
    }
    
    /**
     * @return the $McKanaMei
     */
    public function getMcKanaMei()
    {
        return $this->McKanaMei;
    }
    
    /**
     * @return the $McBirthDay
     */
    public function getMcBirthDay()
    {
        return $this->McBirthDay;
    }
    
    /**
     * @return the $McTelNo
     */
    public function getMcTelNo()
    {
        return $this->McTelNo;
    }
    
    /**
     * @param unknown $TenantId
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setTenantId($TenantId)
    {
        $this->TenantId = $TenantId;
        return $this;
    }
    
    /**
     * @param unknown $KaiinId
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setKaiinId($KaiinId)
    {
        $this->KaiinId = $KaiinId;
        return $this;
    }
    
    /**
     * @param unknown $KaiinPass
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setKaiinPass($KaiinPass)
    {
        $this->KaiinPass = $KaiinPass;
        return $this;
    }
    
    /**
     * @param unknown $CardNo
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setCardNo($CardNo)
    {
        $this->CardNo = $CardNo;
        return $this;
    }
    
    /**
     * @param unknown $CardExp
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setCardExp($CardExp)
    {
        $this->CardExp = $CardExp;
        return $this;
    }
    
    /**
     * @param unknown $PayType
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setPayType($PayType)
    {
        $this->PayType = $PayType;
        return $this;
    }
    
    /**
     * @param unknown $Amount
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setAmount($Amount)
    {
        $this->Amount = $Amount;
        return $this;
    }
    
    /**
     * @param unknown $SecCd
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setSecCd($SecCd)
    {
        $this->SecCd = $SecCd;
        return $this;
    }
    
    /**
     * @param unknown $KanaSei
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setKanaSei($KanaSei)
    {
        $this->KanaSei = mb_convert_kana($KanaSei, 'kha', 'UTF-8');
        return $this;
    }
    
    /**
     * @param unknown $KanaMei
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setKanaMei($KanaMei)
    {
        $this->KanaMei = mb_convert_kana($KanaMei, 'kha', 'UTF-8');
        return $this;
    }
    
    /**
     * @param unknown $BirthDay
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setBirthDay($BirthDay)
    {
        $this->BirthDay = $BirthDay;
        return $this;
    }
    
    /**
     * @param unknown $TelNo
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setTelNo($TelNo)
    {
        $this->TelNo = $TelNo;
        return $this;
    }
    
    /**
     * @param unknown $eLIO
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setELIO($eLIO)
    {
        $this->eLIO = $eLIO;
        return $this;
    }
    
    /**
     * @param unknown $MessageVersionNo3D
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setMessageVersionNo3D($MessageVersionNo3D)
    {
        $this->MessageVersionNo3D = $MessageVersionNo3D;
        return $this;
    }
    
    /**
     * @param unknown $TransactionId3D
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setTransactionId3D($TransactionId3D)
    {
        $this->TransactionId3D = $TransactionId3D;
        return $this;
    }
    
    /**
     * @param unknown $EncodeXId3D
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setEncodeXId3D($EncodeXId3D)
    {
        $this->EncodeXId3D = $EncodeXId3D;
        return $this;
    }
    
    /**
     * @param unknown $TransactionStatus3D
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setTransactionStatus3D($TransactionStatus3D)
    {
        $this->TransactionStatus3D = $TransactionStatus3D;
        return $this;
    }
    
    /**
     * @param unknown $CAVVAlgorithm3D
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setCAVVAlgorithm3D($CAVVAlgorithm3D)
    {
        $this->CAVVAlgorithm3D = $CAVVAlgorithm3D;
        return $this;
    }
    
    /**
     * @param unknown $CAVV3D
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setCAVV3D($CAVV3D)
    {
        $this->CAVV3D = $CAVV3D;
        return $this;
    }
    
    /**
     * @param unknown $ECI3D
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setECI3D($ECI3D)
    {
        $this->ECI3D = $ECI3D;
        return $this;
    }
    
    /**
     * @param unknown $PANCardNo3D
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setPANCardNo3D($PANCardNo3D)
    {
        $this->PANCardNo3D = $PANCardNo3D;
        return $this;
    }
    
    /**
     * @param unknown $CompanyCd
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setCompanyCd($CompanyCd)
    {
        $this->CompanyCd = $CompanyCd;
        return $this;
    }
    
    /**
     * @param unknown $ApproveNo
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setApproveNo($ApproveNo)
    {
        $this->ApproveNo = $ApproveNo;
        return $this;
    }
    
    /**
     * @param unknown $McSecCd
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setMcSecCd($McSecCd)
    {
        $this->McSecCd = $McSecCd;
        return $this;
    }
    
    /**
     * @param unknown $McKanaSei
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setMcKanaSei($McKanaSei)
    {
        $this->McKanaSei = $McKanaSei;
        return $this;
    }
    
    /**
     * @param unknown $McKanaMei
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setMcKanaMei($McKanaMei)
    {
        $this->McKanaMei = $McKanaMei;
        return $this;
    }
    
    /**
     * @param unknown $McBirthDay
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setMcBirthDay($McBirthDay)
    {
        $this->McBirthDay = $McBirthDay;
        return $this;
    }
    
    /**
     * @param unknown $McTelNo
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setMcTelNo($McTelNo)
    {
        $this->McTelNo = $McTelNo;
        return $this;
    }
    
    /**
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\unknown
     */
    public function getToken()
    {
        return $this->Token;
    }
    
    /**
     * @param unknown $Token
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function setToken($Token)
    {
        $this->Token = $Token;
        return $this;
    }
}