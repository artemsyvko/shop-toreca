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

namespace Plugin\RelatedProduct42\Form\Type\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Product;
use Eccube\Form\DataTransformer\EntityToIdTransformer;
use Plugin\RelatedProduct42\Entity\RelatedProduct;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class RelatedProductType.
 */
class RelatedProductType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * RelatedProductType constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        EccubeConfig $eccubeConfig
    ) {
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * RelatedProduct form builder.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                $builder->create('ChildProduct', HiddenType::class, [
                    'label' => '関連商品',
                    'required' => false,
                ])->addModelTransformer(new EntityToIdTransformer($this->entityManager, Product::class))
            )->add('content', TextareaType::class, [
                'label' => '説明文',
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['related_product.text_area_len'],
                    ]),
                ],
                'attr' => [
                    'maxlength' => $this->eccubeConfig['related_product.text_area_len'],
                    'placeholder' => trans('related_product.type.comment.placeholder'),
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RelatedProduct::class,
        ]);
    }
}
