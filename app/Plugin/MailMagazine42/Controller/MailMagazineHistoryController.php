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

namespace Plugin\MailMagazine42\Controller;

use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\MailMagazine42\Entity\MailMagazineSendHistory;
use Plugin\MailMagazine42\Repository\MailMagazineSendHistoryRepository;
use Plugin\MailMagazine42\Service\MailMagazineService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Entity\Master\Sex;

class MailMagazineHistoryController extends AbstractController
{
    /**
     * @var MailMagazineSendHistoryRepository
     */
    protected $mailMagazineSendHistoryRepository;

    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var MailMagazineService
     */
    protected $mailMagazineService;

    /**
     * MailMagazineHistoryController constructor.
     *
     * @param MailMagazineService $mailMagazineService
     * @param MailMagazineSendHistoryRepository $mailMagazineSendHistoryRepository
     * @param PageMaxRepository $pageMaxRepository
     */
    public function __construct(
        MailMagazineService $mailMagazineService,
        MailMagazineSendHistoryRepository $mailMagazineSendHistoryRepository,
        PageMaxRepository $pageMaxRepository
    ) {
        $this->mailMagazineService = $mailMagazineService;
        $this->mailMagazineSendHistoryRepository = $mailMagazineSendHistoryRepository;
        $this->pageMaxRepository = $pageMaxRepository;
    }

    /**
     * 配信履歴一覧.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/history", name="plugin_mail_magazine_history")
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/history/{page_no}",
     *     requirements={"page_no" = "\d+"},
     *     name="plugin_mail_magazine_history_page"
     * )
     * @Template("@MailMagazine42/admin/history_list.twig")
     *
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param int $page_no
     *
     * @return array
     */
    public function index(Request $request, PaginatorInterface $paginator, $page_no = 1)
    {
        $pageNo = $page_no;
        $pageMaxis = $this->pageMaxRepository->findAll();
        $pageCount = $this->eccubeConfig['eccube_default_page_count'];
        $pageCountParam = $request->get('page_count');
        if ($pageCountParam && is_numeric($pageCountParam)) {
            foreach ($pageMaxis as $pageMax) {
                if ($pageCountParam == $pageMax->getName()) {
                    $pageCount = $pageMax->getName();
                    break;
                }
            }
        }

        // リストをView変数に突っ込む
        $pagination = null;
        $searchForm = $this->formFactory
            ->createBuilder()
            ->getForm();
        $searchForm->handleRequest($request);
        $searchData = $searchForm->getData();

        $qb = $this->mailMagazineSendHistoryRepository->getQueryBuilderBySearchData($searchData);

        $pagination = $paginator->paginate($qb, $pageNo, $pageCount);

        return [
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_count' => $pageCount,
        ];
    }

    /**
     * プレビュー
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/history/{id}/preview",
     *     requirements={"id":"\d+"},
     *     name="plugin_mail_magazine_history_preview"
     * )
     * @Template("@MailMagazine42/admin/history_preview.twig")
     *
     * @param MailMagazineSendHistory $mailMagazineSendHistory
     *
     * @return array
     */
    public function preview(MailMagazineSendHistory $mailMagazineSendHistory)
    {
        // 配信履歴を取得する
        return [
            'history' => $mailMagazineSendHistory,
        ];
    }

    /**
     * 配信条件を表示する.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/history/{id}/condition",
     *      requirements={"id":"\d+"},
     *      name="plugin_mail_magazine_history_condition",
     * )
     * @Template("@MailMagazine42/admin/history_condition.twig")
     *
     * @param MailMagazineSendHistory $mailMagazineSendHistory
     *
     * @throws BadRequestHttpException
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response|array
     */
    public function condition(MailMagazineSendHistory $mailMagazineSendHistory)
    {
        // 検索条件をアンシリアライズする
        // base64,serializeされているので注意すること
        $searchData = unserialize(base64_decode($mailMagazineSendHistory->getSearchData()));

        // 区分値を文字列に変更する
        // 必要な項目のみ
        $displayData = $this->searchDataToDisplayData($searchData);

        return [
            'search_data' => $displayData,
        ];
    }

    /**
     * search_dataの配列を表示用に変換する.
     *
     * @param array $searchData
     *
     * @return array
     */
    protected function searchDataToDisplayData($searchData)
    {
        $data = $searchData;

        // 会員種別
        $val = [];
        if (isset($searchData['customer_status']) && is_array($searchData['customer_status'])) {
            array_map(function ($CustomerStatus) use (&$val) {
                /* @var \Eccube\Entity\Master\CustomerStatus $CustomerStatus */
                $val[] = $CustomerStatus->getName();
            }, $searchData['customer_status']);
        }
        $data['customer_status'] = implode(', ', $val);

        // 性別
        $val = [];
        if (isset($searchData['sex']) && is_array($searchData['sex'])) {
            array_map(function ($Sex) use (&$val) {
                /* @var Sex $Sex */
                $val[] = $Sex->getName();
            }, $searchData['sex']);
        }
        $data['sex'] = implode(', ', $val);

        return $data;
    }

    /**
     * 配信履歴を論理削除する
     * RequestがPOST以外の場合はBadRequestHttpExceptionを発生させる.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/history/{id}/delete",
     *     requirements={"id":"\d+"},
     *     name="plugin_mail_magazine_history_delete",
     *     methods={"POST"}
     * )
     *
     * @param MailMagazineSendHistory $mailMagazineSendHistory
     *
     * @throws BadRequestHttpException
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(MailMagazineSendHistory $mailMagazineSendHistory)
    {
        try {
            $this->isTokenValid();
            $id = $mailMagazineSendHistory->getId();
            $this->mailMagazineSendHistoryRepository->delete($mailMagazineSendHistory);
            $this->entityManager->flush();

            $this->mailMagazineService->unlinkHistoryFiles($id);

            $this->addSuccess('admin.mailmagazine.history.delete.sucesss', 'admin');
        } catch (\Exception $e) {
            $this->addError('admin.mailmagazine.history.delete.failure', 'admin');
        }

        // メルマガテンプレート一覧へリダイレクト
        return $this->redirect($this->generateUrl('plugin_mail_magazine_history'));
    }

    /**
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/history/{id}/retry",
     *     requirements={"id":"\d+"},
     *     name="plugin_mail_magazine_history_retry",
     *     methods={"POST"}
     * )
     *
     * @param Request $request
     * @param MailMagazineSendHistory $mailMagazineSendHistory
     *
     * @return mixed
     */
    public function retry(Request $request, MailMagazineSendHistory $mailMagazineSendHistory)
    {
        // Ajax/POSTでない場合は終了する
        if (!$request->isXmlHttpRequest() || 'POST' !== $request->getMethod()) {
            throw new BadRequestHttpException();
        }

        try {
            log_info('メルマガ再試行前処理開始', ['id' => $mailMagazineSendHistory->getId()]);

            $this->mailMagazineService->markRetry($mailMagazineSendHistory->getId());

            log_info('メルマガ再試行前処理完了', ['id' => $mailMagazineSendHistory->getId()]);

            $status = true;
        } catch (\Exception $e) {
            log_error(__METHOD__, [$e]);
            $status = false;
        }

        return $this->json(['status' => $status]);
    }

    /**
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/history/result/{id}",
     *     requirements={"id":"\d+"},
     *     name="plugin_mail_magazine_history_result"
     * )
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/history/result/{id}/{page_no}",
     *     requirements={"id":"\d+", "page_no" = "\d+"},
     *     name="plugin_mail_magazine_history_result_page"
     * )
     * @Template("@MailMagazine42/admin/history_result.twig")
     *
     * @param Request $request
     * @param MailMagazineSendHistory $mailMagazineSendHistory
     * @param PaginatorInterface $paginator
     * @param int $page_no
     *
     * @return mixed
     */
    public function result(Request $request, MailMagazineSendHistory $mailMagazineSendHistory, PaginatorInterface $paginator, $page_no = 1)
    {
        $resultFile = $this->mailMagazineService->getHistoryFileName($mailMagazineSendHistory->getId(), false);
        $pageMaxis = $this->pageMaxRepository->findAll();
        $pageCount = $request->get('page_count');
        $pageCount = $pageCount ? $pageCount : $this->eccubeConfig['eccube_default_page_count'];

        $pagination = $paginator->paginate($resultFile,
            $page_no,
            $pageCount
        );

        return [
            'historyId' => $mailMagazineSendHistory->getId(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_count' => $pageCount,
        ];
    }
}
