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

namespace Plugin\MailMagazine42\Tests\Web\Admin;

use Plugin\MailMagazine42\Tests\Web\MailMagazineCommon;

class MailMagazineControllerTest extends MailMagazineCommon
{
    /**
     * Test routing.
     */
    public function testRoutingMailMagazine()
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testMailMagazineSearchWithBirthmonthLowOctorber()
    {
        $MaiCustomer = $this->createMailMagazineCustomer();
        //test search with birth month < 10
        $MaiCustomer->setBirth(new \DateTime('2016-09-19 23:59:59'));
        $this->entityManager->persist($MaiCustomer);
        $this->entityManager->flush();
        $birth_month = $MaiCustomer->getBirth()->format('n');
        $searchForm = $this->createSearchForm($MaiCustomer, $birth_month);
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine'),
            ['mail_magazine' => $searchForm]
        );
        $this->assertStringContainsString('検索結果：1件が該当しました', $crawler->filter('.c-outsideBlock__contents.mb-5 > span')->text());
    }

    public function testMailMagazineSearchWithBirthmonthHightOctorber()
    {
        $MaiCustomer = $this->createMailMagazineCustomer();
        //test search with birth month > 10
        $MaiCustomer->setBirth(new \DateTime('2016-11-19 23:59:59'));
        $this->entityManager->persist($MaiCustomer);
        $this->entityManager->flush();
        //because 誕生月 select box value start from 0. We need minus 1
        $birth_month = $MaiCustomer->getBirth()->format('n');
        $searchForm = $this->createSearchForm($MaiCustomer, $birth_month);
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine'),
            ['mail_magazine' => $searchForm]
        );
        $this->assertStringContainsString('検索結果：1件が該当しました', $crawler->filter('.c-outsideBlock__contents.mb-5 > span')->text());
    }

    public function testMailMagazineSearchWithBirthmonthNull()
    {
        $MaiCustomer = $this->createMailMagazineCustomer();
        $searchForm = $this->createSearchForm($MaiCustomer);
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine'),
            ['mail_magazine' => $searchForm]
        );
        $this->assertStringContainsString('検索結果：1件が該当しました', $crawler->filter('.c-outsideBlock__contents.mb-5 > span')->text());
    }

    public function testSelect()
    {
        $MailTemplate = $this->createMagazineTemplate();

        $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine_select', ['id' => $MailTemplate->getId()]),
            ['mail_magazine' => [
                'template' => $MailTemplate->getId(),
                'subject' => $MailTemplate->getSubject(),
                'body' => $MailTemplate->getBody(),
                '_token' => 'dummy',
            ]]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testSelect_NotPost()
    {
        $MailTemplate = $this->createMagazineTemplate();
        $this->client->request(
            'GET',
            $this->generateUrl('plugin_mail_magazine_select', ['id' => $MailTemplate->getId()]),
            ['mail_magazine' => [
                'template' => $MailTemplate->getId(),
                'subject' => $MailTemplate->getSubject(),
                'body' => $MailTemplate->getBody(),
                '_token' => 'dummy',
            ]]
        );
        $this->assertEquals(405, $this->client->getResponse()->getStatusCode());
    }

    public function testConfirm_InValid()
    {
        $MailTemplate = $this->createMagazineTemplate();

        $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine_select', ['id' => $MailTemplate->getId()]),
            ['mail_magazine' => [
                'template' => $MailTemplate->getId(),
                'subject' => $MailTemplate->getSubject(),
                'body' => $MailTemplate->getBody(),
                'mode' => 'confirm',
                '_token' => 'dummy',
            ]]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testConfirm()
    {
        $MailTemplate = $this->createMagazineTemplate();

        $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine_select', ['id' => $MailTemplate->getId()]),
            ['mail_magazine' => [
                'id' => $MailTemplate->getId(),
                'template' => $MailTemplate->getId(),
                'subject' => $MailTemplate->getSubject(),
                'body' => $MailTemplate->getBody(),
                'mode' => 'confirm',
                '_token' => 'dummy',
            ]]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testPrepare()
    {
//        $this->initializeMailCatcher();
        $MailTemplate = $this->createMagazineTemplate();
        $MaiCustomer = $this->createMailMagazineCustomer();
        $searchForm = $this->createSearchForm($MaiCustomer);
        $searchForm['template'] = $MailTemplate->getId();
        $searchForm['subject'] = $MailTemplate->getSubject();
        $searchForm['body'] = $MailTemplate->getBody();

        $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine_prepare', ['id' => $MailTemplate->getId()]),
            ['mail_magazine' => $searchForm]
        );

        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('plugin_mail_magazine_history')));

//        $Messages = $this->getMailCatcherMessages();
//        $Message = $this->getMailCatcherMessage($Messages[0]->id);
//
//        $this->expected = $searchForm['subject'];
//        $this->actual = $Message->subject;
//        $this->verify();
//        $this->cleanUpMailCatcherMessages();
    }

    public function testPagination()
    {
        for ($i = 0; $i < 30; ++$i) {
            $this->createMailMagazineCustomer();
        }
        $searchForm = [
            '_token' => 'dummy',
            'sex' => ['1'],
            'multi' => '',
            'customer_status' => [],
            'birth_month' => '',
            'birth_start' => '',
            'birth_end' => '',
            'pref' => '',
            'phone_number' => '',
            'create_date_start' => '',
            'create_date_end' => '',
            'update_date_start' => '',
            'update_date_end' => '',
            'buy_total_start' => '',
            'buy_total_end' => '',
            'buy_times_start' => '',
            'buy_times_end' => '',
            'buy_product_name' => '',
            'last_buy_start' => '',
            'last_buy_end' => '',
        ];
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine'),
            ['mail_magazine' => $searchForm]
        );

        $pageNumber = $crawler->filter('.c-outsideBlock__contents.mb-5 > span')->html();
        $this->assertMatchesRegularExpression('/件/', $pageNumber);

        //pagination
        $crawler = $this->client->request(
            'GET',
            $this->generateUrl('plugin_mail_magazine_page', ['page_no' => '2'])
        );

        //check result
        $pageNumber = $crawler->filter('.c-outsideBlock__contents.mb-5 > span')->html();
        $this->assertMatchesRegularExpression('/件/', $pageNumber);

        //check search condition
        $sexCheckbox = $crawler->filter('#mail_magazine_sex_1:checked')->count();
        $this->assertEquals(1, $sexCheckbox);
    }
}
