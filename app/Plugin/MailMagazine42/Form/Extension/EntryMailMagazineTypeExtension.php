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
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Eccube\Form\Type\Front\EntryType;

class EntryMailMagazineTypeExtension extends AbstractTypeExtension
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * EntryMailMagazineTypeExtension constructor.
     *
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $mailmagaFlg = null;
        $token = $this->tokenStorage->getToken();
        $Customer = $token ? $token->getUser() : null;

        if ($Customer instanceof Customer && $Customer->getId()) {
            $mailmagaFlg = $Customer->getMailmagaFlg();
        }

        $builder
            ->add('mailmaga_flg', ChoiceType::class, [
                'label' => 'admin.mailmagazine.customer.label_mailmagazine',
                'label_attr' => [
                    'class' => 'ec-label',
                ],
                'choices' => [
                    'admin.mailmagazine.customer.label_mailmagazine_yes' => '1',
                    'admin.mailmagazine.customer.label_mailmagazine_no' => '0',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'mapped' => true,
                'data' => $mailmagaFlg,
                'eccube_form_options' => [
                    'auto_render' => true,
                    'form_theme' => '@MailMagazine42/entry_add_mailmaga.twig',
                ],
            ])
            ;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getExtendedType()
    {
        return EntryType::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public static function getExtendedTypes(): iterable
    {
        yield EntryType::class;
    }
}
