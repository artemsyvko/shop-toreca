<?php

namespace Plugin\AmazonPay4\Form\Extension;

use Eccube\Entity\Payment;
use Eccube\Form\Type\Shopping\OrderType;
use Plugin\AmazonPay4\Service\Method\AmazonPay;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderTypeExtension extends AbstractTypeExtension
{
    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['skip_add_form']) {
            return;
        }
        $self = $this;
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use($self) {
            $Order = $event->getData();
            if (null === $Order || !$Order->getId()) {
                return;
            }
            $request = $this->requestStack->getMasterRequest();
            $referer = $request->headers->get('referer');
            $Payment = $Order->getPayment();

            if ($Payment && $Payment->getMethodClass() === AmazonPay::class && preg_match('/shopping_coupon/', $referer)) {
                return;
            }
            $uri = $request->getUri();
            if (preg_match('/shopping\\/amazon_pay/', $uri) == false) {
                $form = $event->getForm();
                $Payments = $this->getPaymentChoices($form);
                $Payments = $this->removeAmazonPayChoice($Payments);
                if ((is_null($Order->getPayment()) || $Order->getPayment()->getMethodClass() === AmazonPay::class) && ($Payment = current($Payments))) {
                    $Order->setPayment($Payment);
                    $Order->setPaymentMethod($Payment->getMethod());
                }
                $this->addPaymentForm($form, $Payments, $Order->getPayment());
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use($self) {
            $request = $this->requestStack->getMasterRequest();
            $uri = $request->getUri();
            if (preg_match('/shopping\\/amazon_pay/', $uri) == false) {
                $form = $event->getForm();
                $Order = $form->getData();
                $Payments = $this->getPaymentChoices($form);
                $Payments = $this->removeAmazonPayChoice($Payments);
                if ((is_null($Order->getPayment()) || $Order->getPayment()->getMethodClass() === AmazonPay::class) && ($Payment = current($Payments))) {
                    $Order->setPayment($Payment);
                    $Order->setPaymentMethod($Payment->getMethod());
                    $data = $event->getData();
                    $data['Payment'] = $Payment->getId();
                    $event->setData($data);
                }
                $this->addPaymentForm($form, $Payments);

            }else{
                $form = $event->getForm();
                $Order = $form->getData();
                $Payments = $this->getPaymentChoices($form);

                if (is_null($Order->getPayment()) || $Order->getPayment()->getMethodClass() === AmazonPay::class) {
                    $data = $event->getData();
                    foreach ($Payments as $key => $Payment) {
                        if (!isset($data['Payment']) && $Payment->getMethodClass() === AmazonPay::class) {
                            $data['Payment'] = $Payment->getId();
                            $event->setData($data);
                        }
                    }

                }
            }

        });
    }

    private function getPaymentChoices(FormInterface $form)
    {
        return $form->get('Payment')->getConfig()->getAttribute('choice_list')->getChoices();
    }

    private function removeAmazonPayChoice($Payments){
        foreach ($Payments as $key => $Payment) {
            if ($Payment->getMethodClass() === AmazonPay::class) {
                unset($Payments[$key]);
            }
        }
        return $Payments;
    }

    private function addPaymentForm(FormInterface $form, array $choices, Payment $data = null){
        $message = trans('front.shopping.payment_method_unselected');
        if (empty($choices)) {
            $message = trans('front.shopping.payment_method_not_fount');
        }

        $form->add('Payment', EntityType::class, [
            'class' => Payment::class,
            'choice_label' => 'method',
            'expanded' => true,
            'multiple' => false,
            'placeholder' => false,
            'constraints' => [
                new NotBlank(['message' => $message])
            ],
            'choices' => $choices,
            'data' => $data,
            'invalid_message' => $message
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class];
    }
}