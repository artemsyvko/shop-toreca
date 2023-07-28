<?php

namespace Plugin\SlnPayment42\Service\SlnContent\Credit;

class Member extends \Plugin\SlnPayment42\Service\SlnContent\Basic
{
    public function getIterator() {
        return new Member(get_object_vars($this));
    }
    
    /**
     * 店舗コード
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
     * カード有効期限(YYMM))
     * @var unknown
     */
    protected $CardExp;
    
    /**
     * カード会社コード
     * @var unknown
     */
    protected $CompanyCd;
    
    /**
     * 新会員パスワード
     * @var unknown
     */
    protected $NewKaiinPass;
    
    /**
     * 会員ステータス
     * @var unknown
     */
    protected $KaiinStatus;
    
    /**
     * 会員有効日付 (YYYYMMDD)
     * @var unknown
     */
    protected $KaiinEnableDate;
    
    /**
     * 洗替使用区分
     * @var unknown
     */
    protected $EnableCheckUseKbn;
    
    /**
     * セキュリティコード
     * @var unknown
     */
    protected $SecCd;
    
    /**
     * カナ氏名(姓)
     * @var unknown
     */
    protected $KanaSei;
    
    /**
     * カナ氏名(名)
     * @var unknown
     */
    protected $KanaMei;
    
    /**
     * 生月日
     * @var unknown
     */
    protected $BirthDay;
    
    /**
     * 電話番号
     * @var unknown
     */
    protected $TelNo;
    
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
        return $this->CardNo ? "**********" . substr($this->CardNo, -4) : $this->CardNo;
    }
    
    /**
     * @return the $CardExp
     */
    public function getCardExp()
    {
        return $this->CardExp;
    }
    
    /**
     * @return the $CompanyCd
     */
    public function getCompanyCd()
    {
        return $this->CompanyCd;
    }
    
    /**
     * @return the $NewKaiinPass
     */
    public function getNewKaiinPass()
    {
        return $this->NewKaiinPass;
    }
    
    /**
     * @return the $KaiinStatus
     */
    public function getKaiinStatus()
    {
        return $this->KaiinStatus;
    }
    
    /**
     * @return the $KaiinEnableDate
     */
    public function getKaiinEnableDate()
    {
        return $this->KaiinEnableDate;
    }
    
    /**
     * @return the $EnableCheckUseKbn
     */
    public function getEnableCheckUseKbn()
    {
        return $this->EnableCheckUseKbn;
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
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setTenantId($TenantId)
    {
        $this->TenantId = $TenantId;
        return $this;
    }
    
    /**
     * @param unknown $KaiinId
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setKaiinId($KaiinId)
    {
        $this->KaiinId = $KaiinId;
        return $this;
    }
    
    /**
     * @param unknown $KaiinPass
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setKaiinPass($KaiinPass)
    {
        $this->KaiinPass = $KaiinPass;
        return $this;
    }
    
    /**
     * @param unknown $CardNo
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setCardNo($CardNo)
    {
        $this->CardNo = $CardNo;
        return $this;
    }
    
    /**
     * @param unknown $CardExp
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setCardExp($CardExp)
    {
        $this->CardExp = $CardExp;
        return $this;
    }
    
    /**
     * @param unknown $CompanyCd
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setCompanyCd($CompanyCd)
    {
        $this->CompanyCd = $CompanyCd;
        return $this;
    }
    
    /**
     * @param unknown $NewKaiinPass
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setNewKaiinPass($NewKaiinPass)
    {
        $this->NewKaiinPass = $NewKaiinPass;
        return $this;
    }
    
    /**
     * @param unknown $KaiinStatus
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setKaiinStatus($KaiinStatus)
    {
        $this->KaiinStatus = $KaiinStatus;
        return $this;
    }
    
    /**
     * @param unknown $KaiinEnableDate
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setKaiinEnableDate($KaiinEnableDate)
    {
        $this->KaiinEnableDate = $KaiinEnableDate;
        return $this;
    }
    
    /**
     * @param unknown $EnableCheckUseKbn
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setEnableCheckUseKbn($EnableCheckUseKbn)
    {
        $this->EnableCheckUseKbn = $EnableCheckUseKbn;
        return $this;
    }
    
    /**
     * @param unknown $SecCd
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setSecCd($SecCd)
    {
        $this->SecCd = $SecCd;
        return $this;
    }
    
    /**
     * @param unknown $KanaSei
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setKanaSei($KanaSei)
    {
        $this->KanaSei = mb_convert_kana($KanaSei, 'kha', 'UTF-8');
        return $this;
    }
    
    /**
     * @param unknown $KanaMei
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setKanaMei($KanaMei)
    {
        $this->KanaMei = mb_convert_kana($KanaMei, 'kha', 'UTF-8');
        return $this;
    }
    
    /**
     * @param unknown $BirthDay
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setBirthDay($BirthDay)
    {
        $this->BirthDay = $BirthDay;
        return $this;
    }
    
    /**
     * @param unknown $TelNo
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setTelNo($TelNo)
    {
        $this->TelNo = $TelNo;
        return $this;
    }
    
    /**
     * @param unknown $McSecCd
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setMcSecCd($McSecCd)
    {
        $this->McSecCd = $McSecCd;
        return $this;
    }
    
    /**
     * @param unknown $McKanaSei
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setMcKanaSei($McKanaSei)
    {
        $this->McKanaSei = $McKanaSei;
        return $this;
    }
    
    /**
     * @param unknown $McKanaMei
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setMcKanaMei($McKanaMei)
    {
        $this->McKanaMei = $McKanaMei;
        return $this;
    }
    
    /**
     * @param unknown $McBirthDay
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function setMcBirthDay($McBirthDay)
    {
        $this->McBirthDay = $McBirthDay;
        return $this;
    }
    
    /**
     * @param unknown $McTelNo
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\Member
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