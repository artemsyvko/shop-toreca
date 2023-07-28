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

namespace Plugin\DeliveryDate42\Service;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Delivery;
use Eccube\Entity\Shipping;
use Eccube\Event\EventArgs;
use Plugin\DeliveryDate42\Repository\ConfigRepository;
use Plugin\DeliveryDate42\Repository\DeliveryDateRepository;
use Plugin\DeliveryDate42\Repository\HolidayRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShoppingService
{
    private $eccubeConfig;
    private $configRepository;
    private $deliveryDateRepository;
    private $holidayRepository;
    private $eventDispatcher;

    public function __construct(
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository,
        DeliveryDateRepository $deliveryDateRepository,
        HolidayRepository $holidayRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->configRepository = $configRepository;
        $this->deliveryDateRepository = $deliveryDateRepository;
        $this->holidayRepository = $holidayRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getFormDeliveryDates(Shipping $Shipping, Delivery $Delivery)
    {
        // お届け日の設定
        $minDate = 0;
        $deliveryDateFlag = true;
        $deliveryDates = [];

        // 配送時に最大となる商品日数を取得
        foreach ($Shipping->getOrderItems() as $orderItem) {
            $ProductClass = $orderItem->getProductClass();
            if(is_null($ProductClass))continue;
            $days = $ProductClass->getDeliveryDateDays();
            if (!is_null($days)) {
                if ($minDate < $days) {
                    $minDate = $days;
                }
            }else{
                $deliveryDateFlag = false;
                break;
            }
        }

        if($deliveryDateFlag){
            $Method = $this->configRepository->findOneBy(['name' => 'method']);
            $AcceptTime = $this->configRepository->findOneBy(['name' => 'accept_time']);
            $SameDayFlg = $this->configRepository->findOneBy(['name' => 'same_day_flg']);
            if($AcceptTime){
                $time = (int)$AcceptTime->getValue();
                if(!is_null($SameDayFlg)){
                    $flg = $SameDayFlg->getValue() ? true : $minDate == 0;
                }else{
                    $flg = $minDate == 0 ? true : false;
                }
                if($flg && $time > 0){
                    $isHoliday = false;
                    if($Method){
                        if($Method->getValue() != 1){
                            $date = new \DateTime();
                            if($this->holidayRepository->checkHoliday($date)){
                                $isHoliday = true;
                            }
                        }
                    }
                    if(!$isHoliday){
                        $now = getdate();
                        if($now['hours'] >= $time){
                            $minDate++;
                        }
                    }
                }
            }

            // 発送までの日数を計算
            if($Method){
                if($Method->getValue() != 1){
                    $shippingDate = $minDate;
                    $i=0;
                    while($shippingDate >= 0){
                        $date = new \DateTime($i . 'day');
                        if($this->holidayRepository->checkHoliday($date)){
                            $minDate++;
                        }else{
                            $shippingDate--;
                        }
                        $i++;
                    }
                }
            }

            // 都道府県ごとの配送日数を加算
            $DeliveryDate = $this->deliveryDateRepository->findOneBy([
                'Delivery' => $Delivery,
                'Pref' => $Shipping->getPref(),
            ]);
            if($DeliveryDate){
                $dates = $DeliveryDate->getDates();
                if(!is_null($dates)){
                    $minDate += $dates;
                }else{
                    return [];
                }
            }else{
                return [];
            }

            // 配達最大日数期間を設定
            $period = new \DatePeriod (
                new \DateTime($minDate . ' day'),
                new \DateInterval('P1D'),
                new \DateTime($minDate + $this->eccubeConfig['eccube_deliv_date_end_max'] . ' day')
            );

            $dateFormatter = \IntlDateFormatter::create(
                'ja_JP@calendar=japanese',
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                'Asia/Tokyo',
                \IntlDateFormatter::TRADITIONAL,
                'E'
            );

            foreach ($period as $day) {
                $deliveryDates[$day->format('Y/m/d')] = $day->format('Y/m/d').'('.$dateFormatter->format($day).')';
            }

            $event = new EventArgs(
                [
                    'deliveryDates' => $deliveryDates,
                    'Shipping' => $Shipping,
                ]
            );
            $this->eventDispatcher->dispatch($event,'deliverydate.getformdeliverydates');
            $deliveryDates = $event->getArgument('deliveryDates');
        }

        return $deliveryDates;

    }
}
