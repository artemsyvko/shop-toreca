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

namespace Plugin\DeliveryDate42\Controller\Block;

use Eccube\Controller\AbstractController;
use Plugin\DeliveryDate42\Repository\ConfigRepository;
use Plugin\DeliveryDate42\Repository\HolidayRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CalendarController extends AbstractController
{
    private $configRepository;

    private $holidayRepository;

    public function __construct(
            ConfigRepository $configRepository,
            HolidayRepository $holidayRepository
            )
    {
        $this->configRepository = $configRepository;
        $this->holidayRepository = $holidayRepository;
    }

    /**
     * @Route("/block/businessday_calendar", name="block_businessday_calendar")
     * @Template("Block/businessday_calendar.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->findOneBy(['name' => 'calendar_month']);
        if($Config){
            $months = $Config->getValue();
        }else{
            $months = 2;
        }

        $Date = [];
        for($i=0 ; $i<$months ; $i++){
            $month = new \DateTime();
            $Month = $month->modify('first day of this month')->modify('+' . $i . 'month')->format('Y-m');
            $start = new \DateTime();
            $start->modify('first day of '. $Month);
            $end = new \DateTime();
            $end->modify('last day of '. $Month);
            $end->modify('+1 day');
            $period = new \DatePeriod (
                $start,
                new \DateInterval('P1D'),
                $end
            );

            foreach ($period as $day) {
                $Date[$day->format('n')][$day->format('d')]['day'] = $day;
                $Holiday = $this->holidayRepository->getHoliday($day);
                if($Holiday){
                    $Date[$day->format('n')][$day->format('d')]['is_holiday'] = true;
                }else{
                    $Date[$day->format('n')][$day->format('d')]['is_holiday'] = false;
                }
            }
        }

        return [
            'Date' => $Date,
        ];
    }
}