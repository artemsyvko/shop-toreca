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

/*
 * [メルマガ配信]-[配信内容設定]用Form
 */

namespace Plugin\MailMagazine42\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Eccube\Form\Type\Admin\SearchCustomerType;
use Symfony\Component\Validator\Constraints as Assert;

class MailMagazineType extends SearchCustomerType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $constraints = isset($options['eccube_form_options']['constraints'])
            ? $options['eccube_form_options']['constraints']
            : true;

        // 以降テンプレート選択で使用する項目
        $builder->add('id', HiddenType::class)
            ->add('template', MailMagazineTemplateType::class, [
                'label' => 'mailmagazine.select.label_template',
                'required' => false,
                'mapped' => false,
            ])
            ->add('subject', TextType::class, [
                'label' => 'mailmagazine.select.label_subject',
                'required' => true,
                'constraints' => $constraints ? [new Assert\NotBlank()] : [],
            ])
            ->add('body', TextareaType::class, [
                'label' => 'mailmagazine.select.label_body',
                'required' => true,
                'constraints' => $constraints ? [new Assert\NotBlank()] : [],
            ])
            ->add('htmlBody', TextareaType::class, [
                'label' => 'mailmagazine.select.label_body_html',
                'required' => false,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mail_magazine';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'mail_magazine';
    }
}
