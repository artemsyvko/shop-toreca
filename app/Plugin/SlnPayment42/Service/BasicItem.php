<?php

namespace Plugin\SlnPayment42\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;

class BasicItem 
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * 支払い回数
     */
    public function getCreditPayMethod()
    {
        return $this->container->getParameter('arrPayKbnKaisu');
    }
    
    /**
     * セキュリティコード
     */
    public function getSecurityCode()
    {
        return $this->container->getParameter('arrSecurityCode');
    }
    
    public function get3DPay()
    {
        return $this->container->getParameter('arr3DPay');
    }
    
    /**
     * 会員登録機能
     */
    public function getMemberRegist()
    {
        return $this->container->getParameter('arrMemberRegist');
    }
    
    /**
     * クイック決済
     */
    public function getQuickAccounts()
    {
        return $this->container->getParameter('arrQuickAccounts');
    }
    
    /**
     * 利用できるオンライン収納決済方法
     */
    public function getOnlinePaymentMethod()
    {
        return $this->container->getParameter('arrOnlinePaymentMethod');
    }
    
    /**
     * 利用できるオンライン収納決済方法(表示Short ver)
     */
    public function getShortPaymentMethod()
    {
        return $this->container->getParameter('arrShortPaymentMethod');
    }
    
    /**
     * 認証アシスト項目
     */
    public function getAssistance()
    {
        return $this->container->getParameter('arrAssistance');
    }
    
    /**
     * 処理区分
     */
    public function getJobCd()
    {
        return $this->container->getParameter('arrJobCd');
    }
    
    /**
     * カード決済手続き
     */
    public function getCardProcedure()
    {
        return $this->container->getParameter('arrCardProcedure');
    }
    
    /**
     * 支払い機関
     */
    public function getCvsCd()
    {
        return $this->container->getParameter('arrCvsCd');
    }
    
    /**
     * カード入金完了後に受注ステータス
     */
    public function getGatheringOrderStatus()
    {
        return $this->container->getParameter('arrGatheringOrderStatus');
    }

    /**
     * クレジット決済接続先
     */
    public function getCreditConnectionDestination()
    {
        return $this->container->getParameter('arrCreditConnectionDestination');
    }

    /**
     * 3Dセキュア認証接続先
     */
    public function getThreedConnectionDestination()
    {
        return $this->container->getParameter('arrThreedConnectionDestination');
    }

    /**
     * オンライン収納代行接続先
     */
    public function getCvsConnectionDestination()
    {
        return $this->container->getParameter('arrCvsConnectionDestination');
    }
}


