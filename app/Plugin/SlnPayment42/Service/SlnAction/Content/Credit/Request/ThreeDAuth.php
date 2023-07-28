<?php

namespace Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Request;

use Plugin\SlnPayment42\Service\SlnContent\Basic as contentBasic;
use Plugin\SlnPayment42\Service\SlnContent\Credit\ThreeDMaster;

class ThreeDAuth extends Auth 
{
    public function getDataKey()
    {
        $dataKey = parent::getDataKey();
        $dataKey['PayType'] = '';
        $dataKey['Amount'] = '';
        $dataKey['ProcNo'] = '';
        $dataKey['RedirectUrl'] = '';
        $dataKey['PostUrl'] = '';
        return $dataKey;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Basic::setContent()
     */
    public function setContent(contentBasic $content) {
        if($content instanceof ThreeDMaster) {
            $this->content = $content;
        } else {
            throw new \Exception("content not Plugin\SlnPayment42\Service\SlnContent\Credit\ThreeDMaster");
        }
    }
}