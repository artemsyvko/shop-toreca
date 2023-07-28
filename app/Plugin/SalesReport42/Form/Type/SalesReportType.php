<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\SalesReport42\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class SalesReportType.
 */
class SalesReportType extends AbstractType
{
    /**
     * BuildForm Sale Report.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // 年月の配列定義. 今年±20年
        $yearList = range(date('Y'), date('Y') - 20);
        // 1～12月
        $monthList = range(1, 12);

        $builder
            ->add('term_type', HiddenType::class, [
                'required' => false,
            ])
            ->add('monthly_year', ChoiceType::class, [
                'label' => 'sales_report.admin.label.monthly_year',
                'required' => true,
                'choices' => array_combine($yearList, $yearList),
                'data' => date('Y'),
            ])
            ->add('monthly_month', ChoiceType::class, [
                'label' => 'sales_report.admin.label.monthly_month',
                'required' => true,
                'choices' => array_combine($monthList, $monthList),
                'data' => date('n'),
            ])
            ->add('term_start', DateType::class, [
                'label' => 'sales_report.admin.label.term_start',
                'required' => true,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'data' => new \DateTime('first day of this month'),
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_term_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('term_end', DateType::class, [
                'label' => 'sales_report.admin.label.term_end',
                'required' => true,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'data' => new \DateTime(),
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_term_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('unit', ChoiceType::class, [
                'label' => 'sales_report.admin.label.unit',
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'choices' => array_flip([
                    'byDay' => 'sales_report.admin.label.byDay',
                    'byMonth' => 'sales_report.admin.label.byMonth',
                    'byWeekDay' => 'sales_report.admin.label.byWeekDay',
                    'byHour' => 'sales_report.admin.label.byHour',
                ]),
                'data' => 'byDay',
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $form->getData();
                if ($data['term_type'] === 'monthly') {
                    if (empty($data['monthly_year'])) {
                        $form['monthly_year']->addError(
                            new FormError(trans('sales_report.admin.type.monthly.error'))
                        );
                    }
                    if (empty($data['monthly_month'])) {
                        $form['monthly_month']->addError(
                            new FormError(trans('sales_report.admin.type.monthly.error'))
                        );
                    }
                } elseif ($data['term_type'] === 'term' && (empty($data['term_start']) || empty($data['term_end']))) {
                    $form['term_start']->addError(new FormError(trans('sales_report.admin.type.term_start.error')));
                }
            })
        ;
    }

    /**
     * get sale report form name.
     *
     * @return string
     */
    public function getName()
    {
        return 'sales_report';
    }
}
