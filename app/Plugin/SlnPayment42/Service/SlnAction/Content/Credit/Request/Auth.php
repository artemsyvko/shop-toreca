<?php

namespace Plugin\SlnPayment42\Service\SlnAction\Content\Credit\Request;

class Auth extends Check
{
    public function getDataKey()
    {
        $dataKey = parent::getDataKey();
        $dataKey['PayType'] = '';
        $dataKey['Amount'] = '';
        $dataKey['Token'] = '';
        return $dataKey;
    }
}