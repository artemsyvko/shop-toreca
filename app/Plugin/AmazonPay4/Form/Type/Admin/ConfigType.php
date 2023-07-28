<?php
namespace Plugin\AmazonPay4\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Plugin\AmazonPay4\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ConfigType extends AbstractType{
    protected $eccubeConfig;

    public function __construct(EccubeConfig $eccubeConfig){$this->eccubeConfig = $eccubeConfig;}

    public function buildForm(FormBuilderInterface $builder, array $options){
        $builder
            ->add('env', ChoiceType::class, [
                'choices' => [
                    'テスト環境' => $this->eccubeConfig['amazon_pay4']['env']['sandbox'],
                    '本番環境' => $this->eccubeConfig['amazon_pay4']['env']['prod']],
                'multiple' => false, 'expanded' => true])
            ->add('seller_id', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Callback(function ($objcet, ExecutionContextInterface $context) {
                        if (!$objcet){
                            $context->buildViolation('※ 出品者IDが入力されていません。')->atPath('seller_id')->addViolation();
                        }
                    }),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']])
                ]

            ])
            ->add('public_key_id', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Callback(function ($objcet, ExecutionContextInterface $context) {
                        if (!$objcet){
                            $context->buildViolation('※ パブリックキーIDが入力されていません。')->atPath('public_key_id')->addViolation();
                        }
                    }),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']])
                ]
            ])
            ->add('private_key_path', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Callback(function ($objcet, ExecutionContextInterface $context) {
                        if(!$objcet){
                            $context->buildViolation('※ プライベートキーパスが入力されていません。')->atPath('private_key_path')->addViolation();
                        }
                    }),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_smtext_len']])
                ]
            ])
            ->add('client_id', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Callback(function ($objcet, ExecutionContextInterface $context) {
                        if (!$objcet)
                        {
                            $context->buildViolation('※ クライアントIDが入力されていません。')->atPath('client_id')->addViolation();
                        }
                    }),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_smtext_len']])
                ]
            ])
            ->add('sale', ChoiceType::class, [
                'choices' => [
                    '仮売上' => $this->eccubeConfig['amazon_pay4']['sale']['authori'],
                    '売上' => $this->eccubeConfig['amazon_pay4']['sale']['capture']],
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ 仮売上 or 売上が選択されていません。'])
                ],
                'multiple' => false,
                'expanded' => true
            ])

            ->add('use_confirm_page', ChoiceType::class, [
                'choices' => [
                    '表示' => $this->eccubeConfig['amazon_pay4']['toggle']['on'],
                    '非表示' => $this->eccubeConfig['amazon_pay4']['toggle']['off']
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ 決済確認画面が選択されていません。'])
                ],
                'multiple' => false,
                'expanded' => true
            ])
            ->add('auto_login', ChoiceType::class, [
                'choices' => [
                    'オン' => $this->eccubeConfig['amazon_pay4']['toggle']['on'],
                    'オフ' => $this->eccubeConfig['amazon_pay4']['toggle']['off']
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ 自動ログインが選択されていません。'])
                ],
                'multiple' => false,
                'expanded' => true
            ])
            ->add('login_required', ChoiceType::class, [
                'choices' => [
                    'オン' => $this->eccubeConfig['amazon_pay4']['toggle']['on'],
                    'オフ' => $this->eccubeConfig['amazon_pay4']['toggle']['off']
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ ログイン・会員登録必須が選択されていません。'])
                ],
                'multiple' => false,
                'expanded' => true
            ])
            ->add('order_correct', ChoiceType::class, [
                'choices' => [
                    'オン' => $this->eccubeConfig['amazon_pay4']['toggle']['on'],
                    'オフ' => $this->eccubeConfig['amazon_pay4']['toggle']['off']
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ 受注補正機能が選択されていません。'])
                ],
                'multiple' => false,
                'expanded' => true
            ])
            ->add('mail_notices', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_ltext_len']])
                ]
            ])
            ->add('use_cart_button', ChoiceType::class, [
                'choices' => [
                    'オン' => $this->eccubeConfig['amazon_pay4']['toggle']['on'],
                    'オフ' => $this->eccubeConfig['amazon_pay4']['toggle']['off']
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ Amazonボタン設置(カート画面)が選択されていません。'])
                ],
                'multiple' => false,
                'expanded' => true
            ])
            ->add('cart_button_color', ChoiceType::class, [
                'choices' => [
                    'ゴールド' => 'Gold',
                    'ダークグレー' => 'DarkGray',
                    'ライトグレー' => 'LightGray'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ Amazonログインボタンカラー(カート)が選択されていません。'])
                ],
                'multiple' => false,
                'expanded' => true
            ])
            ->add('cart_button_place', ChoiceType::class, [
                'choices' => [
                    '自動' => $this->eccubeConfig['amazon_pay4']['button_place']['auto'],
                    '手動' => $this->eccubeConfig['amazon_pay4']['button_place']['manual']
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ Amazonログインボタン配置(カート画面)が選択されていません。'])
                ],
                'multiple' => false,
                'expanded' => true
            ])
            ->add('use_mypage_login_button', ChoiceType::class, [
                'choices' => [
                    'オン' => $this->eccubeConfig['amazon_pay4']['toggle']['on'],
                    'オフ' => $this->eccubeConfig['amazon_pay4']['toggle']['off']
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => '※「LoginWithAmazon」ボタン設置(MYページ/ログイン)が選択されていません。'])
                ],
                'multiple' => false,
                'expanded' => true
            ])
            ->add('mypage_login_button_color', ChoiceType::class, [
                'choices' => [
                    'ゴールド' => 'Gold',
                    'ダークグレー' => 'DarkGray',
                    'ライトグレー' => 'LightGray'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => '※「LoginWithAmazon」ボタンカラー(MYページ/ログイン)が選択されていません。'])
                ],
                'multiple' => false,
                'expanded' => true
            ])
            ->add('mypage_login_button_place', ChoiceType::class, [
                'choices' => [
                    '自動' => $this->eccubeConfig['amazon_pay4']['button_place']['auto'],
                    '手動' => $this->eccubeConfig['amazon_pay4']['button_place']['manual']
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => '※「LoginWithAmazon」ボタン配置(MYページ/ログイン)が選択されていません。'])
                ],
                'multiple' => false,
                'expanded' => true
            ]);
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => Config::class]);
    }
}