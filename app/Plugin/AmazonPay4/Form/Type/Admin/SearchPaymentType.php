<?php

namespace Plugin\AmazonPay4\Form\Type\Admin;

use Doctrine\ORM\EntityRepository;
use Eccube\Form\Type\Master\OrderStatusType;
use Plugin\AmazonPay4\Entity\Master\AmazonStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SearchPaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('OrderStatuses', OrderStatusType::class, [
                'multiple' => true,
                'expanded' => true
            ])
            ->add('AmazonStatuses', EntityType::class, [
                'class' => AmazonStatus::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')->orderBy('p.id', 'ASC');
                },
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true

            ]);
    }
}