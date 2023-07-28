<?php

namespace Plugin\SlnPayment42\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class RecvType extends AbstractType
{   
    /**
     * Build config type form
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return type
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('MerchantId', TextType::class, array(
            'label' => 'マーチャントID',
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array('max' => $this->app['config']['SlnPayment42']['const']['MERCHANTID_LEN'])),
            ),
        ))
        ->add('TransactionId', TextType::class, array(
            'label' => '処理通番',
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array('max' => 27)),
            ),
        ))
        ->add('CvsCd', TextType::class, array(
            'label' => '収納機関コード',
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array('max' => 5)),
            ),
        ))
        ->add('OperateId', TextType::class, array(
            'label' => '処理区分',
            'constraints' => array(
                new Assert\NotBlank(),
            ),
        ));
    }
    
    public function getName()
    {
        return 'recv';
    }
}
