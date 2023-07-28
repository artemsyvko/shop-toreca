<?php

namespace Plugin\AmazonPay4;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Common\Constant;
use Eccube\Entity\Payment;
use Eccube\Entity\PaymentOption;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Entity\Csv;
use Eccube\Entity\Plugin;
use Eccube\Entity\Layout;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Master\CsvType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\PageRepository;
use Eccube\Repository\LayoutRepository;
use Eccube\Repository\PageLayoutRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\DeliveryRepository;
use Eccube\Repository\Master\CsvTypeRepository;
use Eccube\Repository\CsvRepository;
use Plugin\AmazonPay4\Entity\Master\AmazonStatus;
use Plugin\AmazonPay4\Entity\Config;
use Plugin\AmazonPay4\Form\Type\Master\ConfigTypeMaster as Master;
use Plugin\AmazonPay4\Service\Method\AmazonPay;
use Eccube\Repository\PluginRepository;
use Eccube\Service\PluginService;
class PluginManager extends AbstractPluginManager
{
    protected $origin_css;
    protected $origin_img;
    protected $origin_plugin_csv;
    protected $target_css;
    protected $target_plugin_data;
    protected $target_user_data;

    public function __construct()
    {
        $this->origin_css = __DIR__ . '/Resource/assets/css';
        $this->origin_img = __DIR__ . '/Resource/assets/img';
        $this->origin_plugin_csv = __DIR__ . '/Resource/assets/csv';
        $this->target_css = __DIR__ . '/../../../html/template/default/assets/css';
        $this->target_plugin_data = __DIR__ . '/../../PluginData/AmazonPay4';
        $this->target_user_data = __DIR__ . '/../../../html/user_data';
    }

    public function install(array $config, ContainerInterface $container)
    {
        $this->copyAssets($container);
        $file = new Filesystem();
        if (!($file->exists($this->target_plugin_data . '/lastNameList.csv'))) {
            $file->copy($this->origin_plugin_csv . '/lastNameList.csv', $this->target_plugin_data . '/lastNameList.csv');
        }
        $this->setConfigIni();
    }

    private function copyAssets(ContainerInterface $container){
        $file = new Filesystem();
        $file->mkdir($this->target_css);
        $file->mirror($this->origin_css, $this->target_css);
        $file->mirror($this->origin_img, $container->getParameter('eccube_html_dir').'/plugin/AmazonPay/assets/');
    }

    private function setConfigIni(){
        $eccubePlatform = env('ECCUBE_PLATFORM');
        if (!($eccubePlatform === 'ec-cube.co')) {
            return;
        }
        $rand = \Eccube\Util\StringUtil::random();
        file_put_contents(__DIR__ . '/amazon_pay_config.ini', "prefix = '{$rand}'");
    }

    public function enable(array $config, ContainerInterface $container)
    {
        $PluginRepository = $container->get('Eccube\\Repository\\PluginRepository');
        $PluginService = $container->get('Eccube\\Service\\PluginService');
        $Plugin = $PluginRepository->findByCode('AmazonPay4');
        $PluginService->generateProxyAndUpdateSchema($Plugin, $config);
        $this->createAmazonPay($container);
        $this->createConfigCsv($container);
        $this->createAmazonPage($container);
        $this->createPlgAmazonPayConfig($container);
        $this->createPlgAmazonPayStatus($container);
    }

    private function createAmazonPay(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $paymentRepository = $entityManager->getRepository(Payment::class);

        $Payment = $paymentRepository->findOneBy(['method_class' => AmazonPay::class]);
        if ($Payment) {
            $Payment->setVisible(true);
            $entityManager->flush($Payment);
            return;
        }

        $Payment = $paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $Payment ? $Payment->getSortNo() + 1 : 1;

        $Payment = new Payment();
        $Payment->setCharge(0);
        $Payment->setSortNo($sortNo);
        $Payment->setVisible(true);
        $Payment->setMethod('Amazon Pay');
        $Payment->setMethodClass(AmazonPay::class);
        $Payment->setRuleMin(0);

        $entityManager->persist($Payment);
        $entityManager->flush($Payment);
    }

    private function createConfigCsv(ContainerInterface $container){
        $entityManager = $container->get('doctrine')->getManager();
        $csvTypeRepository = $entityManager->getRepository(CsvType::class);
        $csvRepository = $entityManager->getRepository(Csv::class);
        $OrderCsvType = $csvTypeRepository->find(3);
        $LastCsv = $csvRepository->findOneBy(['CsvType' => $OrderCsvType], ['sort_no' => 'DESC']);
        $sortNo = $LastCsv->getSortNo();
        $arrCsv = [
            [
                'entity_name' => 'Eccube\\Entity\\Order',
                'field_name' => 'amazonpay4_charge_permission_id',
                'reference_field_name' => null,
                'disp_name' => 'Amazon参照ID'
            ],
            [
                'entity_name' => 'Eccube\\Entity\\Order',
                'field_name' => 'AmazonPay4AmazonStatus',
                'reference_field_name' => 'name',
                'disp_name' => 'Amazon状況'
            ]
        ];
        foreach ($arrCsv as $c) {
            $Csv = $csvRepository->findOneBy(['disp_name' => $c['disp_name']]);
            if (!$Csv) {
                $Csv = new Csv();
                $Csv->setCsvType($OrderCsvType);
                $Csv->setEntityName($c['entity_name']);
                $Csv->setFieldName($c['field_name']);
                $Csv->setReferenceFieldName($c['reference_field_name']);
                $Csv->setDispName($c['disp_name']);
                $Csv->setSortNo($sortNo++);
                $Csv->setCreateDate(new \DateTime());
                $Csv->setUpdateDate(new \DateTime());
                $entityManager->persist($Csv);
                $entityManager->flush($Csv);
            }
        }
    }

    private function createAmazonPage(ContainerInterface $container){
        $entityManager = $container->get('doctrine')->getManager();
        $pageRepository = $entityManager->getRepository(Page::class);
        $layoutRepository = $entityManager->getRepository(Layout::class);
        $pageLayoutRepository = $entityManager->getRepository(PageLayout::class);
        $Layout = $layoutRepository->find(2);
        $LastPageLayout = $pageLayoutRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $LastPageLayout->getSortNo();
        $arrPage = [
            [
                'page_name' => '商品購入(Amazon Pay)',
                'url' => 'amazon_pay_shopping',
                'file_name' => 'Shopping/index'
            ],
            [
                'page_name' => '商品購入(Amazon Pay)/ご注文確認',
                'url' => 'amazon_pay_shopping_confirm',
                'file_name' => 'Shopping/confirm'
            ]
        ];
        foreach ($arrPage as $p) {
            $Page = $pageRepository->findOneBy(['url' => $p['url']]);
            if (!$Page) {
                $Page = new Page();
                $Page->setName($p['page_name']);
                $Page->setUrl($p['url']);
                $Page->setFileName($p['file_name']);
                $Page->setEditType(Page::EDIT_TYPE_DEFAULT);
                $Page->setCreateDate(new \DateTime());
                $Page->setUpdateDate(new \DateTime());
                $Page->setMetaRobots('noindex');
                $entityManager->persist($Page);
                $entityManager->flush($Page);
                $PageLayout = new PageLayout();
                $PageLayout->setPage($Page);
                $PageLayout->setPageId($Page->getId());
                $PageLayout->setLayout($Layout);
                $PageLayout->setLayoutId($Layout->getId());
                $PageLayout->setSortNo($sortNo++);
                $entityManager->persist($PageLayout);
                $entityManager->flush($PageLayout);
            }
        }
    }

    public function createPlgAmazonPayConfig(ContainerInterface $container){
        $entityManage = $container->get('doctrine.orm.entity_manager');
        $Config = $entityManage->find(Config::class, 1);
        if ($Config) {
            $amazonPayEnvs = $container->getParameter('amazon_pay4')['env'];
            if (in_array($Config->getEnv(), $amazonPayEnvs)) {
                $Config->setAmazonAccountMode(2);
            }else{
                $Config->setEnv(1);
                $Config->setAmazonAccountMode(1);
            }

            $entityManage->flush();
            return;
        }
        $Config = new Config();
        $Config->setAmazonAccountMode(Master::ACCOUNT_MODE['OWNED']);
        $Config->setEnv(Master::ENV['SANDBOX']);
        $Config->setPrivateKeyPath('app/PluginData/AmazonPay4/AmazonPay_*.pem');
        $Config->setSale(Master::SALE['AUTORI']);
        $Config->setUseConfirmPage(false);
        $Config->setAutoLogin(true);
        $Config->setLoginRequired(false);
        $Config->setOrderCorrect(true);
        $Config->setMailNotices(null);
        $Config->setUseCartButton(true);
        $Config->setCartButtonColor('Gold');
        $Config->setCartButtonPlace(Master::CART_BUTTON_PLACE['AUTO']);
        $Config->setUseMypageLoginButton(false);
        $Config->setMypageLoginButtonColor('Gold');
        $Config->setMypageLoginButtonPlace(Master::MYPAGE_LOGIN_BUTTON_PLACE['AUTO']);

        $entityManage->persist($Config);
        $entityManage->flush($Config);
    }

    public function createPlgAmazonPayStatus(ContainerInterface $container)
    {
        $entityManage = $container->get('doctrine.orm.entity_manager');
        $statuses = [AmazonStatus::AUTHORI => 'オーソリ', AmazonStatus::CAPTURE => '売上', AmazonStatus::CANCEL => '取消'];
        $i = 0;
        foreach ($statuses as $id => $name) {
            $AmazonStatus = $entityManage->find(AmazonStatus::class, $id);
            if ($AmazonStatus) {
                continue;
            }
            $AmazonStatus = new AmazonStatus();
            $AmazonStatus->setId($id);
            $AmazonStatus->setName($name);
            $AmazonStatus->setSortNo($i++);
            $entityManage->persist($AmazonStatus);
            $entityManage->flush($AmazonStatus);
        }
    }

    public function disable(array $config, ContainerInterface $container)
    {
        $this->disableAmazonPay($container);
    }

    public function uninstall(array $config, ContainerInterface $container)
    {
        $this->removeAssets($container);
        $file = new Filesystem();
        $file->remove($this->target_plugin_data . '/lastNameList.csv');
        $this->removeConfigCsv($container);
        $this->removeAmazonPage($container);
    }

    private function removeAssets(ContainerInterface $container)
    {
        $file = new Filesystem();
        $file->remove($this->target_css . '/amazon_shopping.css');
        $file->remove($container->getParameter('eccube_html_dir').'/plugin/AmazonPay4');
    }

    private function removeConfigCsv(ContainerInterface $container){
        $entityManager = $container->get('doctrine')->getManager();
        $csvTypeRepository = $entityManager->getRepository(CsvType::class);
        $csvRepository = $entityManager->getRepository(Csv::class);
        $OrderCsvType = $csvTypeRepository->find(3);
        $arrCsv = [
            [
                'entity_name' => 'Eccube\\Entity\\Order',
                'field_name' => 'amazonpay4_charge_permission_id',
                'reference_field_name' => null,
                'disp_name' => 'Amazon参照ID'
            ],
            [
                'entity_name' => 'Eccube\\Entity\\Order',
                'field_name' => 'AmazonPay4AmazonStatus',
                'reference_field_name' => 'name',
                'disp_name' => 'Amazon状況'
            ]
        ];
        foreach ($arrCsv as $c) {
            $Csv = $csvRepository->findOneBy($c);
            if ($Csv) {
                $entityManager->remove($Csv);
                $entityManager->flush();
            }
        }
    }

    private function removeAmazonPage(ContainerInterface $container){
        $entityManager = $container->get('doctrine')->getManager();
        $pageRepository = $entityManager->getRepository(Page::class);
        $arrPage = [
            [
                'name' => '商品購入(Amazon Pay)',
                'url' => 'amazon_pay_shopping',
                'file_name' => 'Shopping/index'
            ],
            [
                'name' => '商品購入(Amazon Pay)/ご注文確認',
                'url' => 'amazon_pay_shopping_confirm',
                'file_name' => 'Shopping/confirm'
            ]
        ];

        foreach ($arrPage as $p) {
            $Page = $pageRepository->findOneBy($p);
            if ($Page) {
                foreach ($Page->getPageLayouts() as $PageLayout) {
                    $Page->removePageLayout($PageLayout);
                    $entityManager->remove($PageLayout);
                    $entityManager->flush($PageLayout);
                }
                $entityManager->remove($Page);
                $entityManager->flush();
            }
        }
    }

    public function update(array $config, ContainerInterface $container)
    {
        $this->updateAssets($container);
        $this->createAmazonPage($container);
    }


    private function disableAmazonPay(ContainerInterface $container){
        $entityManager = $container->get('doctrine')->getManager();
        $paymentRepository = $entityManager->getRepository(Payment::class);
        $Payment = $paymentRepository->findOneBy(['method_class' => AmazonPay::class]);

        if ($Payment) {
            $Payment->setVisible(false);
            $entityManager->flush($Payment);
        }
    }



    private function updateAssets(ContainerInterface $container)
    {
        $file = new Filesystem();
        if (!($file->exists($this->target_plugin_data . '/lastNameList.csv'))) {
            $file->copy($this->origin_plugin_data . '/lastNameList.csv', $this->target_plugin_data . '/lastNameList.csv');
        }
    }

}