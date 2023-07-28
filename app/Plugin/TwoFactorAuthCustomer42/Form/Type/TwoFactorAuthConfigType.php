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

namespace Plugin\TwoFactorAuthCustomer42\Form\Type;

use Eccube\Common\EccubeConfig;
use Plugin\TwoFactorAuthCustomer42\Entity\TwoFactorAuthConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TwoFactorAuthConfigType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    protected ValidatorInterface $validator;

    /**
     * TwoFactorAuthConfigType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig, ValidatorInterface $validator)
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->validator = $validator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('api_key', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                    new Assert\Regex(
                        [
                            'pattern' => '/^[a-zA-Z0-9]+$/i',
                            'message' => 'form_error.graph_only',
                        ]
                    ),
                ],
            ])
            ->add('plain_api_secret', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                ],
            ])
            ->add('from_phone_number', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                    new Assert\Regex(
                        [
                            'pattern' => '/^[0-9]+$/i',
                            'message' => 'form_error.numeric_only',
                        ]
                    ),
                ],
            ])
            ->add('include_routes', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_ltext_len'],
                    ]),
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            if ($data['plain_api_secret'] !== $this->eccubeConfig['eccube_default_password']) {
                $errors = $this->validator->validate($data['plain_api_secret'], [
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z0-9]+$/i',
                        'message' => 'form_error.graph_only',
                    ]),
                ]);
                if ($errors) {
                    foreach ($errors as $error) {
                        $form['plain_api_secret']->addError(new FormError($error->getMessage()));
                    }
                }
            }
        });
    }

    /**
     * {@inheritDoc}
     *
     * @see AbstractType::configureOptions
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TwoFactorAuthConfig::class,
        ]);
    }
}
