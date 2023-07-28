<?php

namespace Plugin\AmazonPay4\Form\Extension;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Payment;
use Eccube\Exception\ShoppingException;
use Eccube\Form\Type\Shopping\ShippingType;
use Eccube\Repository\PaymentOptionRepository;
use Plugin\AmazonPay4\Service\Method\AmazonPay;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\NotBlank;

class ShippingTypeExtension extends AbstractTypeExtension
{
    protected $requestStack;
    protected $paymentOptionRepository;
    protected $eccubeConfig;

    public function __construct(
        RequestStack $requestStack,
        PaymentOptionRepository $paymentOptionRepository,
        EccubeConfig $eccubeConfig
    ){
        $this->requestStack = $requestStack;
        $this->paymentOptionRepository = $paymentOptionRepository;
        $this->eccubeConfig = $eccubeConfig;
    }

    public function buildForm(FormBuilderInterface $builder, array $options){
        $self = $this;
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use($self) {
            $Shipping = $event->getData();
            if (null === $Shipping || !$Shipping->getId()) {
                return;
            }
            $request = $this->requestStack->getMasterRequest();
            $referer = $request->headers->get('referer');
            $Order = $Shipping->getOrder();
            $Payment = $Order->getPayment();
            if ($Payment && $Payment->getMethodClass() === AmazonPay::class && preg_match('/shopping_coupon/', $referer)) {
                return;
            }
            $uri = $request->getUri();

            if (preg_match('/shopping\\/amazon_pay/', $uri) == true) {
                $form = $event->getForm();
                $Deliveries = $this->getDeliveryChoices($form);
                $Deliveries = $this->removeNotAmazonPayChoice($Deliveries);
                if (is_null($Shipping->getDelivery()) && ($Delivery = current($Deliveries))) {
                    $Shipping->setDelivery($Delivery);
                    $Shipping->setShippingDeliveryName($Delivery->getName());
                }
                $this->addDeliveryForm($form, $Deliveries);
            }
        });

    }
    private function getDeliveryChoices(FormInterface $form)
    {
        return $form->get('Delivery')->getConfig()->getAttribute('choice_list')->getChoices();
    }

    private function removeNotAmazonPayChoice($Deliveries){
        foreach ($Deliveries as $key => $Delivery) {
            $PaymentOptions = $Delivery->getPaymentOptions();
            $amazonPayFlg = false;

            foreach ($PaymentOptions as $PaymentOption) {
                $Payment = $PaymentOption->getPayment();
                if ($Payment->getMethodClass() === AmazonPay::class) {
                    $amazonPayFlg = true;
                    break;
                }
            }
            if (!$amazonPayFlg) {
                unset($Deliveries[$key]);
            }
        }
        return $Deliveries;
    }
    private function addDeliveryForm(FormInterface $form, array $choices)
    {
        $form->add('Delivery', EntityType::class, [
            'required' => false,
            'label' => 'shipping.label.delivery_hour',
            'class' => 'Eccube\\Entity\\Delivery',
            'choice_label' => 'name',
            'choices' => $choices,
            'placeholder' => false,
            'constraints' => [new NotBlank()]
        ]);
    }
    public static function getExtendedTypes(): iterable
    {
        return [ShippingType::class];
    }
}