<?php
namespace Plugin\SlnPayment42\Service\SlnAction\Content\Member\Request;

use Plugin\SlnPayment42\Service\SlnAction\Content\Basic;
use Plugin\SlnPayment42\Service\SlnContent\Basic as contentBasic;
use Plugin\SlnPayment42\Service\SlnContent\Credit\Member;

class MemAdd extends Basic
{
    /**
     * @var Member
     */
    protected $content;
    
    public function getDataKey()
    {
        return array(
            'MerchantId' => '',
            'MerchantPass' => '',
            'TransactionDate' => '',
            'OperateId' => '',
            'MerchantFree1' => '',
            'MerchantFree2' => '',
            'MerchantFree3' => '',
            'TenantId' => '',
            'KaiinId' => '',
            'KaiinPass' => '',
            'CardNo' => '',
            'CardExp' => '',
            'EnableCheckUseKbn' => '',
            'SecCd' => '',
            'KanaSei' => '',
            'KanaMei' => '',
            'BirthDay' => '',
            'TelNo' => '',
            'Token' => '',
        );
    }
    
    public function getOperatePrefix()
    {
        return "4";
    }
    
    /**
     * (non-PHPdoc)
     * @see \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Basic::setContent()
     */
    public function setContent(contentBasic $content) {
        if($content instanceof Member) {
            $this->content = $content;
        } else {
            throw new \Exception("content not Plugin\SlnPayment42\Service\SlnContent\Credit\Member");
        }
    }
    
    /**
     * (non-PHPdoc)
     * @var Plugin\SlnPayment42\Service\SlnContent\Credit\Member
     */
    public function getContent() {
        return $this->content;
    }
}