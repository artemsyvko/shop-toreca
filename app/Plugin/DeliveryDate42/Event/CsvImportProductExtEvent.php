<?php
/*
* Plugin Name : DeliveryDate4
*
* Copyright (C) BraTech Co., Ltd. All Rights Reserved.
* http://www.bratech.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\DeliveryDate42\Event;

use Eccube\Event\EventArgs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CsvImportProductExtEvent implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'csvimportproductext.admin.product.csv.import.product.descriptions' => 'hookAdminProductCsvImportProductDescriptions',
            'csvimportproductext.admin.product.csv.import.product.check'=> 'hookAdminProductCsvImportProductCheck',
            'csvimportproductext.admin.product.csv.import.product.process' => 'hookAdminProductCsvImportProductProcess',
        ];
    }

    public function hookAdminProductCsvImportProductDescriptions(EventArgs $event)
    {
        $header = $event->getArgument('header');
        $key = $event->getArgument('key');
        if($key == trans('deliverydate.common.1')){
            $header['description'] = trans('deliverydate.admin.product.product_csv.delivery_date_description');
            $header['required'] = false;
        }

        $event->setArgument('header',$header);
    }

    public function hookAdminProductCsvImportProductCheck(EventArgs $event)
    {
        $row = $event->getArgument('row');
        $lineNo = $event->getArgument('lineNo');
        $errors = $event->getArgument('errors');

        if(isset($row[trans('deliverydate.common.1')])){
            if($row[trans('deliverydate.common.1')] !== '' && (!is_numeric($row[trans('deliverydate.common.1')]) || $row[trans('deliverydate.common.1')] < 0)){
                $message = trans('admin.common.csv_invalid_greater_than_zero', [
                    '%line%' => $lineNo,
                    '%name%' => trans('deliverydate.common.1'),
                ]);
                $errors[] = $message;
            }
        }

        $event->setArgument('errors',$errors);
    }

    public function hookAdminProductCsvImportProductProcess(EventArgs $event)
    {
        $row = $event->getArgument('row');
        $ProductClass = $event->getArgument('ProductClass');

        if(isset($row[trans('deliverydate.common.1')])){
            if($row[trans('deliverydate.common.1')] != ''){
                $ProductClass->setDeliveryDateDays($row[trans('deliverydate.common.1')]);
            }else{
                $ProductClass->setDeliveryDateDays(NULL);
            }
        }
    }
}
