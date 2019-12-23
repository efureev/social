<?php

namespace Sitesoft\Alice\Modules\Pages\Tests\Functional;

use Fureev\Trees\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\RouteCollection;
use Sitesoft\Alice\Modules\Pages\Models\Page;
use Sitesoft\Alice\Modules\Pages\Tests\Models\User;

class ControllerPageDeleteTest extends AbstractFunctionalTestCase
{

    public function test_authenticated_users_can_delete_a_page(): void
    {

        $author = factory(User::class)->create();
        $this->actingAs($author);

        /** @var RouteCollection $routes */
//        $this->assertCount(0, Page::all());

        static::makeTree(null, 3, 2, 1);

        /** @var Collection $list */
        $list = Page::all();

        $nodesForRemove = [
            '1.2.1', '3.1.1', '3.1', '3.2.1', '3.2',
        ];
        static::assertCount(15, $list);


        foreach ($nodesForRemove as $nodeForRemove) {
            $node = Page::where(['title' => "child $nodeForRemove"])->first();

            static::assertInstanceOf(Page::class, $node);
            /** @var JsonResponse $response */
            $this
                ->deleteJson(route('pages.delete', ['page' => $node->getKey()]))
                ->assertSuccessful()
                ->assertNoContent();
        }

        /** @var Collection $list */
        $list = Page::all();
        static::assertCount(10, $list);

        $list = Page::whereIn('title', collect($nodesForRemove)->map(static function ($item) {
            return "child $item";
        }))->count();

        static::assertEmpty($list);
    }
}
