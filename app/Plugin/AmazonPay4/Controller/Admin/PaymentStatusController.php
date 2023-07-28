<?php

namespace Plugin\AmazonPay4\Controller\Admin;

use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\AmazonPay4\Form\Type\Admin\SearchPaymentType;
use Plugin\AmazonPay4\Service\Method\AmazonPay;
use Plugin\AmazonPay4\Service\AmazonOrderHelper;
use Plugin\AmazonPay4\Repository\Master\AmazonStatusRepository;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class PaymentStatusController extends AbstractController
{
    protected $paymentStatusRepository;
    protected $pageMaxRepository;
    protected $orderRepository;
    protected $paymentRepository;
    protected $amazonStatusRepository;
    protected $amazonOrderHelper;

    public function __construct(
        AmazonStatusRepository $amazonStatusRepository,
        AmazonOrderHelper $amazonOrderHelper,
        PageMaxRepository $pageMaxRepository,
        OrderRepository $orderRepository,
        PaymentRepository $paymentRepository
    ){
        $this->amazonStatusRepository = $amazonStatusRepository;
        $this->amazonOrderHelper = $amazonOrderHelper;
        $this->pageMaxRepository = $pageMaxRepository;
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * 決済状況一覧画面
     *
     * @Route("/%eccube_admin_route%/amazon_pay4/payment_status", name="amazon_pay4_admin_payment_status")
     * @Route("/%eccube_admin_route%/amazon_pay4/payment_status/{page_no}", requirements={"page_no" = "\d+"}, name="amazon_pay4_admin_payment_status_pageno")
     * @Template("@AmazonPay4/admin/Order/payment_status.twig")
     */
    public function index(Request $request, PaginatorInterface $paginator, $page_no = null)
    {
        $searchForm = $this->createForm(SearchPaymentType::class);
        $page_count = $this->session->get('amazon_pay4.admin.payment_status.search.page_count', $this->eccubeConfig->get('eccube_default_page_count'));
        $page_count_param = (int) $request->get('page_count');
        $pageMaxis = $this->pageMaxRepository->findAll();
        if ($page_count_param) {
            foreach ($pageMaxis as $pageMax) {
                if ($page_count_param == $pageMax->getName()) {
                    $page_count = $pageMax->getName();
                    $this->session->set('amazon_pay4.admin.payment_status.search.page_count', $page_count);
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
            if ($searchForm->isValid()) {
                $page_no = 1;
                $searchData = $searchForm->getData();
                $this->session->set('amazon_pay4.admin.payment_status.search', FormUtil::getViewData($searchForm));
                $this->session->set('amazon_pay4.admin.payment_status.search.page_no', $page_no);
            }else{
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $page_count,
                    'has_errors' => true
                ];
            }
        }else{
            if (null !== $page_no || $request->get('resume')) {
                if ($page_no) {
                    $this->session->set('amazon_pay4.admin.payment_status.search.page_no', (int) $page_no);
                }else{
                    $page_no = $this->session->get('amazon_pay4.admin.payment_status.search.page_no', 1);
                }
                $viewData = $this->session->get('amazon_pay4.admin.payment_status.search', []);
                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
            }else{
                $page_no = 1;
                $searchData = [];
                $this->session->set('amazon_pay4.admin.payment_status.search', $searchData);
                $this->session->set('amazon_pay4.admin.payment_status.search.page_no', $page_no);
            }

        }

        $qb = $this->createQueryBuilder($searchData);
        $pagination = $paginator->paginate($qb, $page_no, $page_count);

        return ['searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $page_count,
            'has_errors' => false
        ];
    }
    /**
     * 一括処理.
     *
     * @Route("/%eccube_admin_route%/amazon_pay4/payment_status/request_action/", name="amazon_pay4_admin_payment_status_request", methods={"POST"})
     */
    public function requestAction(Request $request)
    {
        $amazon_request = $request->get('amazon_request');
        if (!isset($amazon_request)) {
            throw new BadRequestHttpException();
        }
        $this->isTokenValid();
        $requestOrderId = $request->get('amazon_order_id');
        if (!empty($requestOrderId)) {
            $ids = [$requestOrderId];
        }else{
            $ids = $request->get($amazon_request . '_id');
        }
        $request_name = $amazon_request == 'capture' ? '売上' : '取消';
        $Orders = $this->orderRepository->findBy(['id' => $ids]);

        foreach ($Orders as $Order) {
            $amazonErr = $this->amazonOrderHelper->adminRequest($amazon_request, $Order);
            if (empty($amazonErr)) {
                $result_message = "■注文番号:" . $Order->getId() . " ： " . $request_name . "処理に成功しました。";
                $this->addSuccess($result_message, 'admin');
            }else{
                $result_message = "■注文番号:" . $Order->getId() . " ： " . $request_name . "処理に失敗しました。" . $amazonErr;
                $this->addError($result_message, 'admin');
            }

        }

        return $this->redirectToRoute('amazon_pay4_admin_payment_status_pageno', ['resume' => Constant::ENABLED]);
    }

    private function createQueryBuilder(array $searchData){
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('o')
            ->from(Order::class, 'o')
            ->orderBy('o.order_date', 'DESC')
            ->addOrderBy('o.id', 'DESC');

        $Payment = $this->paymentRepository->findOneBy(['method_class' => AmazonPay::class]);
        $qb->andWhere('o.Payment = :Payment')
            ->setParameter('Payment', $Payment)
            ->andWhere('o.AmazonPay4AmazonStatus IS NOT NULL');

        $qb->andWhere('o.order_date IS NOT NULL');
        if (!empty($searchData['OrderStatuses']) && count($searchData['OrderStatuses']) > 0) {
            $qb->andWhere($qb->expr()->in('o.OrderStatus', ':OrderStatuses'))->setParameter('OrderStatuses', $searchData['OrderStatuses']);
        }
        if (!empty($searchData['AmazonStatuses']) && count($searchData['AmazonStatuses']) > 0) {
            $qb->andWhere($qb->expr()->in('o.AmazonPay4AmazonStatus', ':AmazonStatuses'))->setParameter('AmazonStatuses', $searchData['AmazonStatuses']);
        }
        return $qb;
    }
}