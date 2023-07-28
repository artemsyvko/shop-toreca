<?php

namespace Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response;

use Plugin\SlnPayment42\Service\SlnContent\Basic as contentBasic;
use Plugin\SlnPayment42\Service\SlnAction\Content\Basic;
use Plugin\SlnPayment42\Service\SlnContent\Credit\Master;

class Check extends Basic
{
    /**
     * @var Master
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
            'ProcessId' => '',
            'ProcessPass' => '',
            'ResponseCd' => '',
            'CompanyCd' => '',
            'ApproveNo' => '',
            'McSecCd' => '',
            'McKanaSei' => '',
            'McKanaMei' => '',
            'McBirthDay' => '',
            'McTelNo' => '',
        );
    }
    
    public function getOperatePrefix()
    {
        return "1";
    }

    /**
     * (non-PHPdoc)
     * @see \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Basic::setContent()
     */
    public function setContent(contentBasic $content) {
        if($content instanceof Master) {
            $this->content = $content;
        } else {
            throw new \Exception("content not Plugin\SlnPayment42\Service\SlnContent\Credit\Master");
        }
    }

    /**
     * (non-PHPdoc)
     * @var Plugin\SlnPayment42\Service\SlnContent\Credit\Master
     */
    public function getContent() {
        return $this->content;
    }
}