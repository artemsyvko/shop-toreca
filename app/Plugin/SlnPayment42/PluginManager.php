<?php

namespace Plugin\SlnPayment42;

use Eccube\Plugin\AbstractPluginManager;
use Eccube\Entity\Payment;
use Eccube\Entity\Page;
use Eccube\Entity\Layout;
use Eccube\Entity\PageLayout;
use Eccube\Entity\Master\DeviceType;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\PageRepository;
use Eccube\Repository\LayoutRepository;
use Eccube\Repository\PageLayoutRepository;
use Eccube\Repository\Master\DeviceTypeRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Plugin\SlnPayment42\Service\Method\CreditCard;
use Plugin\SlnPayment42\Service\Method\RegisteredCreditCard;
use Plugin\SlnPayment42\Service\Method\CvsMethod;

class PluginManager extends AbstractPluginManager {

    public function enable(array $meta, ContainerInterface $container)
    {
        $this->createCreditPayment($container);
        $this->createRegisteredCreditPayment($container);
        $this->createCvsPayment($container);
        $this->createPage($container);
    }

    private function createCreditPayment(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $paymentRepository = $container->get('doctrine')->getManager()->getRepository(Payment::class);

        $Payment = $paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $Payment ? $Payment->getSortNo() + 1 : 1;

        $Payment = $paymentRepository->findOneBy(['method_class' => CreditCard::class]);
        if (!$Payment) {
            $Payment = new Payment();
        }

        $Payment->setCharge(0);
        $Payment->setRuleMin(1);
        $Payment->setRuleMax($container->getParameter('SLN_PAYID_CREDIT_MAX'));
        $Payment->setSortNo($sortNo);
        $Payment->setVisible(true);
        $Payment->setMethod($container->getParameter('SLN_PAYNAME_CREDIT'));
        $Payment->setMethodClass(CreditCard::class);

        $entityManager->persist($Payment);
        $entityManager->flush($Payment);
    }

    private function createRegisteredCreditPayment(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $paymentRepository = $container->get('doctrine')->getManager()->getRepository(Payment::class);

        $Payment = $paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $Payment ? $Payment->getSortNo() + 1 : 1;

        $Payment = $paymentRepository->findOneBy(['method_class' => RegisteredCreditCard::class]);
        if (!$Payment) {
            $Payment = new Payment();
        }

        $Payment->setCharge(0);
        $Payment->setRuleMin(1);
        $Payment->setRuleMax($container->getParameter('SLN_PAYID_REGIST_CREDIT_MAX'));
        $Payment->setSortNo($sortNo);
        $Payment->setVisible(true);
        $Payment->setMethod($container->getParameter('SLN_PAYNAME_REGIST_CREDIT'));
        $Payment->setMethodClass(RegisteredCreditCard::class);

        $entityManager->persist($Payment);
        $entityManager->flush($Payment);
    }

    private function createCvsPayment(ContainerInterface $container) {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $paymentRepository = $container->get('doctrine')->getManager()->getRepository(Payment::class);

        $Payment = $paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $Payment ? $Payment->getSortNo() + 1 : 1;

        $Payment = $paymentRepository->findOneBy(['method_class' => CvsMethod::class]);
        if (!$Payment) {
            $Payment = new Payment();
        }

        $Payment->setCharge(0);
        $Payment->setRuleMin(1);
        $Payment->setRuleMax($container->getParameter('SLN_PAYID_CVS_MAX'));
        $Payment->setSortNo($sortNo);
        $Payment->setVisible(true);
        $Payment->setMethod($container->getParameter('SLN_PAYNAME_CVS'));
        $Payment->setMethodClass(CvsMethod::class);

        $entityManager->persist($Payment);
        $entityManager->flush($Payment);
    }

    private function createPage(ContainerInterface $container) {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $pageRepository = $container->get('doctrine')->getManager()->getRepository(Page::class);
        $layoutRepository = $container->get('doctrine')->getManager()->getRepository(Layout::class);
        $deviceTypeRepository = $container->get('doctrine')->getManager()->getRepository(DeviceType::class);
        $pageLayoutRepository = $container->get('doctrine')->getManager()->getRepository(PageLayout::class);

        // ページ取得
        $Page = $pageRepository->findOneBy(['url' => 'sln_edit_card']);
        if (!$Page) {
            // 新規ページ生成
            $Page = new Page();
        }

        $entityManager->getConnection()->beginTransaction();

        try{
            $Page->setName('マイページ/登録済クレジットカード');
            $Page->setUrl('sln_edit_card');
            $Page->setFileName('@SlnPayment42/sln_edit_card');
            $Page->setEditType(2);
            $Page->setMetaRobots('noindex');

            // マイページの登録済クレジットカードページを登録
            $entityManager->persist($Page);
            $entityManager->flush();

            $PageLayout = $pageLayoutRepository->findOneBy([], ['sort_no' => 'DESC']);
            $sortNo = $PageLayout ? $PageLayout->getSortNo() + 1 : 1;

            $PageLayout = $pageLayoutRepository->findOneBy(['page_id' => $Page->getId()]);
            if (!$PageLayout) {
                $PageLayout = new PageLayout();
                $PageLayout->setPage($Page);
                $PageLayout->setPageId($Page->getId());
                $PageLayout->setSortNo($sortNo);
            }
            
            $Layout = $layoutRepository->findOneBy(['id' => 2]);
            $PageLayout->setLayout($Layout);
            $PageLayout->setLayoutId($Layout->getId());
            
            // ページとレイアウトを紐づけ
            $entityManager->persist($PageLayout);

            $entityManager->getConnection()->commit();
        } catch(Exception $e) {
            log_error($e->getMessage());
            $entityManager->getConnection()->rollBack();
        }
    }

}