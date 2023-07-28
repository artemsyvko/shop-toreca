<?php

namespace Plugin\SlnPayment42\Service\SlnAction\Content\Member\Request;

class ThreeDMemChg extends ThreeDMemAdd
{
    public function getDataKey()
    {
        $dataKey = parent::getDataKey();
        $dataKey['NewKaiinPass'] = '';
        return $dataKey;
    }
}