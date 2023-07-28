<?php

namespace Plugin\AmazonPay4\Form\Extension;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Form\Type\Shopping\OrderType;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\PluginRepository;
use Plugin\AmazonPay4\Service\Method\AmazonPay;
use Plugin\AmazonPay4\Repository\ConfigRepository;
//use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AmazonCustomerExtension extends AbstractTypeExtension
{
   // use ControllerTrait;
    protected $paymentRepository;
    protected $eccubeConfig;
    protected $configRepository;
    protected $container;
    protected $pluginRepository;
    protected $Config;
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;
    public function __construct(
        EccubeConfig $eccubeConfig,
        PaymentRepository $paymentRepository,
        PluginRepository $pluginRepository,
        ConfigRepository $configRepository,
        AuthorizationCheckerInterface $authorizationChecker,
        ContainerInterface $container
    ){
        $this->paymentRepository = $paymentRepository;
        $this->pluginRepository = $pluginRepository;
        $this->eccubeConfig = $eccubeConfig;
        $this->configRepository = $configRepository;
        $this->Config = $this->configRepository->get();
        $this->authorizationChecker = $authorizationChecker;
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $self = $this;

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use($self) {
            $data = $event->getData();
            $form = $event->getForm();
            if ($data->getPayment()) {
                if ($data->getPayment()->getMethodClass() === AmazonPay::class && !$this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
                    $form->add('customer_regist', CheckboxType::class, [
                        'label' => null,
                        'required' => false,
                        'mapped' => false,
                        'attr' => ['autocomplete' => 'off']
                    ]);
                    if ($this->pluginRepository->findOneBy(['code' => 'MailMagazine4', 'enabled' => true]) || $this->pluginRepository->findOneBy(['code' => 'PostCarrier4', 'enabled' => true])) {
                        $form->add('mail_magazine', CheckboxType::class, [
                            'label' => null,
                            'required' => false,
                            'mapped' => false,
                            'attr' => [
                                'autocomplete' => 'off'
                            ]
                        ]);
                    }
                    if ($this->Config->getLoginRequired() == $this->eccubeConfig['amazon_pay4']['toggle']['on'] && !$this->isGranted('IS_AUTHENTICATED_FULLY')) {
                        $form
                            ->add('login_check', ChoiceType::class, [
                                'choices' => [
                                    'まだ会員登録されていないお客様' => 'regist',
                                    '会員登録がお済みのお客様' => 'login'
                                ],
                                'mapped' => false,
                                'expanded' => true
                            ])
                            ->add('amazon_login_email', TextType::class, [
                                'mapped' => false, 'required' => false,
                                'attr' => [
                                    'class' => 'form-control',
                                    'max_length' => 50
                                ]
                            ])
                            ->add('amazon_login_password', PasswordType::class, [
                                'mapped' => false,
                                'required' => false,
                                'always_empty' => false,
                                'attr' => [
                                    'class' => 'form-control',
                                    'max_length' => 50
                                ]
                            ])
                        ;
                    }
                }
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $options = $event->getForm()->getConfig()->getOptions();
            if ($options['skip_add_form']) {
                return;
            }
            $Payment = $this->paymentRepository->findOneBy(['method_class' => AmazonPay::class]);
            $data = $event->getData();
            $form = $event->getForm();
            if (!empty($data['Payment']) && $Payment->getId() != $data['Payment']) {
                $form
                    ->remove('customer_regist')
                    ->remove('login_check')
                    ->remove('amazon_login_email')
                    ->remove('amazon_login_password')
                ;
            }
        });
    }

    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class];
    }
}