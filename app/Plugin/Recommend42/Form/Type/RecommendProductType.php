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

namespace Plugin\Recommend42\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormEvents;
use Eccube\Form\DataTransformer;

/**
 * Class RecommendProductType.
 */
class RecommendProductType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * RecommendProductType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EccubeConfig $eccubeConfig, EntityManagerInterface $entityManager)
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->entityManager = $entityManager;
    }

    /**
     * Build config type form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', TextType::class, [
                'label' => 'plugin_recommend.admin.edit.product',
                'required' => false,
                'attr' => ['readonly' => 'readonly'],
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'plugin_recommend.admin.edit.comment',
                'required' => true,
                'trim' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'max' => $this->eccubeConfig['plugin_recommend.text_area_len'],
                    ]),
                ],
                'attr' => [
                    'maxlength' => $this->eccubeConfig['plugin_recommend.text_area_len'],
                    'placeholder' => 'plugin_recommend.admin.type.comment.placeholder',
                ],
            ]);

        $builder->add(
            $builder
                ->create('Product', HiddenType::class)
                ->addModelTransformer(new DataTransformer\EntityToIdTransformer($this->entityManager, '\Eccube\Entity\Product'))
        );

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $form->getData();
            // Check product
            $Product = $data['Product'];
            if (empty($Product)) {
                $form['comment']->addError(new FormError(trans('plugin_recommend.admin.type.product.not_found')));

                return;
            }
        });
    }

    /**
     *  {@inheritdoc}
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Plugin\Recommend42\Entity\RecommendProduct',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'admin_recommend';
    }
}
