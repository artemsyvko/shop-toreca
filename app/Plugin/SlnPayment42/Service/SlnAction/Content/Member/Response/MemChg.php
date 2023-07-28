<?php

namespace Plugin\SlnPayment42\Service\SlnAction\Content\Member\Response;

class MemChg extends MemAdd
{
    public function getDataKey()
    {
        $dataKey = parent::getDataKey();
        $dataKey['NewKaiinPass'] = '';
        return $dataKey;
    }
}