<?php
namespace Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response;

use Plugin\SlnPayment42\Service\SlnContent\Basic as contentBasic;
use Plugin\SlnPayment42\Service\SlnContent\Credit\ThreeDMember;

class ThreeDMemAdd extends MemAdd
{
    /**
     * @var Member
     */
    protected $content;
    
    public function getDataKey()
    {
        return array(
            'TransactionId' => '',
            'TransactionDate' => '',
            'OperateId' => '',
            'MerchantFree1' => '',
            'MerchantFree2' => '',
            'MerchantFree3' => '',
            'ResponseCd' => '',
            'CompanyCd' => '',
            'EnableCheckUseKbn' => '',
            'McSecCd' => '',
            'McKanaSei' => '',
            'McKanaMei' => '',
            'McBirthDay' => '',
            'McTelNo' => '',
            'ProcNo' => '',
            'RedirectUrl' => '',
        );
    }
    
    /**
     * (non-PHPdoc)
     * @see \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Basic::setContent()
     */
    public function setContent(contentBasic $content) {
        if($content instanceof ThreeDMember) {
            $this->content = $content;
        } else {
            throw new \Exception("content not Plugin\SlnPayment42\Service\SlnContent\Credit\Member");
        }
    }
}