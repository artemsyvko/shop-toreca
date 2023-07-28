<?php

namespace Plugin\AmazonPay4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Plugin\AmazonPay4\Entity\Master\AmazonStatus;
use Plugin\AmazonPay4\Entity\AmazonTrading;
use Plugin\AmazonPay4\Exception\AmazonException;
use Plugin\AmazonPay4\Exception\AmazonPaymentException;
use Plugin\AmazonPay4\Exception\AmazonSystemException;
use Plugin\AmazonPay4\Repository\Master\AmazonStatusRepository;
use Plugin\AmazonPay4\Repository\ConfigRepository;
use Plugin\AmazonPay4\Service\Method\AmazonPay;
use Plugin\AmazonPay4\Service\AmazonRequestService;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\PluginRepository;
use Eccube\Repository\Master\CustomerStatusRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\Master\PrefRepository;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\Processor\StockReduceProcessor;
use Eccube\Service\PurchaseFlow\Processor\PointProcessor;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class AmazonOrderHelper
{
    private $sessionAmazonProfileKey = 'amazon_pay4.profile';
    private $sessionAmazonShippingAddressKey = 'amazon_pay4.shipping_address';
    private $amazonSettings;
    private $pointProcessor;
    private $stockReduceProcessor;

    public function __construct(CustomerRepository $customerRepository, BaseInfoRepository $baseInfoRepository, PaymentRepository $paymentRepository, PluginRepository $pluginRepository, CustomerStatusRepository $customerStatusRepository, OrderStatusRepository $orderStatusRepository, EntityManagerInterface $entityManager, EccubeConfig $eccubeConfig, PrefRepository $prefRepository, SessionInterface $session, EncoderFactoryInterface $encoderFactory, TokenStorageInterface $tokenStorage, AmazonStatusRepository $amazonStatusRepository, ConfigRepository $configRepository, AmazonRequestService $amazonRequestService, StockReduceProcessor $stockReduceProcessor, PointProcessor $pointProcessor)
    {
        $this->customerRepository = $customerRepository;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->paymentRepository = $paymentRepository;
        $this->pluginRepository = $pluginRepository;
        $this->customerStatusRepository = $customerStatusRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;
        $this->prefRepository = $prefRepository;
        $this->session = $session;
        $this->encoderFactory = $encoderFactory;
        $this->tokenStorage = $tokenStorage;
        $this->amazonStatusRepository = $amazonStatusRepository;
        $this->configRepository = $configRepository;
        $this->amazonRequestService = $amazonRequestService;
        $this->Config = $this->configRepository->get();
        $this->stockReduceProcessor = $stockReduceProcessor;
        $this->pointProcessor = $pointProcessor;
    }

    public function getOrderer($shippingAddress)
    {
        $Customer = new Customer();
        $Customer->setName01('　');
        $Customer->setName02('　');
        $Customer->setKana01('　');
        $Customer->setKana02('　');
        $Pref = $this->prefRepository->find(13);
        $Customer->setPref($Pref);
        $profile = unserialize($this->session->get($this->sessionAmazonProfileKey));
        $Customer->setEmail($profile->email);
        $this->convert($Customer, $shippingAddress);

        return $Customer;
    }

    public function initializeAmazonOrder($Order, $Customer)
    {
        $Payment = $this->paymentRepository->findOneBy(['method_class' => AmazonPay::class]);
        $Order->setPayment($Payment);
        $Order->setEmail($Customer->getEmail());
        $this->entityManager->persist($Order);

        return $Order;
    }

    public function convert($entity, $shippingAddress)
    {
        goto JiyNm;
        D8tCl:
        $entity->setName01($arrName['name01'])->setName02($arrName['name02']);
        goto pT30w;
        auIoq:
        goto T8ow0;
        goto BfKJj;
        zcihH:
        $entity->setPostalCode(preg_replace('/(-|−)/', '', $shippingAddress->postalCode));
        goto wHPmo;
        YzIg3:R9BnA:
        goto fOLLl;
        LAsUI:
        $addr01 = $shippingAddress->city . $convertAmznAddr1;
        goto yIstq;
        TGqXz:
        $email = $entity->getEmail();
        goto dp76I;
        QaAfT:
        $entity->setPhoneNumber(preg_replace('/(-|−)/', '', $shippingAddress->phoneNumber));
        goto iv7nz;
        QzrB6:MXNmV:
        goto frLQ6;
        GeHXC:
        $Pref = $this->prefRepository->findOneBy(['name' => $shippingAddress->stateOrRegion]);
        goto YfZ6C;
        evel1:
        if (!(mb_strlen($PostalCodeCount) >= 9)) {
            goto qbbMQ;
        }
        goto p97_5;
        pywxm:prlCt:
        goto PW3zp;
        JiyNm:
        if (strpos($shippingAddress->postalCode, '-') !== false) {
            goto AMV3z;
        }
        goto aZIfA;
        sGZOW:
        $entity->setKana02('　');
        goto auIoq;
        UoXkd:
        if (!empty($arrFixKana)) {
            goto a8W1z;
        }
        goto HeCxw;
        RHirs:SeUso:
        goto KSnQG;
        PEIWP:
        $entity->setKana01($arrName['kana01']);
        goto sGZOW;
        w0Wk5:
        $AddressLine1Front = mb_substr($convertAmznAddr1, 0, $offset);
        goto s6ly_;
        h48sc:ykedP:
        goto PEIWP;
        LwZE9:CCTPA:
        goto TUI3A;
        aaFfc:
        if ($entity instanceof Shipping) {
            goto TO8Ib;
        }
        goto vMUeq;
        AfeyV:
        if ($entity instanceof Order) {
            goto fACYW;
        }
        goto We3U_;
        ApKs4:
        goto aEyfk;
        goto fztXD;
        HeCxw:
        if ($entity->getKana01() == null) {
            goto CCTPA;
        }
        goto GNYky;
        fOLLl:
        $convertAmznAddr1 = mb_convert_kana($shippingAddress->addressLine1, 'n', 'utf-8');
        goto gj_Wv;
        hXVqe:qbbMQ:
        goto zcihH;
        G4v1y:
        $PostalCodeCount = preg_replace('/(-|−)/', '', $shippingAddress->postalCode);
        goto evel1;
        Qm1Ug:elnPh:
        goto paa3N;
        lrdLm:
        $entity->setAddr01($addr01)->setAddr02($addr02);
        goto QaAfT;
        WUtxe:
        $offset = strlen(substr($convertAmznAddr1, 0, $matches[0][1]));
        goto w0Wk5;
        i4iMx:
        goto HhFdc;
        goto Qm1Ug;
        vMUeq:
        goto T8ow0;
        goto h48sc;
        BH1Mu:
        goto BN7yE;
        goto kidpC;
        IMGBV:SWQiz:
        goto WUtxe;
        GNYky:
        goto wENic;
        goto UViAZ;
        gj_Wv:
        $convertAmznAddr2 = mb_convert_kana($shippingAddress->addressLine2, 'n', 'utf-8');
        goto diUG4;
        EA9fD:
        $addr02 .= " " . $shippingAddress->addressLine3;
        goto jVer8;
        KSnQG:
        $Order = $entity->getOrder();
        goto pVSX0;
        H65Kw:
        logs('amazon_pay4')->info('*** 都道府県マッチングエラー *** addr = ' . var_export($shippingAddress, true));
        goto aISdY;
        frLQ6:
        switch ($shippingAddress->stateOrRegion) {
            case "東京":
                $shippingAddress->stateOrRegion = "東京都";
                goto R9BnA;
            case "大阪":
                $shippingAddress->stateOrRegion = "大阪府";
                goto R9BnA;
            case "京都":
                $shippingAddress->stateOrRegion = "京都府";
                goto R9BnA;
            case "東京都":
                goto R9BnA;
            case "大阪府":
                goto R9BnA;
            case "京都府":
                goto R9BnA;
            case "北海道":
                goto R9BnA;
            default:
                goto F8lPV;
                F8lPV:
                if (preg_match('/県$/u', $shippingAddress->stateOrRegion)) {
                    goto dl580;
                }
                goto vEzBE;
                Eza4j:dl580:
                goto Rld2s;
                vEzBE:
                $shippingAddress->stateOrRegion .= '県';
                goto Eza4j;
                Rld2s:
        }
        goto sfyZ9;
        aISdY:
        $Pref = $this->prefRepository->find(13);
        goto JY1cP;
        wcNXc:
        $entity->setKana01('　')->setKana02('　');
        goto anG8r;
        uUjKZ:
        if ($entity instanceof Customer) {
            goto prlCt;
        }
        goto ApKs4;
        CXfKw:
        $arrPostalCode = explode('-', $shippingAddress->postalCode);
        goto MqJoV;
        UViAZ:a8W1z:
        goto fjdSD;
        K4VQo:aEyfk:
        goto wTrBq;
        diUG4:
        if (preg_match("/[0-9]/", $convertAmznAddr1, $matches, PREG_OFFSET_CAPTURE)) {
            goto SWQiz;
        }
        goto LAsUI;
        aZIfA:
        preg_match('/(\\d{3})(\\d{4})/', $shippingAddress->postalCode, $arrPostalCode);
        goto fQVaR;
        yIstq:
        $addr02 = $convertAmznAddr2;
        goto ZS23G;
        ZTvlm:
        $arrPostalCode = array_values($arrPostalCode);
        goto BH1Mu;
        paa3N:
        $entity->setPref($Pref);
        goto s9bZ8;
        pT30w:
        if (isset($arrName['kana01'])) {
            goto ykedP;
        }
        goto aaFfc;
        fjdSD:
        $entity->setKana01($arrFixKana['kana01'])->setKana02($arrFixKana['kana02']);
        goto EKYRA;
        PW3zp:
        $email = $entity->getEmail();
        goto K4VQo;
        anG8r:T8ow0:
        goto Sl7z_;
        fztXD:fACYW:
        goto TGqXz;
        s9bZ8:HhFdc:
        goto lrdLm;
        wTrBq:
        $arrFixKana = $this->reviseKana($entity->getName01(), $entity->getName02(), $email);
        goto UoXkd;
        v_cw1:wENic:
        goto QzrB6;
        s6ly_:
        $AddressLine1End = mb_substr($convertAmznAddr1, $offset);
        goto T0VaT;
        YHLLD:df2Ac:
        goto tcaaZ;
        Sl7z_:
        if (!($this->Config->getOrderCorrect() == $this->eccubeConfig['amazon_pay4']['toggle']['on'])) {
            goto MXNmV;
        }
        goto AfeyV;
        T0VaT:
        $addr01 = $shippingAddress->city . $AddressLine1Front;
        goto c0_SE;
        c0_SE:
        $addr02 = $AddressLine1End . $convertAmznAddr2;
        goto YHLLD;
        We3U_:
        if ($entity instanceof Shipping) {
            goto SeUso;
        }
        goto uUjKZ;
        YfZ6C:
        if (!empty($Pref)) {
            goto elnPh;
        }
        goto H65Kw;
        tcaaZ:
        if (!($shippingAddress->addressLine3 != '')) {
            goto doFiJ;
        }
        goto EA9fD;
        TUI3A:
        $entity->setKana01('　')->setKana02('　');
        goto v_cw1;
        pVSX0:
        $email = $Order->getEmail();
        goto ggUzw;
        ZS23G:
        goto df2Ac;
        goto IMGBV;
        BfKJj:TO8Ib:
        goto wcNXc;
        EKYRA:
        goto wENic;
        goto LwZE9;
        sfyZ9:ZBpEO:
        goto YzIg3;
        fQVaR:
        unset($arrPostalCode[0]);
        goto ZTvlm;
        O3uOA:
        $addr01 = $shippingAddress->stateOrRegion . $addr01;
        goto i4iMx;
        kidpC:AMV3z:
        goto CXfKw;
        jVer8:doFiJ:
        goto GeHXC;
        ggUzw:
        goto aEyfk;
        goto pywxm;
        p97_5:
        $arrAmznAddr = ['PostalCode' => ' ', 'CountryCode' => '', 'StateOrRegion' => '', 'Name' => '', 'AddressLine1' => '', 'AddressLine2' => '', 'AddressLine3' => '', 'City' => '', 'Phone' => ''];
        goto hXVqe;
        wHPmo:
        $arrName = $this->divideName($shippingAddress->name);
        goto D8tCl;
        MqJoV:BN7yE:
        goto G4v1y;
        dp76I:
        goto aEyfk;
        goto RHirs;
        JY1cP:
        $entity->setPref($Pref);
        goto O3uOA;
        iv7nz:
    }

    public function divideName($name)
    {
        goto dE4gN;
        oHQjF:uZVTI:
        goto krFxS;
        Jxw13:sMBWt:
        goto E3yqb;
        uAS_7:
        $arrResult['name01'] = $name;
        goto xF966;
        SIhMO:
        goto GX5eT;
        goto rxLlG;
        dTp17:
        $arrName = preg_split('/[ 　]+/u', $name);
        goto vifpT;
        BuTDV:
        return $arrResult;
        goto LcbTk;
        qhxwU:
        $arrResult = $arrFixName;
        goto d0pN0;
        E3yqb:
        if (!empty($arrFixName)) {
            goto VXW5I;
        }
        goto uAS_7;
        krFxS:
        goto fHwRK;
        goto xla1I;
        ZZY1x:GX5eT:
        goto oHQjF;
        vifpT:
        if (count($arrName) == 2) {
            goto VvfZp;
        }
        goto UhlWA;
        xF966:
        $arrResult['name02'] = '　';
        goto SIhMO;
        rxLlG:VXW5I:
        goto qhxwU;
        k3HC1:
        if (!($this->Config->getOrderCorrect() == $this->eccubeConfig['amazon_pay4']['toggle']['on'])) {
            goto sMBWt;
        }
        goto JO_H4;
        iDsUO:
        $arrResult['name02'] = $arrName[1];
        goto eXk9r;
        p9Qdc:
        $arrResult['name01'] = $arrName[0];
        goto iDsUO;
        JO_H4:
        $arrFixName = $this->reviseName($name);
        goto Jxw13;
        troVZ:
        if (!empty($arrResult)) {
            goto uZVTI;
        }
        goto k3HC1;
        xla1I:VvfZp:
        goto p9Qdc;
        dE4gN:
        $arrResult = [];
        goto dTp17;
        eXk9r:fHwRK:
        goto BuTDV;
        UhlWA:
        $arrResult = $this->reviseLastNameList($name);
        goto troVZ;
        d0pN0:
        logs('amazon_pay4')->info('*** 名前補正 ***');
        goto ZZY1x;
        LcbTk:
    }

    private function searchObject($search, $target, $objectName)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('o')->from('\\Eccube\\Entity' . "\\{$objectName}", 'o');

        if ($target == 'name') {
            $qb->andWhere('CONCAT(o.name01, o.name02) = :name')->setParameter('name', $search)
                ->andWhere("TRIM(o.name02) <> ''");
        }
        return $qb->getQuery()->getResult();
    }

    private function searchObjectByNameAndEmail($name01, $name02, $email, $target, $objectName)
    {
        $qb = $this->entityManager->createQueryBuilder();
        if ($target == 'kana') {
            if ($objectName == 'Order') {
                $qb->select('o')
                    ->from(Order::class, 'o')
                    ->andWhere('o.name01 = :name01')->setParameter('name01', $name01)
                    ->andWhere('o.name02 = :name02')->setParameter('name02', $name02)
                    ->andWhere('o.email = :email')->setParameter('email', $email)
                    ->andWhere("o.kana01 <> ''")->andWhere("o.kana02 <> ''")
                    ->andWhere("o.OrderStatus <> :status_pending")->setParameter('status_pending', OrderStatus::PENDING)
                    ->andWhere("o.OrderStatus <> :status_processing")->setParameter('status_processing', OrderStatus::PROCESSING)
                    ->andWhere("o.AmazonPay4AmazonStatus IS NOT NULL")->orderBy('o.update_date', 'DESC');
            } else if ($objectName == 'Shipping') {
                $qb->select('s')
                    ->from(Shipping::class, 's')
                    ->leftJoin('s.Order', 'o')
                    ->andWhere('s.name01 = :name01')->setParameter('name01', $name01)
                    ->andWhere('s.name02 = :name02')->setParameter('name02', $name02)
                    ->andWhere('o.email = :email')->setParameter('email', $email)
                    ->andWhere("s.kana01 <> ''")->andWhere("s.kana02 <> ''")
                    ->andWhere("o.OrderStatus <> :status_pending")->setParameter('status_pending', OrderStatus::PENDING)
                    ->andWhere("o.OrderStatus <> :status_processing")->setParameter('status_processing', OrderStatus::PROCESSING)
                    ->andWhere("o.AmazonPay4AmazonStatus IS NOT NULL")->orderBy('o.update_date', 'DESC');
            } else if ($objectName == 'Customer') {
                $qb->select('c')
                    ->from(Customer::class, 'c')
                    ->andWhere('c.name01 = :name01')->setParameter('name01', $name01)
                    ->andWhere('c.name02 = :name02')->setParameter('name02', $name02)
                    ->andWhere('c.email = :email')->setParameter('email', $email)
                    ->andWhere("c.kana01 <> ''")->andWhere("c.kana02 <> ''")
                    ->andWhere("c.amazon_user_id <> ''")
                    ->orderBy('c.update_date', 'DESC');
            }
        }
        return $qb->getQuery()->getResult();
    }

    public function reviseName($name)
    {
        $arrFixName = [];
        $Objects = $this->searchObject($name, 'name', 'Order');
        if (empty($Objects)) {
            $Objects = $this->searchObject($name, 'name', 'Shipping');
            if (!empty($Objects)) {
                $Objects = $this->searchObject($name, 'name', 'Customer');
            }
        }

        if (!empty($Objects)) {
            $arrFixName['name01'] = $Objects[0]->getName01();
            $arrFixName['name02'] = $Objects[0]->getName02();
        }
        return $arrFixName;
    }

    public function reviseKana($name01, $name02, $email)
    {
        $arrFixKana = [];
        $Objects = $this->searchObjectByNameAndEmail($name01, $name02, $email, 'kana', 'Order');
        if (empty($Objects)) {
            $Objects = $this->searchObjectByNameAndEmail($name01, $name02, $email, 'kana', 'Shipping');
            if (empty($Objects)) {
                $Objects = $this->searchObjectByNameAndEmail($name01, $name02, $email, 'kana', 'Customer');
            }
        }

        if (!empty($Objects)) {
            $arrFixKana['kana01'] = $Objects[0]->getKana01();
            $arrFixKana['kana02'] = $Objects[0]->getKana02();
        }
        return $arrFixKana;
    }

    public function reviseLastNameList($name)
    {
        $arrName = [];
        $fp = fopen($this->eccubeConfig['plugin_data_realdir'] . '/AmazonPay4/lastNameList.csv', 'r');
        if ($fp === false) {
            return null;
        }
        $arrName = null;
        $last_max_len = 0;
        $row = fgetcsv($fp);

        while (($row = fgetcsv($fp)) !== FALSE) {
            if (mb_strpos($name, $row[0], null, 'utf-8') === 0) {
                $cr_len = mb_strlen($row[0], 'utf-8');
                if ($last_max_len > $cr_len) {
                    continue;
                }
                $last_max_len = $cr_len;
                $arrName['name01'] = $row[0];
                $arrName['name02'] = mb_substr($name, $cr_len, null, 'utf-8');
                $arrName['kana01'] = $row[1];
                $arrName['kana02'] = '　';
            }
        }

        fclose($fp);

        return $arrName;
    }

    public function copyToOrderFromCustomer(Order $Order, Customer $Customer)
    {
        if (empty($Customer)) {
            return;
        }

        if ($Customer->getId()) {
            $Order->setCustomer($Customer);
        }
        $Order
            ->setName01($Customer->getName01())
            ->setName02($Customer->getName02())
            ->setKana01($Customer->getKana01())
            ->setKana02($Customer->getKana02())
            ->setCompanyName($Customer->getCompanyName())
            ->setEmail($Customer->getEmail())
            ->setPhoneNumber($Customer->getPhoneNumber())
            ->setPostalCode($Customer->getPostalCode())
            ->setPref($Customer->getPref())
            ->setAddr01($Customer->getAddr01())
            ->setAddr02($Customer->getAddr02())
            ->setSex($Customer->getSex())
            ->setBirth($Customer->getBirth())
            ->setJob($Customer->getJob());
    }

    public function completeCheckout(Order $Order, $amazonCheckoutSessionId)
    {
        if ($Order->getPaymentTotal() == 0) {
            throw AmazonPaymentException::create(AmazonPaymentException::ZERO_PAYMENT);
        }
        $response = $this->amazonRequestService->completeCheckoutSession($Order, $amazonCheckoutSessionId);
        return $response;
    }

    public function setAmazonOrder(Order $Order, $chargePermissionId, $chargeId = null)
    {
        goto i0XvZ;
        QOS3l:
        $Order->setPaymentDate(new \DateTime());
        goto zLZSA;
        BwscD:
        $AmazonTrading->setChargeId($chargeId);
        goto oLTuI;
        z7UlO:cWjin:
        goto WqWGh;
        qEO81:y3FxZ:
        goto lQYlA;
        d9gWf:
        $AmazonTrading = new AmazonTrading();
        goto qkw5w;
        S_Q1D:
        $Order->addAmazonPay4AmazonTrading($AmazonTrading);
        goto iz7ty;
        ut1xK:
        $AmazonTrading->setRefundCount(0);
        goto S_Q1D;
        qVIak:wzdPu:
        goto ut1xK;
        zyG4N:
        if ($this->Config->getSale() == $this->eccubeConfig['amazon_pay4']['sale']['capture']) {
            goto y3FxZ;
        }
        goto UIXjB;
        WqWGh:
        $billableAmount = floor($payment_total * 9);
        goto MKS3X;
        Fihf1:
        if ($this->Config->getSale() == $this->eccubeConfig['amazon_pay4']['sale']['capture']) {
            goto afx7h;
        }
        goto uOmNd;
        lQYlA:
        $AmazonTrading->setCaptureAmount($payment_total);
        goto qVIak;
        yf1eF:afx7h:
        goto dMFBu;
        zvCGz:
        if ($payment_total * 9 > $this->eccubeConfig['amazon_pay4']['max_billable_amount']) {
            goto cWjin;
        }
        goto zKKxY;
        MKS3X:m39EN:
        goto Fihf1;
        kZtAZ:
        $AmazonTrading->setChargePermissionId($chargePermissionId);
        goto BwscD;
        mVgtD:
        $Order->setAmazonPay4AmazonStatus($AmazonStatus);
        goto TgnNh;
        zKKxY:
        $billableAmount = $payment_total + $this->eccubeConfig['amazon_pay4']['max_billable_amount'];
        goto SYXgd;
        TDJSz:
        $AmazonStatus = $this->amazonStatusRepository->find($this->Config->getSale());
        goto mVgtD;
        dMFBu:
        $Order->setAmazonPay4BillableAmount($billableAmount - $payment_total);
        goto QOS3l;
        oLTuI:
        $AmazonTrading->setAuthoriAmount($payment_total);
        goto zyG4N;
        uOmNd:
        $Order->setAmazonPay4BillableAmount($billableAmount);
        goto qBynb;
        TgnNh:
        $payment_total = (int)$Order->getPaymentTotal();
        goto zvCGz;
        qBynb:
        goto ktgRX;
        goto yf1eF;
        kHA3L:
        goto wzdPu;
        goto qEO81;
        SYXgd:
        goto m39EN;
        goto z7UlO;
        zLZSA:ktgRX:
        goto d9gWf;
        i0XvZ:
        $Order->setAmazonPay4ChargePermissionId($chargePermissionId);
        goto TDJSz;
        qkw5w:
        $AmazonTrading->setOrder($Order);
        goto kZtAZ;
        UIXjB:
        $AmazonTrading->setCaptureAmount(0);
        goto kHA3L;
        iz7ty:
    }

    public function registCustomer(Order $Order, $mail_magazine, $profile = null)
    {
        logs('amazon_pay4')->info('*** 会員登録処理 start ***');
        if (!($profile)) {
            $profile = unserialize($this->session->get($this->sessionAmazonProfileKey));
        }
        $Customer = $this->customerRepository->newCustomer();
        $encoder = $this->encoderFactory->getEncoder($Customer);
        $salt = $encoder->createSalt();
        $password = $this->createPassword();
        $enc_password = $encoder->encodePassword($password, $salt);
        $secretKey = $this->customerRepository->getUniqueSecretKey();
        $Customer->setSalt($salt)->setPassword($enc_password)->setSecretKey($secretKey)->setPoint(0);
        $CustomerStatus = $this->customerStatusRepository->find(CustomerStatus::REGULAR);
        $Customer->setStatus($CustomerStatus);
        $Customer
            ->setName01($Order->getName01())
            ->setName02($Order->getName02())
            ->setPostalCode($Order->getPostalCode())
            ->setEmail($Order->getEmail())
            ->setPref($Order->getPref())
            ->setAddr01($Order->getAddr01())
            ->setAddr02($Order->getAddr02())
            ->setPhoneNumber($Order->getPhoneNumber())
            ->setAmazonUserId($profile->buyerId);

        if ($this->pluginRepository->findOneBy(['code' => 'MailMagazine4', 'enabled' => true])) {
            if ($mail_magazine) {
                $Customer->setMailmagaFlg(true);
            }else{
                $Customer->setMailmagaFlg(false);
            }
        }
        if ($this->pluginRepository->findOneBy(['code' => 'PostCarrier4', 'enabled' => true])) {
            if ($mail_magazine) {
                $Customer->setPostcarrierFlg(true);
            }else{
                $Customer->setPostcarrierFlg(false);
            }
        }

        $this->entityManager->persist($Customer);
        $this->entityManager->flush();
        $token = new UsernamePasswordToken($Customer, null, 'customer', ['ROLE_USER']);
        $this->tokenStorage->setToken($token);

        logs('amazon_pay4')->info('*** 会員登録処理 end ***');

        return $password;
    }

    public function adminRequest($request, $Order)
    {
        goto GH0ex;bc_uv:if ($r === false) {goto rPoCG;}goto BXc1U;XQEoB:$Order->setPaymentDate(new \DateTime());goto vFSAp;ve2bJ:if ($Order->getOrderStatus()->getId() == OrderStatus::DELIVERED) {goto OS70n;}goto kEvIX;eo0pc:$OrderStatus = $this->orderStatusRepository->find($this->orderStatusRepository->find(OrderStatus::CANCEL));goto ve2bJ;oBGBS:wgi12:goto pnCPw;dhVgf:return $amazonErr;goto FnCSA;eEmqh:$amazon_status = $Order->getAmazonPay4AmazonStatus()->getName();goto kMjWK;KwTNN:$billableAmount = $Order->getAmazonPay4BillableAmount();goto pYjS6;FtQ5E:YFIUG:goto AIwNP;yoRyI:$cancel_flg = false;goto nfbcG;jl6vf:icwt4:goto JE46S;aas58:foreach ($AmazonTradings as $AmazonTrading) {goto BCAee;j46k4:GqaNj:goto hZO3a;BCAee:$sumAuthoriAmount += $AmazonTrading->getAuthoriAmount();goto Q5Mtd;Q5Mtd:$sumCaptureAmount += $AmazonTrading->getCaptureAmount();goto j46k4;hZO3a:}goto CmR7S;E45zB:iHrFQ:goto bje19;pnCPw:goto xal5x;goto E_UKM;IdHEp:$this->pointProcessor->rollback($Order, new PurchaseContext());goto QXvVI;g5OGh:goto ltLiI;goto WGBAX;CmR7S:Q8d98:goto DT_os;jOhNa:$Order->setAmazonPay4AmazonStatus($AmazonStatus);goto ouR4l;MCj3t:goto wgi12;goto fHjCD;la5mz:goto cVPGn;goto FtQ5E;bje19:TPsUq:goto y5yYn;IyR2b:xal5x:goto dhVgf;yLOgT:if ($request == 'cancel' && $payment_total == $sumCaptureAmount) {goto YFIUG;}goto LCgW9;p1IG6:$amazonErr = "下記のエラーが発生しました。\n" . "リクエストを受け付けないため処理を終了しました。\n";goto IyR2b;nKu50:$AmazonStatus = $this->amazonStatusRepository->find(AmazonStatus::CAPTURE);goto g5OGh;nfbcG:$totalRefundAmount = $allRefund_flg ? $payment_total : $sumCaptureAmount - $payment_total;goto SAkLA;f26ks:if ($cancel_flg || $allRefund_flg) {goto hpoaO;}goto XQEoB;SAkLA:$resultAmazonTradingInfo = [];goto FK9Vk;AIwNP:$allRefund_flg = true;goto M34sp;FK9Vk:foreach ($AmazonTradings as $key => $AmazonTrading) {goto lSOva;LCstv:QxhXS:goto kEc1w;ei6nJ:HRXYo:goto dyJgw;RsAKf:$refundAmount = $captureAmount > $totalRefundAmount ? $totalRefundAmount : $captureAmount;goto blgXp;CakMx:$newAmazonTrading->setChargePermissionId($r->chargePermissionId);goto UWKTb;jNVQT:ww0Ll:goto I4x0H;tTWad:goto Lqm_4;goto IGK8D;blgXp:$refundCount = $refundCount + 1;goto bslkz;rbJ24:logs('amazon_pay4')->error('aws request error from admin_order r=' . var_export($r, true));goto OeXHr;aUHEO:$totalRefundAmount = $totalRefundAmount - $refundAmount;goto oi75T;JtZOi:$newAmazonTrading->setRefundCount(0);goto LpP1H;lSOva:$amazonChargePermissionId = $AmazonTrading->getChargePermissionId();goto YuKJA;Kyqgi:$newAmazonTrading->setCaptureamount($addAmount);goto JtZOi;np2g4:rfxld:goto G4owO;CF34X:logs('amazon_pay4')->error('aws request error from admin_order r=' . var_export($r, true));goto OY_dt;P1W9f:IV1Pc:goto DLBO7;bslkz:if (!($refundAmount > 0)) {goto IV1Pc;}goto Mm8Bj;UWKTb:$newAmazonTrading->setChargeId($newAmazonChargeId);goto b3uVH;gVnS2:$r = $this->amazonRequestService->captureCharge($amazonChargeId, $Order, $billingAmount);goto YCWCO;P8qCm:logs('amazon_pay4')->error('aws request error from admin_order r = ' . var_export($r, true));goto jC9SF;G4owO:$r = $this->amazonRequestService->createCharge($amazonChargePermissionId, $addAmount);goto THWl7;LmJvz:jwTC5:goto ei6nJ;lIBKe:RzOgu:goto zi4Lx;YCWCO:if (!isset($r->reasonCode)) {goto GROSe;}goto CF34X;WBNiZ:if (!isset($r->chargeId)) {goto jwTC5;}goto iNF2K;LWceR:jcPNV:goto dvKh1;glYe2:$this->entityManager->flush($newAmazonTrading);goto LmJvz;kEc1w:$totalBillingAmount = $totalBillingAmount - $addAmount;goto e3BSB;rViX4:$this->entityManager->flush($AmazonTrading);goto pQckT;Mm8Bj:$r = $this->amazonRequestService->createRefund($amazonChargeId, $refundAmount);goto VVsuR;pFSce:if ($sumCaptureAmount == 0) {goto ivFJc;}goto o170d;IVhwe:if ($amazon_status == 'オーソリ') {goto NFbOt;}goto RfOp2;NY2QS:$AmazonTrading->setCaptureAmount($captureAmount - $refundAmount);goto au7bk;P58U1:$r = $this->amazonRequestService->captureCharge($newAmazonChargeId, $Order, $addAmount);goto xKdNK;aNB7Y:ivFJc:goto HuKd_;THWl7:if (!(isset($r->reasonCode) && $r === 'status_failed')) {goto SfqIo;}goto rez1X;XDqc7:$addAmount = $payment_total - $sumAuthoriAmount;goto np2g4;au7bk:$AmazonTrading->setRefundCount($refundCount);goto EKuL1;HuKd_:$r = $this->amazonRequestService->closeChargePermission($amazonChargePermissionId);goto GocUs;j1J9G:Jd2v4:goto tTWad;LaGMA:$captureAmount = $AmazonTrading->getCaptureAmount();goto mpUAe;z73MC:jP23V:goto NY2QS;o170d:if ($totalRefundAmount == 0) {goto jcPNV;}goto RsAKf;ShX79:lX8WU:goto hiKq2;cyyUg:NFbOt:goto XDqc7;iNF2K:$newAmazonChargeId = $r->chargeId;goto P58U1;b3uVH:$newAmazonTrading->setAuthoriAmount($addAmount);goto Kyqgi;VVsuR:$AmazonTrading->setRefundId($r->refundId);goto P1W9f;dvKh1:goto RzOgu;goto lktej;mpUAe:$refundCount = $AmazonTrading->getRefundCount();goto FYLtn;jC9SF:goto icwt4;goto z73MC;OrWVC:$this->entityManager->persist($newAmazonTrading);goto glYe2;lBh0g:SfqIo:goto WBNiZ;LpP1H:$Order->addAmazonPay4AmazonTrading($newAmazonTrading);goto OrWVC;QTvuI:$AmazonTrading->setCaptureAmount($billingAmount);goto rViX4;Dtbz6:if ($captureAmount > 0 || $totalBillingAmount == 0) {goto ww0Ll;}goto EVJxW;I4x0H:$r = false;goto ShX79;rez1X:logs('amazon_pay4')->error('aws request error from admin_order r=' . var_export($r, true));goto KWKRZ;YuKJA:$amazonChargeId = $AmazonTrading->getChargeId();goto xPQrP;hiKq2:if (!($payment_total > $sumAuthoriAmount && $amazon_status == 'オーソリ' || $totalBillingAmount > 0 && $amazon_status == '売上')) {goto HRXYo;}goto IVhwe;GocUs:$cancel_flg = true;goto j1J9G;HrA9g:GROSe:goto QTvuI;EKuL1:$this->entityManager->flush($AmazonTrading);goto aUHEO;xKdNK:if (!isset($r->reasonCode)) {goto QxhXS;}goto rbJ24;DLBO7:if (!isset($r->reasonCode)) {goto jP23V;}goto P8qCm;IGK8D:g_Has:goto Dtbz6;ou7Fx:goto Jd2v4;goto aNB7Y;RfOp2:$addAmount = $totalBillingAmount;goto BYYTJ;OeXHr:goto icwt4;goto LCstv;FYLtn:if ($request == 'capture') {goto g_Has;}goto pFSce;dyJgw:Lqm_4:goto lIBKe;Ljic2:$newAmazonTrading->setOrder($Order);goto CakMx;lktej:zCC_g:goto ou7Fx;pQckT:goto lX8WU;goto jNVQT;EVJxW:$billingAmount = $authoriAmount > $totalBillingAmount ? $totalBillingAmount : $authoriAmount;goto gVnS2;e3BSB:$newAmazonTrading = new AmazonTrading();goto Ljic2;oi75T:goto zCC_g;goto LWceR;KWKRZ:goto icwt4;goto lBh0g;xPQrP:$authoriAmount = $AmazonTrading->getAuthoriAmount();goto LaGMA;BYYTJ:goto rfxld;goto cyyUg;OY_dt:goto icwt4;goto HrA9g;zi4Lx:}goto jl6vf;Dq_DI:$AmazonStatus = $this->amazonStatusRepository->find(AmazonStatus::CANCEL);goto ltubt;X0qR4:if (!$Customer) {goto bHVJe;}goto nvUUt;y5yYn:goto qn5G1;goto p5AeF;BfsJu:$AmazonTradings = $Order->getAmazonPay4AmazonTradings();goto eEmqh;JE46S:if (isset($r->reasonCode)) {goto Vg9ph;}goto OMxWw;p5AeF:OS70n:goto rls_W;nvUUt:$Customer->setPoint(intval($Customer->getPoint()) - intval($Order->getAddPoint()));goto IdHEp;kMjWK:$sumAuthoriAmount = 0;goto gmLkR;QXvVI:bHVJe:goto w01fu;sAdsC:$Order->setOrderStatus($OrderStatus);goto Dq_DI;o4Z8s:$this->pointProcessor->rollback($Order, new PurchaseContext());goto E45zB;Tf_56:if (!$Customer) {goto iHrFQ;}goto o4Z8s;OMxWw:if (is_object($r)) {goto fsBLY;}goto bc_uv;E_UKM:Vg9ph:goto p1IG6;M34sp:cVPGn:goto yoRyI;AYt35:rPoCG:goto f1fUu;w01fu:qn5G1:goto sAdsC;XqRW9:$this->stockReduceProcessor->rollback($Order, new PurchaseContext());goto IIWJW;kEvIX:if (!($Order->getOrderStatus()->getId() != $OrderStatus->getId())) {goto TPsUq;}goto XqRW9;ouR4l:$this->entityManager->flush();goto oBGBS;rls_W:$Customer = $Order->getCustomer();goto X0qR4;WGBAX:hpoaO:goto eo0pc;IIWJW:$Customer = $Order->getCustomer();goto Tf_56;U_V8h:goto QoykI;goto AYt35;pYjS6:$totalBillingAmount = $payment_total - $sumCaptureAmount;goto yLOgT;BXc1U:$amazonErr = "下記のエラーが発生しました。\n" . var_export($r, true) . "\n";goto U_V8h;ltubt:ltLiI:goto jOhNa;f1fUu:QoykI:goto MCj3t;gmLkR:$sumCaptureAmount = 0;goto aas58;fHjCD:fsBLY:goto f26ks;DT_os:$payment_total = (int) $Order->getPaymentTotal();goto KwTNN;LCgW9:$allRefund_flg = false;goto la5mz;GH0ex:$amazonErr = '';goto BfsJu;vFSAp:$Order->setAmazonPay4BillableAmount($billableAmount);goto nKu50;FnCSA:
    }

    public function checkShippingPref($shippingAddress)
    {
        switch ($shippingAddress->stateOrRegion) {
            case "東京":
                $shippingAddress->stateOrRegion = "東京都";
                break;
            case "大阪":
                $shippingAddress->stateOrRegion = "大阪府";
                break;
            case "京都":
                $shippingAddress->stateOrRegion = "京都府";
                break;
            case "東京都":
                break;
            case "大阪府":
                break;
            case "京都府":
                break;
            case "北海道":
                break;
            default:
                if (!(preg_match('/県$/u', $shippingAddress->stateOrRegion))) {
                    $shippingAddress->stateOrRegion .= '県';
                }
        }
        $Pref = $this->prefRepository->findOneBy(['name' => $shippingAddress->stateOrRegion]);
        if (empty($Pref)) {
            logs('amazon_pay4')->info('*** 都道府県マッチングエラー *** addr = ' . var_export($shippingAddress, true));
            return false;
        }
        return true;
    }

    public function createPassword($length = 8)
    {
        $pwd = [];
        $pwd_strings = ["sletter" => range('a', 'z'), "cletter" => range('A', 'Z'), "number" => range('0', '9')];

        while (count($pwd) < $length) {
            $key = array_rand($pwd_strings);
            $pwd[] = $pwd_strings[$key][array_rand($pwd_strings[$key])];
        }

        shuffle($pwd);
        return implode($pwd);
    }
}