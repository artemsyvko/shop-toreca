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

namespace Plugin\DeliveryDate42;

use Eccube\Entity\Block;
use Eccube\Entity\BlockPosition;
use Eccube\Entity\Csv;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\ProductClass;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\DeliveryDate42\Entity\DeliveryDateConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class PluginManager extends AbstractPluginManager
{
    public function install(array $meta, ContainerInterface $container)
    {
        $file = new Filesystem();
        try {
            $file->copy($container->getParameter('plugin_realdir'). '/DeliveryDate42/Resource/template/default/Block/businessday_calendar.twig', $container->getParameter('eccube_theme_app_dir'). '/' . $container->getParameter('env(ECCUBE_TEMPLATE_CODE)'). '/Block/businessday_calendar.twig', true);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function uninstall(array $meta, ContainerInterface $container)
    {
        if(file_exists($container->getParameter('eccube_theme_app_dir'). '/' . $container->getParameter('env(ECCUBE_TEMPLATE_CODE)'). '/Block/businessday_calendar.twig'))
                unlink($container->getParameter('eccube_theme_app_dir'). '/' . $container->getParameter('env(ECCUBE_TEMPLATE_CODE)'). '/Block/businessday_calendar.twig');
    }

    public function enable(array $meta, ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $translator = $container->get('translator');
        $ymlPath = $container->getParameter('plugin_realdir') . '/DeliveryDate42/Resource/locale/messages.'.$translator->getLocale().'.yaml';
        $messages = Yaml::parse(file_get_contents($ymlPath));

        // 初回有効時に設定の初期値を設定
        $Configs = $entityManager->getRepository(DeliveryDateConfig::class)->findAll();
        if(count($Configs) == 0){
            $SetConfigs = [
                'method' => DeliveryDateConfig::DISABLED,
                'calendar_month' => 2,
                    ];
            foreach($SetConfigs as $name => $value){
                $Config = new DeliveryDateConfig();
                $Config->setName($name);
                $Config->setValue($value);
                $entityManager->persist($Config);
            }
            $entityManager->flush();

            $productClassRepository = $entityManager->getRepository(ProductClass::class);
            $ProductClasses = $productClassRepository->findAll();
            $conne = $entityManager->getConnection();

            foreach($ProductClasses as $ProductClass){
                $DeliveryDuration = $ProductClass->getDeliveryDuration();

                if(!is_null($DeliveryDuration)){
                    $days = $DeliveryDuration->getDuration();
                }else{
                    $days = -1;
                }
                if($days == -1)continue;
                $conne->update('dtb_product_class',['delivery_date_days' => $days],  ['id' => $ProductClass->getId()]);

            }
        }

        $Block = new \Eccube\Entity\Block();
        $Block->setFileName('businessday_calendar');
        $Block->setName($messages['deliverydate.block.title']);
        $Block->setUseController(true);
        $Block->setDeletable(false);
        $DeviceType = $entityManager->getRepository(DeviceType::class)->find(DeviceType::DEVICE_TYPE_PC);
        $Block->setDeviceType($DeviceType);
        $entityManager->persist($Block);
        $entityManager->flush();

        $this->addCsv($container);
    }

    public function disable(array $meta, ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $Block = $entityManager->getRepository(Block::class)->findOneBy(['file_name' => 'businessday_calendar']);
        if($Block){
            $BlockPositions = $entityManager->getRepository(BlockPosition::class)->findBy(['Block' => $Block]);
            foreach($BlockPositions as $BlockPosition){
                $entityManager->remove($BlockPosition);
            }
            $entityManager->remove($Block);
        }

        $Csvs = $entityManager->getRepository(Csv::class)->findBy(['field_name' => 'delivery_date_days']);
        foreach($Csvs as $Csv){
            $entityManager->remove($Csv);
        }
        $entityManager->flush();
    }

    private function addCsv($container)
    {
        $translator = $container->get('translator');
        $ymlPath = $container->getParameter('plugin_realdir') . '/DeliveryDate42/Resource/locale/messages.'.$translator->getLocale().'.yaml';
        $messages = Yaml::parse(file_get_contents($ymlPath));

        $entityManager = $container->get('doctrine.orm.entity_manager');

        $now = new \DateTime();
        //CSV項目追加
        $CsvType = $entityManager->getRepository(CsvType::class)->find(CsvType::CSV_TYPE_PRODUCT);
        $sort_no = $entityManager->createQueryBuilder()
            ->select('MAX(c.sort_no)')
            ->from('Eccube\Entity\Csv','c')
            ->where('c.CsvType = :csvType')
            ->setParameter(':csvType',$CsvType)
            ->getQuery()
            ->getSingleScalarResult();
        if (!$sort_no) {
            $sort_no = 0;
        }

        $Csv = new Csv();
        $Csv->setCsvType($CsvType);
        $Csv->setEntityName('Eccube\\\Entity\\ProductClass');
        $Csv->setFieldName('delivery_date_days');
        $Csv->setDispName($messages['deliverydate.common.1']);
        $Csv->setEnabled(false);
        $Csv->setSortNo($sort_no + 1);
        $Csv->setCreateDate($now);
        $Csv->setUpdateDate($now);
        $entityManager->persist($Csv);

        $entityManager->flush();
    }

}
