<?php

namespace Fureev\Social\Tests\Functional;

use Fureev\Socialite\SocialiteManager;
use Fureev\Socialite\Two\VkProvider;

class ServiceTest extends AbstractFunctionalTestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->addDrivers();

    }

    public function testMakeInstance(): void
    {
        static::assertInstanceOf(SocialiteManager::class, $this->app->get(SocialiteManager::class));
        static::assertInstanceOf(SocialiteManager::class, $this->app->get('social'));
    }


    public function testCommonConfig(): void
    {
        $config = $this->app['config']['social'];
        foreach ([
                     'userClass',
                     'redirectOnAuth',
                     'routes',
                     'onSuccess',
                     'drivers',
                 ] as $key) {
            static::assertArrayHasKey($key, $config);
        }
    }

    public function testDrivers(): void
    {
        /** @var SocialiteManager $service */
        $service = $this->app->get('social');

        $providers = $service->getProviders();
        $configDrivers = static::drivers();


        static::assertCount(count(static::drivers()), $providers);

        /**
         * @var string $driverName
         * @var VkProvider $provider
         */
        foreach ($providers as $driverName => $provider) {

            $driverExpected = $configDrivers[$driverName]['expected'];

            static::assertEquals($driverExpected['name'], $provider->getName());
            static::assertEquals($driverExpected['enabled'] ?? true, $provider->enabled);
            static::assertEquals($driverExpected['label'] ?? $provider->getName(), $provider->getLabel());
            static::assertEquals($driverExpected['redirectUrl'] ?? $provider->getName(), $provider->getRedirectUrl());
        }
//        foreach (static::drivers() as $driverName => $driverConfig) {
//            $config = $driverConfig['config'];
//            $expected = $driverConfig['expected'];
//
//            static::assertCount(count(static::drivers()), $providers);
//        }
//        dd($providers);
//        $config = $this->app['config']['social'];
//        dd($config);

    }

    /* public function testCreateRootPage(): void
     {
         $user = factory(User::class)->create();


         for ($i = 1; $i < 5; $i++) {
             $title = "Root Page From Test: $i";
             $this->be($author);
             $page = $this->service->createRootPage($title);
             static::assertNotNull($page);
             static::assertEquals($page->title, $title);
             static::assertTrue($page->isRoot());
             static::assertTrue($page->exists);
             static::assertNotNull($page->getKey());
             static::assertTrue($page->status->isDraft());
             static::assertInstanceOf(PageParams::class, $page->params);
             static::assertEquals($page->params->get('seo.title'), $title);
             static::assertInstanceOf(Carbon::class, $page->created_at);
             static::assertTrue($page->created_at->addSeconds(5) > now());
         }

     }*/


    private function addDrivers(): void
    {
        foreach (static::drivers() as $driver => $driverData) {
            config()->set("social.drivers.$driver", $driverData['config']);
        }
    }

    private static function drivers(): array
    {
        return [
            'vk' => [
                'config' => [
                    'label' => 'VK',
                    'clientId' => 1234,
                    'clientSecret' => 'secret',
                ],
                'expected' => [
                    'name' => 'vk',
                    'label' => 'VK',
                    'redirect' => '/redirect/vk',
                    'callback' => '/callback/vk',
                    'redirectUrl' => 'http://localhost/auth/redirect/vk',
                    'callbackUrl' => 'http://localhost/auth/callback/vk',
                ],
            ],
        ];
    }

}
