<?php

namespace Sitesoft\Alice\Modules\Pages\Tests\Functional;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\RouteCollection;
use Sitesoft\Alice\Modules\Pages\Models\Page;
use Sitesoft\Alice\Modules\Pages\Services\PageService;
use Sitesoft\Alice\Modules\Pages\Tests\Models\User;

class ControllerPageUpdateTest extends AbstractFunctionalTestCase
{

    public function test_authenticated_users_can_update_a_page(): void
    {
        /** @var RouteCollection $routes */
        $this->assertCount(0, Page::all());

        $author = factory(User::class)->create();
        $this->actingAs($author);

        /** @var Page $root */
        $root = $this->app->get(PageService::class)->createRootPage('Root Page');

        static::assertTrue($root->isRoot());
        static::assertTrue($root->exists);
        static::assertEquals($root->title, 'Root Page');
        static::assertEquals($root->author_id, $author->getKey());

        /** @var JsonResponse $response */
        $response = $this
            ->postJson(route('pages.update', ['page' => $root->getKey(),]), [
                'title' => 'My first fake title',
                'content' => 'My first fake body',
            ])
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'status' => [
                        'key', 'title',
                    ],
                    'params' => [
                        'seo' => [
                            'title',
                        ],
                    ],
                    'author' => [
                        'id', 'name',
                    ],
                    'createdAt',
                ],
            ]);


        $root->refresh();

        $this->assertEquals($root->getKey(), $response->json('data.id'));
        $this->assertEquals($root->title, $response->json('data.title'));
        $this->assertEquals($root->title, 'My first fake title',);
    }

    public function test_authenticated_users_can_not_update_a_page_wo_valid_fields(): void
    {
        $author = factory(User::class)->create();
        $this->actingAs($author);

        /** @var Page $root */
        $root = $this->app->get(PageService::class)->createRootPage('Root Page');

        static::assertTrue($root->isRoot());
        static::assertTrue($root->exists);
        static::assertEquals($root->title, 'Root Page');
        static::assertEquals($root->author_id, $author->getKey());


        /** @var JsonResponse $response */
        $this
            ->postJson(route('pages.update', ['page' => $root->getKey(),]), [
                'title' => '',
                'content' => 'My first fake body',
            ])
            ->assertJsonValidationErrors([
                'title' => 'The title field is required.',
            ])
            ->assertStatus(422);
    }

}
