<?php
namespace Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Response;

use Plugin\SlnPayment42\Service\SlnAction\Content\Basic;
use Plugin\SlnPayment42\Service\SlnContent\Basic as contentBasic;
use Plugin\SlnPayment42\Service\SlnContent\Receipt\ChgDelProcess;

class Chg extends Basic
{
    
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
            'KessaiNumber' => '',
            'FreeArea' => '',
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
        if($content instanceof ChgDelProcess) {
            $this->content = $content;
        } else {
            throw new \Exception("content not Plugin\SlnPayment42\Service\SlnContent\Receipt\ChgDelProcess");
        }
    }
    
    /**
     * (non-PHPdoc)
     * @var Plugin\SlnPayment42\Service\SlnContent\Receipt\ChgDelProcess
     */
    public function getContent() {
        return $this->content;
    }
}