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

namespace Plugin\MailMagazine42\Form\Extension;

use Eccube\Entity\Customer;
use Eccube\Common\Constant;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Eccube\Form\Type\Admin\CustomerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CustomerMailMagazineTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $mailmagaFlg = null;

        /** @var Customer $Customer */
        $Customer = $builder->getData();
        if ($Customer instanceof Customer && $Customer->getId()) {
            $mailmagaFlg = $Customer->getMailmagaFlg();
        }

        $options = [
            'label' => 'admin.mailmagazine.customer.label_mailmagazine',
            'choices' => [
                'admin.mailmagazine.customer.label_mailmagazine_yes' => Constant::ENABLED,
                'admin.mailmagazine.customer.label_mailmagazine_no' => Constant::DISABLED,
            ],
            'expanded' => true,
            'multiple' => false,
            'required' => true,
            'constraints' => [
                new Assert\NotBlank(),
            ],
            'mapped' => true,
            'eccube_form_options' => [
                'auto_render' => true,
                'form_theme' => '@MailMagazine42/admin/mailmagazine.twig',
            ],
        ];

        if (!is_null($mailmagaFlg)) {
            $options['data'] = $mailmagaFlg;
        }

        $builder->add('mailmaga_flg', ChoiceType::class, $options);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getExtendedType()
    {
        return CustomerType::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public static function getExtendedTypes(): iterable
    {
        yield CustomerType::class;
    }
}
