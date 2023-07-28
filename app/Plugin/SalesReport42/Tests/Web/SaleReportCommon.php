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

namespace Plugin\SalesReport42\Tests\Web;

use Eccube\Entity\Customer;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\TaxRule;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;

/**
 * Class SaleReportCommon.
 */
class SaleReportCommon extends AbstractAdminWebTestCase
{
    /** @var CustomerRepository */
    protected $customerRepository;

    /** @var OrderStatusRepository */
    protected $orderStatusRepository;

    /**
     * Set up function.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->customerRepository = $this->entityManager->getRepository(Customer::class);
        $this->orderStatusRepository = $this->entityManager->getRepository(OrderStatus::class);
    }

    /**
     * createCustomerByNumber.
     *
     * @param int $number
     *
     * @return array
     */
    public function createCustomerByNumber($number = 5)
    {
        $arrCustomer = [];
        $current = new \DateTime();
        for ($i = 0; $i < $number; ++$i) {
            $email = 'customer0'.$i.'@mail.com';
            $age = rand(10, 50);
            $age = $current->modify("-$age years");
            $Customer = $this->createCustomer($email);
            $arrCustomer[] = $Customer->getId();
            $Customer->setBirth($age);
            $this->entityManager->persist($Customer);
            $this->entityManager->flush();
        }

        return $arrCustomer;
    }

    /**
     * createOrderByCustomer.
     *
     * @param int $number
     *
     * @return array $arrOrder
     */
    public function createOrderByCustomer($number = 5)
    {
        $arrCustomer = $this->createCustomerByNumber($number);
        $current = new \DateTime();
        $arrOrder = [];
        for ($i = 0; $i < count($arrCustomer); ++$i) {
            $Customer = $this->customerRepository->find($arrCustomer[$i]);
            $Order = $this->createOrder($Customer);
            $Order->setOrderStatus($this->orderStatusRepository->find(OrderStatus::NEW));
            $Order->setOrderDate($current);
            $arrOrder[] = $Order;
            $this->entityManager->persist($Order);
            $this->entityManager->flush();
        }

        return $arrOrder;
    }

    /**
     * @param Order[] $Orders
     * @param TaxRule $TaxRule
     */
    public function changeOrderDetail($Orders, $TaxRule)
    {
        /** @var Order $Order */
        foreach ($Orders as $Order) {
            $totalTax = 0;
            $total = 0;
            foreach ($Order->getOrderItems() as $orderItem) {
                /** @var OrderItem $orderItem */
                if ($orderItem->isProduct()) {
                    $TaxRate = $TaxRule->getTaxRate() / 100;
                    $tax = 500 * $TaxRate;
                    $orderItem->setPrice(500);
                    $orderItem->setQuantity(1);
                    $orderItem->setTax($tax);
                    $this->entityManager->persist($orderItem);
                    $this->entityManager->flush();
                    $totalTax += $tax;
                    $total += 500 + $tax;
                }
            }

            $Order->setSubtotal($total);
            $Order->setTotal($total);
            $Order->setTax($totalTax);
            $this->entityManager->persist($Order);
            $this->entityManager->flush();
        }
    }
}
