<?php

namespace Plugin\SlnPayment42\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Plugin\SlnPayment42\Service\BasicItem;

class ConfigType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var BasicItem
     */
    protected $basicItem;

    public function __construct(
        ContainerInterface $container,
        BasicItem $basicItem
    ) {
        $this->container = $container;
        $this->basicItem = $basicItem;
    }
    
    /**
     * Build config type form
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return type
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (empty($this->subData)) {
            $this->subData = array(
                'SecCd' => 1,
                'isSendMail' => 1,
            );
        }
        
        $builder->add('MerchantId', TextType::class, array(
            'label' => 'マーチャントID',
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array('max' => $this->container->getParameter('MERCHANTID_LEN'))),
            ),
            'attr' => array(
                'class' => 'form-control',
            ),
        ))
        ->add('MerchantPass', PasswordType::class, array(
            'label' => 'マーチャントパスワード',
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array('max' => $this->container->getParameter('MERCHANTPASS_LEN'))),
            ),
            'attr' => array(
                'class' => 'form-control',
            ),
        ))
        ->add('TenantId', TextType::class, array(
            'label' => '店舗コード',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('TENPOCODE_LEN'))),
            ),
        ))
        ->add('isSendMail', ChoiceType::class, array(
            'label' => '通信エラーメール送信',
            'choices' => array('送信する' => 1),
            'expanded' => true,
            'multiple' => true,
        ))
        //トーク決済通信先
        ->add('creditConnectionPlace6', TextType::class, array(
            'label' => 'トークン通信先',
            'attr' => array(
                'class' => 'form-control',
                'readonly' => 'readonly',
            ),
            'constraints' => array(
                new Assert\Url(),
            ),
        ))
        //クレジットカード決済
        ->add('creditConnectionDestination', ChoiceType::class, array(
            'label' => 'クレジットカード決済接続先環境',
            'choices' => array('試験環境' => 1, '本番環境' => 2),
            'expanded' => false,
            'multiple' => false,
            'required' => true,
        ))
        ->add('creditConnectionPlace1', TextType::class, array(
            'label' => 'カード取引通信先',
            'attr' => array(
                'class' => 'form-control',
                'readonly' => 'readonly',
            ),
            'constraints' => array(
                new Assert\Url(),
            ),
        ))
        ->add('creditConnectionPlace2', TextType::class, array(
            'label' => '会員情報登録',
            'attr' => array(
                'class' => 'form-control',
                'readonly' => 'readonly',
            ),
            'constraints' => array(
                new Assert\Url(),
            ),
        ))
        ->add('tokenNinsyoCode', TextType::class, array(
            'label' => 'トークン決済認証コード',
            'attr' => array(
                'class' => 'form-control',
            ),
        ))
        ->add('cardOrderPreEnd', ChoiceType::class, array(
            'label' => '受注入金済み手続き',
            'choices' => $this->basicItem->getGatheringOrderStatus(),
            'constraints' => array(
                new Assert\NotBlank(),
            ),
            'expanded' => true,
        ))
        ->add('payKbnKaisu', ChoiceType::class, array(
            'label' => '支払回数',
            'choices' => $this->basicItem->getCreditPayMethod(),
            'constraints' => array(
                new Assert\NotBlank(),
            ),
            'expanded' => true,
            'multiple' => true,
        ))
        ->add('SecCd', ChoiceType::class, array(
            'label' => 'セキュリティコード',
            'choices' => $this->basicItem->getSecurityCode(),
            'constraints' => array(
                new Assert\NotBlank(),
            ),
            'expanded' => true,
        ))
        ->add('attestationAssistance', ChoiceType::class, array(
            'label' => '認証アシスト項目',
            'choices' => $this->basicItem->getAssistance(),
            'expanded' => true,
            'multiple' => true,
        ))
        ->add('OperateId', ChoiceType::class, array(
            'label' => 'カード決済手続き',
            'choices' => $this->basicItem->getCardProcedure(),
            'constraints' => array(
                new Assert\NotBlank(),
            ),
            'expanded' => true,
        ))
        ->add('memberRegist', ChoiceType::class, array(
            'label' => '会員登録機能',
            'choices' => $this->basicItem->getMemberRegist(),
            'constraints' => array(
                new Assert\NotBlank(),
            ),
            'expanded' => true,
        ))
        //オンライン収納代行
        ->add('cvsConnectionDestination', ChoiceType::class, array(
            'label' => 'オンライン収納代行接続先環境',
            'choices' => array('試験環境' => 1, '本番環境' => 2),
            'expanded' => false,
            'multiple' => false,
            'required' => true,
        ))
        ->add('creditConnectionPlace5', TextType::class, array(
            'label' => 'オンライン取引',
            'attr' => array(
                'class' => 'form-control',
                'readonly' => 'readonly',
            ),
            'constraints' => array(
                new Assert\Url(),
            ),
        ))
        ->add('quickAccounts', ChoiceType::class, array(
            'label' => 'クイック決済',
            'choices' => $this->basicItem->getQuickAccounts(),
            'constraints' => array(
                new Assert\NotBlank(),
            ),
            'expanded' => true,
        ))
        ->add('creditConnectionPlace3', TextType::class, array(
            'label' => 'リダイレクト先',
            'attr' => array(
                'class' => 'form-control',
                'readonly' => 'readonly',
            ),
            'constraints' => array(
                new Assert\Url(),
            ),
        ))
        ->add('OnlinePaymentMethod', ChoiceType::class, array(
            'label' => '利用できるオンライン収納決済方法',
            'choices' => $this->basicItem->getOnlinePaymentMethod(),
            'expanded' => true,
            'multiple' => true,
        ))
        ->add('Free1', TextType::class, array(
            'label' => 'フリーエリア1',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('FREEAREA_LEN'))),
            ),
        ))
        ->add('Free2', TextType::class, array(
            'label' => 'フリーエリア2',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('FREEAREA_LEN'))),
            ),
        ))
        ->add('Free3', TextType::class, array(
            'label' => 'フリーエリア3',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('FREEAREA_LEN'))),
            ),
        ))
        ->add('Free4', TextType::class, array(
            'label' => 'フリーエリア4',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('FREEAREA_LEN'))),
            ),
        ))
        ->add('Free5', TextType::class, array(
            'label' => 'フリーエリア5',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('FREEAREA_LEN'))),
            ),
        ))
        ->add('Free6', TextType::class, array(
            'label' => 'フリーエリア6',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('FREEAREA_LEN'))),
            ),
        ))
        ->add('Free7', TextType::class, array(
            'label' => 'フリーエリア7',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('FREEAREA_LEN'))),
            ),
        ))
        ->add('Comment', TextType::class, array(
            'label' => 'ご案内1',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('ANNAI_LEN'))),
            ),
        ))
        ->add('Free8', TextType::class, array(
            'label' => 'ご案内2',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('ANNAI_LEN'))),
            ),
        ))
        ->add('Free9', TextType::class, array(
            'label' => 'ご案内3',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('ANNAI_LEN'))),
            ),
        ))
        ->add('Free10', TextType::class, array(
            'label' => 'ご案内4',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('ANNAI_LEN'))),
            ),
        ))
        ->add('Free11', TextType::class, array(
            'label' => 'ご案内5',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('ANNAI_LEN'))),
            ),
        ))
        ->add('Free12', TextType::class, array(
            'label' => 'ご案内6',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('ANNAI_LEN'))),
            ),
        ))
        ->add('Free13', TextType::class, array(
            'label' => 'ご案内7',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('ANNAI_LEN'))),
            ),
        ))
        ->add('Free14', TextType::class, array(
            'label' => 'ご案内8',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('ANNAI_LEN'))),
            ),
        ))
        ->add('Free15', TextType::class, array(
            'label' => 'ご案内9',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('ANNAI_LEN'))),
            ),
        ))
        ->add('Free16', TextType::class, array(
            'label' => 'ご案内10',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('ANNAI_LEN'))),
            ),
        ))
        ->add('Free17', TextType::class, array(
            'label' => '問い合わせ先',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('QUESTIONSAKI_LEN'))),
            ),
        ))
        ->add('Free18', TextType::class, array(
            'label' => '問合せ電話',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Regex(array('pattern' => '/^[0-9\-]+$/', 
                        'message' => '半角数字ハイフンで入力してください。',
                    )
                ),
            ),
        ))
        ->add('Free19', TextType::class, array(
            'label' => '問い合わせ時間',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('QUESTIONTIME_LEN'))),
                new Assert\Regex(array('pattern' => '/^[ -\~]+$/',
                    'message' => '半角英数記号で入力してください。',
                )),
            ),
        ))
        ->add('Title', TextType::class, array(
            'label' => 'ご案内タイトル',
            'attr' => array(
                'class' => 'form-control',
            ),
            'constraints' => array(
                new Assert\Length(array('max' => $this->container->getParameter('CUSTANNNAITITLE_LEN'))),
            ),
        ))
        
        ;

        if (extension_loaded('openssl')) {
            $builder->add('threedPay', ChoiceType::class, array(
                'label' => '3Dセキュアサービス',
                'choices' => $this->basicItem->get3DPay(),
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
                'expanded' => true,
            ))
            ->add('threedConnectionDestination', ChoiceType::class, array(
                'label' => '3Dセキュアサービス接続先環境',
                'choices' => array('試験環境' => 1, '本番環境' => 2),
                'expanded' => false,
                'multiple' => false,
                'required' => true,
            ))
            ->add('creditConnectionPlace7', TextType::class, array(
                'label' => '3Dセキュア認証接続先',
                'attr' => array(
                    'class' => 'form-control',
                    'readonly' => 'readonly',
                ),
            ))
            ->add('creditAesKey', TextType::class, array(
                'label' => '3Dセキュア認証AES暗号キー',
                'attr' => array(
                    'class' => 'form-control',
                ),
            ))
            ->add('creditAesIv', TextType::class, array(
                'label' => '3Dセキュア認証AESベクトル',
                'attr' => array(
                    'class' => 'form-control',
                ),
            ))
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                $form = $event->getForm();
                if (is_null($form['threedPay']->getData())) {
                    $form['threedPay']->setData(1);
                }
            })
            ;
        }
    }
    
    public function getName()
    {
        return 'config';
    }
}
