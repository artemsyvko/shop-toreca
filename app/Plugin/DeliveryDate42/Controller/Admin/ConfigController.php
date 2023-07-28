<?php
/*
* Plugin Name : DeliveryDate4
*
* Copyright (C) BraTech Co., Ltd. All Rights Reserved.
* http://www.bratech.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\DeliveryDate42\Controller\Admin;

use Plugin\DeliveryDate42\Entity\DeliveryDateConfig;
use Plugin\DeliveryDate42\Form\Type\Admin\ConfigType;
use Plugin\DeliveryDate42\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ConfigController extends \Eccube\Controller\AbstractController
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
            ConfigRepository $configRepository
            )
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/setting/deliverydate/config", name="admin_setting_deliverydate_config")
     * @Template("@DeliveryDate42/admin/Setting/config.twig")
     */
    public function index(Request $request)
    {

        $form = $this->formFactory
                ->createBuilder(ConfigType::class)
                ->getForm();

        $Configs = $this->configRepository->findAll();
        foreach($Configs as $config){
            if(is_null($config->getValue()) || is_array($config->getValue()))continue;
            if($config->getName() == 'same_day_flg'){
                if($config->getValue() == 1)$form[$config->getName()]->setData(true);
            }else{
                $form[$config->getName()]->setData($config->getValue());
            }
        }

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                foreach($Configs as $config){
                    $this->entityManager->remove($config);
                }
                $this->entityManager->flush();

                $accept_time = $form->get('accept_time')->getData();

                $Config = new DeliveryDateConfig();
                $Config->setName('accept_time');
                $Config->setValue($accept_time);
                $this->entityManager->persist($Config);

                $same_day_flg = $form->get('same_day_flg')->getData();

                $Config = new DeliveryDateConfig();
                $Config->setName('same_day_flg');
                $Config->setValue($same_day_flg);
                $this->entityManager->persist($Config);

                $method = $form->get('method')->getData();

                $days = $form->get('default_deliverydate_days')->getData();

                $Config = new DeliveryDateConfig();
                $Config->setName('default_deliverydate_days');
                $Config->setValue($days);
                $this->entityManager->persist($Config);

                $method = $form->get('method')->getData();

                $Config = new DeliveryDateConfig();
                $Config->setName('method');
                $Config->setValue($method);
                $this->entityManager->persist($Config);

                $calendar_month = $form->get('calendar_month')->getData();

                $Config = new DeliveryDateConfig();
                $Config->setName('calendar_month');
                $Config->setValue($calendar_month);
                $this->entityManager->persist($Config);

                $this->entityManager->flush();

                $this->addSuccess('admin.setting.deliverydate.save.complete', 'admin');
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }
}