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
use Plugin\MailMagazine42\Entity\MailMagazineTemplate;
use Plugin\MailMagazine42\Repository\MailMagazineTemplateRepository;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Plugin\MailMagazine42\Form\Type\MailMagazineTemplateEditType;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MailMagazineTemplateController extends AbstractController
{
    /**
     * @var MailMagazineTemplateRepository
     */
    protected $mailMagazineTemplateRepository;

    /**
     * MailMagazineTemplateController constructor.
     *
     * @param MailMagazineTemplateRepository $mailMagazineTemplateRepository
     */
    public function __construct(
        MailMagazineTemplateRepository $mailMagazineTemplateRepository
    ) {
        $this->mailMagazineTemplateRepository = $mailMagazineTemplateRepository;
    }

    /**
     * 一覧表示.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template", name="plugin_mail_magazine_template")
     * @Template("@MailMagazine42/admin/template_list.twig")
     */
    public function index()
    {
        $templateList = $this->mailMagazineTemplateRepository->findAll();

        return [
            'TemplateList' => $templateList,
        ];
    }

    /**
     * preview画面表示.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template/{id}/preview",
     *     requirements={"id":"\d+"},
     *     name="plugin_mail_magazine_template_preview"
     * )
     * @Template("@MailMagazine42/admin/preview.twig")
     *
     * @param MailMagazineTemplate $mailMagazineTemplate
     *
     * @return array
     */
    public function preview(MailMagazineTemplate $mailMagazineTemplate)
    {
        // プレビューページ表示
        return [
            'Template' => $mailMagazineTemplate,
        ];
    }

    /**
     * メルマガテンプレートを論理削除.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template/{id}/delete",
     *     requirements={"id":"\d+"},
     *     name="plugin_mail_magazine_template_delete",
     *     methods={"POST"}
     * )
     *
     * @param MailMagazineTemplate $mailMagazineTemplate
     *
     * @return RedirectResponse
     */
    public function delete(MailMagazineTemplate $mailMagazineTemplate)
    {
        // POSTかどうか判定
        // パラメータ$idにマッチするデータが存在するか判定
        // POSTかつ$idに対応するdtb_mailmagazine_templateのレコードがあれば、del_flg = 1に設定して更新
        try {
            $this->isTokenValid();
            $this->mailMagazineTemplateRepository->delete($mailMagazineTemplate);
            $this->entityManager->flush();
            $this->addSuccess('admin.mailmagazine.template.delete.complete', 'admin');
        } catch (\Exception $e) {
            $this->addError('admin.mailmagazine.template.delete.failure', 'admin');
        }

        // メルマガテンプレート一覧へリダイレクト
        return $this->redirect($this->generateUrl('plugin_mail_magazine_template'));
    }

    /**
     * テンプレート編集画面表示.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template/{id}/edit",
     *     requirements={"id":"\d+"},
     *     name="plugin_mail_magazine_template_edit"
     * )
     * @Template("@MailMagazine42/admin/template_edit.twig")
     *
     * @param MailMagazineTemplate $mailMagazineTemplate
     *
     * @return array
     */
    public function edit(MailMagazineTemplate $mailMagazineTemplate)
    {
        // formの作成
        $form = $this->formFactory
            ->createBuilder(MailMagazineTemplateEditType::class, $mailMagazineTemplate)
            ->getForm();

        return [
            'form' => $form->createView(),
            'Template' => $mailMagazineTemplate,
        ];
    }

    /**
     * テンプレート編集確定処理.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template/commit/{id}",
     *     requirements={"id":"\d+"},
     *     name="plugin_mail_magazine_template_commit",
     *     methods={"POST"}
     * )
     * @Template("@MailMagazine42/admin/template_edit.twig")
     *
     * @param Request $request
     * @param int $id
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function commit(Request $request, $id = null)
    {
        $Template = $id ? $this->mailMagazineTemplateRepository->find($id) : new MailMagazineTemplate();

        // データが存在しない場合はメルマガテンプレート一覧へリダイレクト
        if (is_null($Template)) {
            $this->addError('admin.mailmagazine.template.data.notfound', 'admin');

            return $this->redirect($this->generateUrl('plugin_mail_magazine_template'));
        }

        // Formを取得
        $builder = $this->formFactory->createBuilder(MailMagazineTemplateEditType::class, $Template);
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // 入力項目確認処理を行う.
            // エラーであれば元の画面を表示する

            if (!$form->isValid()) {
                $this->addError('admin.flash.register_failed', 'admin');

                return [
                    'form' => $form->createView(),
                    'Template' => $Template,
                ];
            }

            try {
                $this->mailMagazineTemplateRepository->save($Template);
                $this->entityManager->flush();
                // 成功時のメッセージを登録する
                $this->addSuccess('admin.mailmagazine.template.save.complete', 'admin');
            } catch (\Exception $e) {
                $this->addError('admin.mailmagazine.template.save.failure', 'admin');

                return [
                    'form' => $form->createView(),
                    'Template' => $Template,
                ];
            }
        }

        // メルマガテンプレート一覧へリダイレクト
        return $this->redirect($this->generateUrl('plugin_mail_magazine_template'));
    }

    /**
     * メルマガテンプレート登録画面を表示する.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template/regist", name="plugin_mail_magazine_template_regist")
     * @Template("@MailMagazine42/admin/template_edit.twig")
     *
     * @return array
     */
    public function regist()
    {
        $Template = new MailMagazineTemplate();

        // formの作成
        $form = $this->formFactory
            ->createBuilder(MailMagazineTemplateEditType::class, $Template)
            ->getForm();

        return [
            'form' => $form->createView(),
            'Template' => $Template,
        ];
    }
}
