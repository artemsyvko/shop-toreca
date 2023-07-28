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

namespace Plugin\SiteKit42\Service;


use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Member;
use Eccube\Repository\BaseInfoRepository;
use Google_Service_SiteVerification;
use Google_Service_Webmasters;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SiteKitClientFactory
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * SiteKitClientFactory constructor.
     */
    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    public static function createClient(SiteKitClientFactory $factory, Router $router, TokenStorageInterface $tokenStorage, BaseInfoRepository $baseInfoRepository)
    {
        $BaseInfo = $baseInfoRepository->get();
        $client = new Google_Site_Kit_Proxy_Client();
        $client->setClientId($BaseInfo->getSiteKitSiteId());
        $client->setClientSecret($BaseInfo->getSiteKitSiteSecret());
        $client->setScopes([
            'https://www.googleapis.com/auth/userinfo.profile',
            Google_Service_SiteVerification::SITEVERIFICATION,
            Google_Service_Webmasters::WEBMASTERS,
        ]);
        $client->setAccessType('offline');
        $client->setRedirectUri(
            $router->generate('site_kit42_admin_config', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        $Token = $tokenStorage->getToken();
        if ($Token !== null) {
            /** @var Member $Member */
            $Member = $Token->getUser();
            if ($Member instanceof Member && !is_null($Member->getIdToken())) {
                $client->setAccessToken($Member->getIdToken()->getIdToken());
                $client->setTokenCallback([$factory, 'updateAccessToken']);
            }
        }

        return $client;
    }

    public function updateAccessToken($cacheKey, $accessToken)
    {
        /** @var Member $Member */
        $Member = $this->tokenStorage->getToken()->getUser();
        $IdToken = $Member->getIdToken();
        $json = json_decode($IdToken->getIdToken(), true);
        $json['access_token'] = $accessToken;
        $IdToken->setIdToken(json_encode($json));

        $this->entityManager->persist($IdToken);
        $this->entityManager->flush();
    }

}
