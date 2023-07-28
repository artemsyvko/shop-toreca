<?php

namespace Plugin\SlnPayment42\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Repository\PluginRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;
use Plugin\SlnPayment42\Form\Type\Admin\ConfigType;
use Plugin\SlnPayment42\Repository\PluginConfigRepository;
use Plugin\SlnPayment42\Entity\ConfigSubData;
use Plugin\SlnPayment42\Service\BasicItem;


class ConfigController extends AbstractController {

    /**
     * @var PluginRepository;
     */
    protected $pluginRepository;

    /**
     * @var PluginConfigRepository
     */
    protected $configRepository;

    /**
     * @var BasicItem
     */
    protected $basicItem;

    public function __construct(
        PluginRepository $pluginRepository,
        pluginConfigRepository $configRepository,
        BasicItem $basicItem
    ) {
        $this->pluginRepository = $pluginRepository;
        $this->configRepository = $configRepository;
        $this->basicItem = $basicItem;
    }

    /**
     * @Route("/%eccube_admin_route%/sln_payment42/config", name="sln_payment42_admin_config")
     * @Template("@SlnPayment42/admin/config.twig")
     */
    public function edit(Request $request) {
        $Plugin = $this->pluginRepository->findOneBy(array('code' => 'SlnPayment42'));
        
        if (is_null($Plugin)) {
            $error_message = $error = "例外エラー プラグインが存在しません。";
            $error_title = 'エラー';
            return $this->render('error.twig', compact('error', 'error_title', 'error_message'));
        }
        
        $form = $this->createForm(ConfigType::class);
        
        if($request->getMethod() == 'POST') {
            $form->handleRequest($request);
    
            if ($form->isSubmitted() && $form->isValid()) {
                /* @var $basicItem \Plugin\SlnPayment42\Service\BasicItem */
                $configData = new \Plugin\SlnPayment42\Entity\ConfigSubData();
                
                $formData = $form->getData();
                
                foreach ($formData as $key => $data) {
                    $configData[$key] = $data;
                }
                
                $this->configRepository->saverConfig($configData);
                
                //設定成功情報を表示する
                $this->addSuccess('admin.common.save_complete', 'admin');
                return $this->redirectToRoute('sln_payment42_admin_config');
            }
        } else {
            $form->setData($this->configRepository->getConfig());
        }
        
        return [
            'form' => $form->createView(),
            'isGet3D' => extension_loaded('openssl'),
            'creditConnectionDestination' => $this->basicItem->getCreditConnectionDestination(),
            'threedConnectionDestination' => $this->basicItem->getThreedConnectionDestination(),
            'cvsConnectionDestination' => $this->basicItem->getCvsConnectionDestination(),
        ];
    }
}
