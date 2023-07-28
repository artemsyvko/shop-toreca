<?php

namespace Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Response;

use Plugin\SlnPayment42\Service\SlnContent\Basic as contentBasic;
use Plugin\SlnPayment42\Service\SlnContent\Credit\ThreeDMaster;

class ThreeDAuth extends Auth
{
    public function getDataKey()
    {
        $dataKey = parent::getDataKey();
        $dataKey['SecureResultCode'] = '';
        $dataKey['MessageVersionNo3D'] = '';
        $dataKey['DSTransactionId'] = '';
        $dataKey['ThreeDSServerTransactionId'] = '';
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
            throw new \Exception("content not Plugin\SlnPayment42\Service\SlnContent\Credit\Master");
        }
    }
}