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

namespace Plugin\MailMagazine42\Event;

use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin\MailMagazine42\Service\MailMagazineService;

class MailMagazineHistoryFilePaginationSubscriber implements EventSubscriberInterface
{
    /**
     * @var MailMagazineService
     */
    protected $mailMagazineService;

    /**
     * MailMagazineHistoryFilePaginationSubscriber constructor.
     *
     * @param MailMagazineService $mailMagazineService
     */
    public function __construct(MailMagazineService $mailMagazineService)
    {
        $this->mailMagazineService = $mailMagazineService;
    }

    public function items(ItemsEvent $event)
    {
        $mailMagazineDir = $this->mailMagazineService->getMailMagazineDir();
        if (!is_string($event->target) || strpos($event->target, $mailMagazineDir) !== 0) {
            return;
        }

        $event->stopPropagation();
        $file = $event->target;
        if (!file_exists($file)) {
            $event->count = 0;
            $event->items = [];

            return;
        }

        $skip = $event->getOffset();
        $fp = fopen($file, 'r');
        $count = $event->getLimit();
        $total = 0;

        $event->items = [];
        while ($line = fgets($fp)) {
            $total++;
            if ($skip-- > 0) {
                continue;
            }
            if ($count > 0) {
                list($status, $customerId, $email, $name) = explode(',', str_replace(PHP_EOL, '', $line), 4);
                $event->items[] = [
                    'status' => $status,
                    'customerId' => $customerId,
                    'email' => $email,
                    'name' => $name,
                ];
            }
            --$count;
        }
        $event->count = $total;
    }

    public static function getSubscribedEvents()
    {
        return [
            'knp_pager.items' => ['items', 1],
        ];
    }
}
