<?php
namespace Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Request;

class ReAuth extends Capture
{
    public function getDataKey()
    {
        $dataKey = parent::getDataKey();
        $dataKey['Amount'] = '';
        return $dataKey;
    }
}