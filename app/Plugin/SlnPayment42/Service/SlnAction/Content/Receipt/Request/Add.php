<?php
namespace Plugin\SlnPayment42\Service\SlnAction\Content\Receipt\Request;

use Plugin\SlnPayment42\Service\SlnAction\Content\Basic;
use Plugin\SlnPayment42\Service\SlnContent\Basic as contentBasic;
use Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster;

class Add extends Basic
{
    /**
     * @var AddMaster
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
            'Amount' => '',
            'PayLimit' => '',
            'NameKanji' => '',
            'NameKana' => '',
            'TelNo' => '',
            'ShouhinName' => '',
            'Free1' => '',
            'Free2' => '',
            'Free3' => '',
            'Free4' => '',
            'Free5' => '',
            'Free6' => '',
            'Free7' => '',
            'Comment' => '',
            'Free8' => '',
            'Free9' => '',
            'Free10' => '',
            'Free11' => '',
            'Free12' => '',
            'Free13' => '',
            'Free14' => '',
            'Free15' => '',
            'Free16' => '',
            'Free17' => '',
            'Free18' => '',
            'Free19' => '',
            'ReturnURL' => '',
            'Title' => '',
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
        if($content instanceof AddMaster) {
            $this->content = $content;
        } else {
            throw new \Exception("content not Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster");
        }
    }
    
    /**
     * (non-PHPdoc)
     * @var Plugin\SlnPayment42\Service\SlnContent\Receipt\AddMaster
     */
    public function getContent() {
        return $this->content;
    }
}