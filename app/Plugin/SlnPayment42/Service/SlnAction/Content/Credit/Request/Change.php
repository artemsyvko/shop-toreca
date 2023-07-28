<?php
namespace Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Request;

class Change extends Capture
{
    public function getDataKey()
    {
        $dataKey = parent::getDataKey();
        unset($dataKey['SalesDate']);
        $dataKey['Amount'] = '';
        return $dataKey;
    }
}