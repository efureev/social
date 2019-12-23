<?php

namespace Sitesoft\Alice\Modules\Pages\Tests\Functional;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\RouteCollection;
use Ramsey\Uuid\Uuid;
use Sitesoft\Alice\Modules\Pages\Models\Page;

class ControllerPageGetTest extends AbstractFunctionalTestCase
{

    public function test_get_a_page(): void
    {
        /** @var RouteCollection $routes */
        $this->assertCount(0, Page::all());

        static::makeTree(null, 3, 2, 1);

        $list = Page::all();

        for ($i = 0; $i < 10; $i++) {
            /** @var Page $expectedPage */
            $expectedPage = $list->random();

            /** @var JsonResponse $response */
            $response = $this
                ->getJson(route('pages', ['page' => $expectedPage->getKey()]))
                ->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'title',
                        'status' => [
                            'key', 'title',
                        ],
                        'params' => [
                            'meta' => [
                                'path',
                            ],
                        ],
                        'author' => [
                            'id', 'name',
                        ],
                        'createdAt',
                    ],
                ]);

            static::assertJson($expectedPage->toJson(), \json_encode($response->json('data'), JSON_THROW_ON_ERROR, 512));
        }
    }


    public function test_not_found_page(): void
    {
        /** @var RouteCollection $routes */
        $this->assertCount(0, Page::all());

        /** @var JsonResponse $response */
        $this
            ->getJson(route('pages', ['page' => Uuid::uuid4()]))
            ->assertNotFound()
            ->assertJsonStructure([
                'message',
            ]);
    }
}
