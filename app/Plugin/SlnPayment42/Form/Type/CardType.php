<?php

namespace Plugin\SlnPayment42\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Plugin\SlnPayment42\Repository\PluginConfigRepository;

class CardType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var PluginConfigRepository
     */
    protected $configRepository;

    /**
     * CardType constructor.
     *
     * @param PluginConfigRepository $configRepository
     * @param ContainerInterface $container;
     */
    public function __construct(PluginConfigRepository $configRepository, ContainerInterface $container)
    {
        $this->configRepository = $configRepository;
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $configRepository = $this->configRepository;

        $years = array();
        $tmp = range(date('Y'), date('Y') + 10);
        foreach ($tmp as $data) {
            $years[$data] = $data;
        }
        
        $month = array();
        $tmp = range(1, 12);
        foreach ($tmp as $data) {
            $month[$data] = $data;
        }
        
        // 3D決済判断
        $is3DPay = false;//トークン決済と3D機能併用
        
        $CardNo = array(
            'label' => 'カード番号',
            'attr' => array(
                'maxlength' => 16,
            ),
            'constraints' => array(
                new Assert\Length(array('max' => 16)),
                new Assert\Regex(array('pattern' => '/^[0-9]+$/', 
                        'message' => '半角数字で入力してください。',
                    )
                ),
            ),
            'required' => false,
        );
        
        if ($is3DPay) {
            $CardNo['constraints'][] = new Assert\NotBlank();
        }

        $CardExpYear = array(
            'label' => 'カード有効期限(年)',
            'choices' => $years,
            'constraints' => array(
                new Assert\Regex(array('pattern' => '/^[0-9]+$/', 
                        'message' => '半角数字で入力してください。',
                    )
                ),
                new Assert\Length(array('max' => 4)),
            ),
            'expanded' => false,
            'multiple' => false,
            'required' => false,
        );
        
        $CardExpMonth = array(
            'label' => 'カード有効期限(月)',
            'choices' => $month,
            'constraints' => array(
                new Assert\Regex(array('pattern' => '/^[0-9]+$/', 
                        'message' => '半角数字で入力してください。',
                    )
                ),
                new Assert\Length(array('max' => 2)),
            ),
            'expanded' => false,
            'multiple' => false,
            'required' => false,
        );
        
        if ($is3DPay) {
            $CardExpYear['constraints'][] = new Assert\NotBlank();
            $CardExpMonth['constraints'][] = new Assert\NotBlank();
        }
        
        $attAss = $configRepository->getConfig()->getAttestationAssistance();
        
        $KanaSei = array(
            'label' => 'カード名義(姓)',
            'attr' => array(
                'maxlength' => 10,
            ),
            'constraints' => array(
                new Assert\Length(array('max' => 10)),
                new Assert\Regex(array(
                    'pattern' => "/^[ァ-ヶｦ-ﾟー]+$/u",
                )),
            ),
            'required' => false,
        );
        
        $KanaMei = array(
            'label' => 'カード名義(名)',
            'attr' => array(
                'maxlength' => 10,
            ),
            'constraints' => array(
                new Assert\Length(array('max' => 10)),
                new Assert\Regex(array(
                    'pattern' => "/^[ァ-ヶｦ-ﾟー]+$/u",
                )),
            ),
            'required' => false,
        );
        
        if ($is3DPay && in_array('KanaSei', $attAss)) {
            $KanaSei['constraints'][] = new Assert\NotBlank();
            $KanaMei['constraints'][] = new Assert\NotBlank();
        }
        
        $SecCd = array(
            'label' => 'セキュリティコード',
            'attr' => array(
                'maxlength' => 4,
                'size' => 4,
                'autocomplete' => 'off',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => 4)),
                new Assert\Regex(array('pattern' => '/^[0-9]+$/',
                        'message' => '半角数字で入力してください。',
                    )
                ),
            ),
            'required' => false,
        );
        
        if ($is3DPay && $configRepository->getConfig()->getSecCd() == 1) {
            $SecCd['constraints'][] = new Assert\NotBlank();
        }
        
        $BirthDay = array(
            'label' => '生月日',
            'attr' => array(
                'maxlength' => 4,
            ),
            'constraints' => array(
                new Assert\Length(array('max' => 4, 'min' => 4)),
                new Assert\Regex(array('pattern' => '/^[0-9]+$/',
                        'message' => '半角数字で入力してください。',
                    )
                ),
            ),
            'required' => false,
        );
        
        if ($is3DPay && in_array('BirthDay', $attAss)) {
            $BirthDay['constraints'][] = new Assert\NotBlank();
        }
        
        $TelNo = array(
            'label' => '電話番号(下4桁)',
            'attr' => array(
                'maxlength' => 4,
            ),
            'constraints' => array(
                new Assert\Length(array('max' => 4, 'min' => 4)),
                new Assert\Regex(array('pattern' => '/^[0-9]+$/',
                        'message' => '半角数字で入力してください。',
                    )
                ),
            ),
            'required' => false,
        );
        
        if ($is3DPay && in_array('TelNo', $attAss)) {
            $TelNo['constraints'][] = new Assert\NotBlank();
        }
        
        $token = array(
            'constraints' => array(
                new Assert\NotBlank(),
            ),
            'required' => false,
        );
        
        if ($is3DPay) {
            $token['constraints'] = array();
        }
        
        //支払い回数選択
        $payKbnKaisu = $configRepository->getConfig()->getPayKbnKaisu();
        $payMethod = array_flip($this->container->getParameter('arrPayKbnKaisu'));
        
        $arrPayType = array();
        if (is_array($payKbnKaisu)) {
            foreach ($payKbnKaisu as $value) {
                $arrPayType[$payMethod[$value]] = sprintf("%02d", $value);
            }
        }
        
        $builder
        ->add('CardNo', TextType::class, $CardNo)
        ->add('CardExpYear', ChoiceType::class, $CardExpYear)
        ->add('CardExpMonth', ChoiceType::class, $CardExpMonth)
        ->add('KanaSei', TextType::class, $KanaSei)
        ->add('KanaMei', TextType::class, $KanaMei)
        ->add('SecCd', PasswordType::class, $SecCd)
        ->add('BirthDay', TextType::class, $BirthDay)
        ->add('TelNo', TextType::class, $TelNo)
        ->add('PayType', ChoiceType::class, array(
            'label' => '支払い方法',
            'choices' => $arrPayType,
            'expanded' => false,
            'multiple' => false,
            'constraints' => array(
                new Assert\NotBlank(),
            ),
        ))
        ->add('Token', HiddenType::class, $token)
        ->add('AddMem', ChoiceType::class, array(
            'label' => 'カード情報登録',
            'choices' => array('このカードを登録する。' => 1),
            'expanded' => true,
            'multiple' => true,
            'required' => false,
        ))
        ->addEventListener(FormEvents::POST_SUBMIT, function ($event) {
            $form = $event->getForm();
            $data = $form->getData();
            if (array_key_exists('BirthDay', $data) && $data['BirthDay']) {
                if (!(substr($data['BirthDay'], 0, 2) <= 12 && substr($data['BirthDay'], -2) <= 31)) {
                    $form['BirthDay']->addError(new FormError('生月日を正しく入力ください。'));
                }
            }
            
            if (array_key_exists('CardExpYear', $data) && array_key_exists('CardExpMonth', $data) && $data['CardExpYear'] && $data['CardExpMonth']) {

                if ($data['CardExpYear'] < date('Y')) {
                    $form['CardExpYear']->addError(new FormError('正しく入力ください。'));
                } 
                
                if ($data['CardExpYear'] == date('Y')) {
                    if ($data['CardExpMonth'] < date('m')) {
                        $form['CardExpMonth']->addError(new FormError('正しく入力ください。'));
                    }
                }
            }
        });
    }
    
    public function getName()
    {
        return 'sln_card';
    }
}
