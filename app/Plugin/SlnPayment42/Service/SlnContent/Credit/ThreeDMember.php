<?php

namespace Plugin\SlnPayment42\Service\SlnContent\Credit;

class ThreeDMember extends Member
{
    /**
     * 処理番号
     * @var string
     */
    protected $ProcNo;
    
    /**
     * リダイレクト用 URL
     * @var string
     */
    protected $RedirectUrl;
    
    /**
     * POST 用 URL
     * @var string
     */
    protected $PostUrl;
    
    /**
     * 3D セキュア認証結果コード
     * @var string
     */
    protected $SecureResultCode;
    
    /**
     * @return the $ProcNo
     */
    public function getProcNo()
    {
        return $this->ProcNo;
    }
    
    /**
     * @return the $RedirectUrl
     */
    public function getRedirectUrl()
    {
        return $this->RedirectUrl;
    }
    
    /**
     * @return the $PostUrl
     */
    public function getPostUrl()
    {
        return $this->PostUrl;
    }
    
    /**
     * @return the $SecureResultCode
     */
    public function getSecureResultCode()
    {
        return $this->SecureResultCode;
    }
    
    /**
     * @param unknown $ProcNo
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\ThreeDMaster
     */
    public function setProcNo($ProcNo)
    {
        $this->ProcNo = $ProcNo;
        return $this;
    }
    
    /**
     * @param unknown $RedirectUrl
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\ThreeDMaster
     */
    public function setRedirectUrl($RedirectUrl)
    {
        $this->RedirectUrl = $RedirectUrl;
        return $this;
    }
    
    /**
     * @param unknown $PostUrl
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\ThreeDMaster
     */
    public function setPostUrl($PostUrl)
    {
        $this->PostUrl = $PostUrl;
        return $this;
    }
    
    /**
     * @param unknown $SecureResultCode
     * @return \Plugin\SlnPayment42\Service\SlnContent\Credit\ThreeDMaster
     */
    public function setSecureResultCode($SecureResultCode)
    {
        $this->SecureResultCode = $SecureResultCode;
        return $this;
    }
    
    /**
     * @return the $CardNo
     */
    public function getCardNo()
    {
        return $this->CardNo;
    }
}