<?php

namespace Plugin\SlnPayment42\Repository;

use Eccube\Repository\AbstractRepository;
use Eccube\Repository\MailTemplateRepository;
use Eccube\Repository\PaymentRepository;
use Doctrine\Persistence\ManagerRegistry;
use Plugin\SlnPayment42\Entity\PluginConfig;
use Plugin\SlnPayment42\Entity\ConfigSubData;
use Plugin\SlnPayment42\Service\BasicItem;

class PluginConfigRepository extends AbstractRepository
{
    /**
     * @var MailTemplateRepository
     */
    protected $mailTemplateRepository;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var BasicItem
     */
    protected $basicItem;

    public function __construct(
        ManagerRegistry $registry,
        MailTemplateRepository $mailTemplateRepository,
        PaymentRepository $paymentRepository,
        BasicItem $basicItem
    )
    {
        parent::__construct($registry, PluginConfig::class);
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->paymentRepository = $paymentRepository;
        $this->basicItem = $basicItem;
    }

    public function saverConfig(\Plugin\SlnPayment42\Entity\ConfigSubData $subData)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        
        //メール情報設定
        $isNewMail = false;
        $template = $this->mailTemplateRepository->findOneBy(['file_name' => 'SlnPayment42/Resource/template/mail_template/cvs_order.twig']);
        if (!$template) {
            $isNewMail = true;
        }
        else {
            $subData->setCvsOrderMailId($template->getId());
        }
        
        if ($isNewMail) {
            $mailId = $this->createMailTpl();
            $subData->setCvsOrderMailId($mailId);
        }

        if (empty($subData->getTenantId())) {
            $subData->setTenantId('0001');
        }

        /**
         * クレジット決済接続先設定
         */
        $dst = $subData->getCreditConnectionDestination();
        if (!$dst) $dst = 1;
        $destinations = $this->basicItem->getCreditConnectionDestination();
        log_debug(print_r($destinations, true));
        $subData->setCreditConnectionPlace1($destinations[$dst]['creditConnectionPlace1']);
        $subData->setCreditConnectionPlace2($destinations[$dst]['creditConnectionPlace2']);
        $subData->setCreditConnectionPlace6($destinations[$dst]['creditConnectionPlace6']);

        /**
         * 3Dセキュア認証接続先設定
         */
        $dst = $subData->getThreedConnectionDestination();
        if (!$dst) $dst = 1;
        $destinations = $this->basicItem->getThreedConnectionDestination();
        $subData->setCreditConnectionPlace7($destinations[$dst]['creditConnectionPlace7']);

        /**
         * オンライン収納代行接続先設定
         */
        $dst = $subData->getCvsConnectionDestination();
        if (!$dst) $dst = 1;
        $destinations = $this->basicItem->getCvsConnectionDestination();
        $subData->setCreditConnectionPlace3($destinations[$dst]['creditConnectionPlace3']);
        $subData->setCreditConnectionPlace5($destinations[$dst]['creditConnectionPlace5']);
        
        
        //設定情報を保存する
        $config = new PluginConfig();
        $config->setCreateDate(new \DateTime())
            ->setUpdateDate(new \DateTime())
            ->setSubData($subData->ToSaveData());
        $em->persist($config);
        $em->flush();
        
        self::$configData = null;
        $em->getConnection()->commit();
    }
    
    /**
     * @return \Eccube\Entity\MailTemplate
     */
    protected function getNewMail()
    {
        return new \Eccube\Entity\MailTemplate();
    }
    
    /**
     * @return \Eccube\Entity\MailTemplate
     */
    protected function getOrderMail()
    {
        return $this->mailTemplateRepository->find(1);
    }

    /**
     * @return number
     */
    protected function createMailTpl()
    {
        $OrderMail = $this->getOrderMail();
    
        $Mail = $this->getNewMail();
        $Mail->setCreateDate(new \DateTime());
        $Mail->setFileName("SlnPayment42/Resource/template/mail_template/cvs_order.twig");
        $Mail->setName($OrderMail->getName());
        $Mail->setMailSubject($OrderMail->getMailSubject());
        $Mail->setUpdateDate(new \DateTime());
    
        $em = $this->getEntityManager();
        $em->persist($Mail);
        $em->flush();
    
        return $Mail->getId();
    }

    static protected $configData;

    /**
     * @return \Plugin\SlnPayment42\Entity\ConfigSubData
     */
    public function getConfig()
    {
        $configData = $this->findOneBy(array(), array('id' => 'DESC'));
        if($configData && $configData->getSubData()) {
            
            self::$configData = new ConfigSubData();
            self::$configData->ToData($configData->getSubData());
            
            return self::$configData;
        }
        return new ConfigSubData();
    }
}