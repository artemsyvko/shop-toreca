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

namespace Plugin\DeliveryDate42\Controller\Admin;

use Plugin\DeliveryDate42\Entity\Holiday;
use Plugin\DeliveryDate42\Form\Type\Admin\HolidaySearchType;
use Plugin\DeliveryDate42\Form\Type\Admin\HolidayType;
use Plugin\DeliveryDate42\Repository\HolidayRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class HolidayController extends \Eccube\Controller\AbstractController
{
    private $holidayRepository;

    public function __construct(
            HolidayRepository $holidayRepository
            )
    {
        $this->holidayRepository = $holidayRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/setting/deliverydate/holiday", name="admin_setting_deliverydate_holiday")
     * @Template("@DeliveryDate42/admin/Setting/Shop/holiday.twig")
     */
    public function index(Request $request)
    {

        $form = $this->formFactory
            ->createBuilder(HolidaySearchType::class)
            ->getForm();

        $holidayForm = null;

        $form->handleRequest($request);
        if($form->isSubmitted()) {
            if($form->isValid()) {
                $arrChoice[Holiday::SUNDAY] = trans('deliverydate.common.sunday');
                $arrChoice[Holiday::MONDAY] = trans('deliverydate.common.monday');
                $arrChoice[Holiday::TUESDAY] = trans('deliverydate.common.tuesday');
                $arrChoice[Holiday::WEDNESDAY] = trans('deliverydate.common.wednesday');
                $arrChoice[Holiday::THURSDAY] = trans('deliverydate.common.thursday');
                $arrChoice[Holiday::FRIDAY] = trans('deliverydate.common.friday');
                $arrChoice[Holiday::SATURDAY] = trans('deliverydate.common.saturday');
                $month = $form->get('month')->getData();

                if(strlen($month) > 0){
                    $end = new \DateTime(($month.'/01'));
                    $end->modify('last day of this months');
                    $end->modify('+1 day');
                    $period = new \DatePeriod (
                        new \DateTime($month . '/01' ),
                        new \DateInterval('P1D'),
                        $end
                    );

                    $Holidays = new \Doctrine\Common\Collections\ArrayCollection();
                    foreach ($period as $day) {
                        $Holiday = $this->holidayRepository->getHoliday($day);
                        if($Holiday){
                            $Holiday->setAdd(true);
                        }else{
                            $Holiday = new Holiday();
                            $Holiday->setAdd(false);
                        }
                        $Holiday->setDate($day->format('Y/m/d'));

                        $Holidays->add($Holiday);
                    }

                    $builder = $this->formFactory->createBuilder();

                    $builder
                        ->add('check_day', Type\ChoiceType::class, [
                            'choices' => array_flip($arrChoice),
                            'expanded' => true,
                            'multiple'=> true,
                            'mapped' => false,
                            'required' => false,
                        ])
                        ->add('holidays', Type\CollectionType::class, [
                            'entry_type' => HolidayType::class,
                            'allow_add' => true,
                            'allow_delete' => true,
                            'data' => $Holidays,
                        ]);

                    $holidayForm = $builder->getForm()->createView();
                }


            }
        }

        return [
            'form' => $form->createView(),
            'holidayForm' => $holidayForm,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/setting/deliverydate/holiday/edit", name="admin_setting_deliverydate_holiday_edit")
     */
    public function edit(Request $request)
    {

        $builder = $this->formFactory->createBuilder();

        $builder
            ->add('holidays', Type\CollectionType::class, [
                'entry_type' => HolidayType::class,
                'allow_add' => true,
                'allow_delete' => true,
            ]);

        $form = $builder->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            foreach ($form->get('holidays') as $formData) {
                $data = $formData->getData();
                $day = new \DateTime($data->getDate() , new \DateTimeZone('UTC'));
                $Holiday = $this->holidayRepository->getHoliday($day);
                if ($data->getAdd()) {
                    if ($formData->isValid()) {
                        if(!$Holiday){
                            $Holiday = $data;
                            $Holiday->setDate($day);
                        }
                        $Holiday->setTitle($data->getTitle());
                        $this->entityManager->persist($Holiday);
                    }
                }else{
                    if($Holiday){
                        $this->entityManager->remove($Holiday);
                    }
                }
            }

            $this->entityManager->flush();
        }

        $this->addSuccess('admin.deliverydate.holiday.save.complete', 'admin');
        return $this->redirectToRoute('admin_setting_deliverydate_holiday');
    }
}