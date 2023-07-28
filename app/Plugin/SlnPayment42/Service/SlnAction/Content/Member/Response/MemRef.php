<?php

namespace Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response;

class MemRef extends MemInval
{
    public function getDataKey()
    {
        $dataKey = parent::getDataKey();
        $dataKey['CardNo'] = '';
        $dataKey['CardExp'] = '';
        $dataKey['CompanyCd'] = '';
        $dataKey['KaiinStatus'] = '';
        $dataKey['KaiinEnableDate'] = '';
        return $dataKey;
    }
}