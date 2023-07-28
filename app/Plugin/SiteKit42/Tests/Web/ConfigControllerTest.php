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

namespace Plugin\SiteKit42\Tests\Web;

use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;


/**
 * Class ConfigControllerTest.
 */
class ConfigControllerTest extends AbstractAdminWebTestCase
{
    public function testIndex()
    {
        $this->client->request('GET', $this->generateUrl('site_kit42_admin_config'));
        self::assertTrue($this->client->getResponse()->isSuccessful());
    }
}
