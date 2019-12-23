<?php

namespace Sitesoft\Alice\Modules\Pages\Tests\Functional;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\RouteCollection;
use Sitesoft\Alice\Modules\Pages\Models\Page;
use Sitesoft\Alice\Modules\Pages\Services\PageService;
use Sitesoft\Alice\Modules\Pages\Tests\Models\User;

class ControllerPageCreateTest extends AbstractFunctionalTestCase
{

    public function test_authenticated_users_can_create_a_page(): void
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
            ->postJson(route('pages.create', ['parentPage' => $root->getKey(),]), [
                'title' => 'My first fake title',
                'content' => 'My first fake body',
            ])
            ->assertCreated()
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

        /** @var Page $page */
        $page = $this->app->get(PageService::class)->getPage($response->json('data.id'));

        $root->refresh();
        $this->assertTrue($page->parent()->first()->equalTo($root));
    }


    public function test_authenticated_users_can_not_create_a_page_wo_parent(): void
    {
        $author = factory(User::class)->create();

        /** @var JsonResponse $response */
        $response = $this->actingAs($author)
            ->postJson(route('pages.create'), [
                'title' => 'My first fake title',
                'content' => 'My first fake body',
            ])
            ->assertStatus(500);

        static::assertEquals($response->exception->getMessage(), 'Page must has parent');
    }

    public function test_authenticated_users_can_not_create_a_page_wo_valid_fields(): void
    {
        $author = factory(User::class)->create();

        /** @var JsonResponse $response */
        $this->actingAs($author)
            ->postJson(route('pages.create'), [
                'content' => 'My first fake body',
            ])
            ->assertJsonValidationErrors([
                'title' => 'The title field is required.',
            ]);
    }

}
