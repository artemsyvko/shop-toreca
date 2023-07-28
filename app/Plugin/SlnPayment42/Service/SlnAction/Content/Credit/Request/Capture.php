<?php
namespace Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Request;

use Plugin\SlnPayment42\Service\SlnAction\Content\Basic;
use Plugin\SlnPayment42\Service\SlnContent\Basic as contentBasic;
use Plugin\SlnPayment42\Service\SlnContent\Credit\Process;

class Capture extends Basic
{
    /**
     * @var Master
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
            'ProcessId' => '',
            'ProcessPass' => '',
            'KaiinId' => '',
            'KaiinPass' => '',
            'SalesDate' => '',
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
        if($content instanceof Process) {
            $this->content = $content;
        } else {
            throw new \Exception("content not Plugin\SlnPayment42\Service\SlnContent\Credit\Process");
        }
    }
    
    /**
     * (non-PHPdoc)
     * @var Plugin\SlnPayment42\Service\SlnContent\Credit\Process
     */
    public function getContent() {
        return $this->content;
    }
}