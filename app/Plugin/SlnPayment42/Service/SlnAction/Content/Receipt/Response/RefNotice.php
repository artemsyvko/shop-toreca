<?php
namespace Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Response;

use Plugin\SlnPayment42\Service\SlnAction\Content\Basic;
use Plugin\SlnPayment42\Service\SlnContent\Basic as contentBasic;
use Plugin\SlnPayment42\Service\SlnContent\Receipt\RefProcess;

class RefNotice extends Basic
{
    
    public function getDataKey()
    {
        return array(
            'MerchantId' => '',
            'TransactionId' => '',
            'TransactionDate' => '',
            'OperateId' => '',
            'MerchantFree1' => '',
            'MerchantFree2' => '',
            'MerchantFree3' => '',
            'ProcessId' => '',
            'ProcessPass' => '',
            'ResponseCd' => '',
            'Condition' => '',
            'Amount' => '',
            'RecvNum' => '',
            'CvsCd' => '',
            'TenantCd' => '',
            'NyukinDate' => '',
            'StampFlag' => '',
            'HashCd' => '',
            'id_payment_mail' => '',
            'payment_mail' => '',
            'MerchantName' => '',
        );
    }
    
    public function getOperatePrefix()
    {
        return "2";
    }
    
    /**
     * (non-PHPdoc)
     * @see \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Basic::setContent()
     */
    public function setContent(contentBasic $content) {
        if($content instanceof RefProcess) {
            $this->content = $content;
        } else {
            throw new \Exception("content not Plugin\SlnPayment42\Service\SlnContent\Receipt\RefProcess");
        }
    }
    
    /**
     * (non-PHPdoc)
     * @var Plugin\SlnPayment42\Service\SlnContent\Receipt\RefProcess
     */
    public function getContent() {
        return $this->content;
    }
}