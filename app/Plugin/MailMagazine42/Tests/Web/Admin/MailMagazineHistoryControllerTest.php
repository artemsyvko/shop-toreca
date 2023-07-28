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

class MailMagazineHistoryControllerTest extends MailMagazineCommon
{
    public function testIndex()
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_history')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testPreview()
    {
        $MailCustomer = $this->createMailMagazineCustomer();
        $SendHistory = $this->createSendHistoy($MailCustomer);

        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_history_preview', ['id' => $SendHistory->getId()])
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testPreview_IdIncorrect()
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_history_preview', ['id' => 9999999])
        );

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testPreview_IdIsNull()
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_history_preview', ['id' => null])
        );

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testCondition()
    {
        $MailCustomer = $this->createMailMagazineCustomer();
        $SendHistory = $this->createSendHistoy($MailCustomer);

        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_history_condition', ['id' => $SendHistory->getId()])
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testCondition_IdIncorrect()
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_history_condition', ['id' => 9999999])
        );

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testCondition_IdIsNull()
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_history_condition', ['id' => null])
        );
        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testDelete()
    {
        $MailCustomer = $this->createMailMagazineCustomer();
        $SendHistory = $this->createSendHistoy($MailCustomer);

        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_history_delete', ['id' => $SendHistory->getId()])
        );

        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('plugin_mail_magazine_history')));
    }

    public function testDelete_IdIncorrect()
    {
        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_history_delete', ['id' => 9999999])
        );

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testDelete_IdIsNull()
    {
        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_history_delete', ['id' => null])
        );
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testDelete_NotPost()
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_history_delete', ['id' => null])
        );
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }
}
