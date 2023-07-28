<?php

namespace Plugin\SlnPayment42\Repository;

use Plugin\SlnPayment42\Entity\SlnAgreement;
use Eccube\Repository\AbstractRepository;
use Eccube\Entity\Order;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Persistence\ManagerRegistry;

class SlnAgreementRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SlnAgreement::class);
    }
    
    /**
     * @param Order $Order
     * @return Plugin\SlnPayment42\Entity\SlnAgreement;
     */

    public function getStatus(Order $Order)
    {
        //ステータス存在判断
        $orderId = $Order->getId();
        $OrderStatus = $this->find($orderId);
        
        if (is_null($OrderStatus)) {
            $OrderStatus = new SlnAgreement($orderId);
        }

        return $OrderStatus;
    }

    public function setAgreementStatus(Order $Order, $flag)
    {
        $payStatus = $this->find($Order->getId());

        if ($flag == 0 && !is_null($payStatus)) {
            $payStatus->setAgreeFlg($flag);
            $em = $this->getEntityManager();
            $em->persist($payStatus);
            $em->flush();
        }

        if ($flag == 1) {
            $payStatus = $this->getStatus($Order);
            $payStatus->setAgreeFlg($flag);
            $em = $this->getEntityManager();
            $em->persist($payStatus);
            $em->flush();
        }
    }


    public function getAgreementStatus($orderId = null)
    {
        $OrderStatus = $this->find($orderId);
        if (is_null($OrderStatus)) {
            return null;
        } else {
            $agreeFlg = $OrderStatus->getAgreeFlg();
            return $agreeFlg;
        }     
    }
}
