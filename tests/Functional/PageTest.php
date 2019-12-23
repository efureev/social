<?php

namespace Sitesoft\Alice\Modules\Pages\Tests\Functional;

use Fureev\Trees\Collection;
use Fureev\Trees\NestedSetTrait;
use Sitesoft\Alice\Modules\Pages\Entity\PageParams;
use Sitesoft\Alice\Modules\Pages\Entity\PageStatus;
use Sitesoft\Alice\Modules\Pages\Models\Page;
use Sitesoft\Alice\Modules\Pages\Tests\Models\User;
use Sitesoft\Hub\Modules\Concerns\CasterAttribute;

class PageTest extends AbstractFunctionalTestCase
{
    public function testMakeInstance(): void
    {
        $page = new Page();
        static::assertInstanceOf(Page::class, $page);

        $page = new Page(['title' => 'Tile']);
        static::assertInstanceOf(Page::class, $page);
        static::assertEquals('Tile', $page->title);
    }

    public function test_a_page_has_an_author_type(): void
    {
        /** @var Page $page */
        $page = factory(Page::class)->create([
            '_setRoot' => true,
        ]);

        $this->assertEquals(User::class, $page->author_type);
        $this->assertInstanceOf(User::class, $page->author);
        $this->assertNotEmpty($page->author->getKey());
    }

    public function test_a_page_belongs_to_an_author(): void
    {
        $author = factory(User::class)->create();

        $author->pages()->create([
            'title' => 'My first fake page',
            'content' => 'The body of this fake page',
            '_setRoot' => true,
        ]);

        $this->assertCount(1, Page::all());
        $this->assertCount(1, $author->pages);

        tap($author->pages()->first(), function ($page) use ($author) {
            $this->assertEquals('My first fake page', $page->title);
            $this->assertEquals('The body of this fake page', $page->content);
            $this->assertTrue($page->author->is($author));
        });
    }

    public function testTraits(): void
    {
        static::assertClassUsesTraits(Page::make(), [
            CasterAttribute::class,
            NestedSetTrait::class,
        ]);
    }

    public function testCreateRootInstance_EmptyParams(): void
    {
        /** @var Page $model */
        $model = factory(Page::class)->create([
            'title' => 'Root node',
            '_setRoot' => true,
        ]);

        static::assertInstanceOf(PageParams::class, $model->params);
        static::assertCount(0, $model->params);

        $model->makeRoot()->save();

        $id = $model->getKey();
        static::assertNotEmpty($id);

        $newModel = Page::find($id);
        static::assertInstanceOf(Page::class, $newModel);
        static::assertInstanceOf(PageParams::class, $newModel->params);
        static::assertInstanceOf(PageStatus::class, $newModel->status);
        static::assertEquals($model->title, $newModel->title);
        static::assertEquals($model->params, $newModel->params);
        static::assertEquals($model->status, $newModel->status);
        static::assertEquals($model->params->toArray(), $newModel->params->toArray());
        static::assertEquals($model->status->toArray(), $newModel->status->toArray());
        static::assertEquals(PageStatus::STATUS_DRAFT, $newModel->status->key());
        static::assertCount(0, $newModel->params);
    }


    public function testCreateRootInstance_WithParams(): void
    {
        foreach ([
                     new PageParams(['seo' => ['title' => 'SEO: root node']]),
                     ['seo' => ['title' => 'SEO: root node']],
                     '{"seo":{"title":"SEO: root node"}}',
                 ] as $params) {

            /** @var Page $model */
            $model = factory(Page::class)->make([
                'title' => 'Root node',
                'status' => PageStatus::STATUS_MODERATED,
                'params' => $params,
            ]);

            static::assertInstanceOf(PageParams::class, $model->params);
            static::assertCount(1, $model->params);

            $model->makeRoot()->save();

            $id = $model->getKey();
            static::assertNotEmpty($id);

            $newModel = Page::find($id);

            static::assertInstanceOf(Page::class, $newModel);
            static::assertInstanceOf(PageParams::class, $newModel->params);
            static::assertInstanceOf(PageStatus::class, $newModel->status);
            static::assertEquals($model->title, $newModel->title);
            static::assertEquals($model->params, $newModel->params);
            static::assertEquals($model->status, $newModel->status);
            static::assertEquals($model->params->toArray(), $newModel->params->toArray());
            static::assertEquals($model->status->toArray(), $newModel->status->toArray());
            static::assertEquals(PageStatus::STATUS_MODERATED, $newModel->status->key());
            static::assertCount(1, $newModel->params);
            static::assertFalse($model->isPublished());
            static::assertFalse($newModel->isPublished());
        }
    }

    public function testChangeStatus(): void
    {
        /** @var Page $oldModel */
        $oldModel = factory(Page::class)->make([
            'title' => 'Root node',
            'params' => ['seo' => ['title' => 'SEO: root node']],
        ]);

        $oldModel->makeRoot()->save();

        static::assertTrue($oldModel->status->isDraft());
        static::assertFalse($oldModel->isPublished());
        $oldModel->is_published = true;
        $oldModel->status = PageStatus::STATUS_REVIEWING;
        $oldModel->save();

        $newModel = Page::find($oldModel->getKey());
        static::assertTrue($newModel->status->isReviewing());
        static::assertTrue($newModel->isPublished());

    }

    /* private static function createRoot(): array
     {
         $models = [];
         for ($i = 1; $i <= 3; $i++) {
             $model = new Page([
                 'title' => "Root node $i",
                 'params' => ['seo' => ['title' => "SEO: root node $i"]],
             ]);
             $model->makeRoot()->save();

             $models[] = $model;
         }
         return $models;
     }*/

    public function testCreateTree(): void
    {
        static::makeTree(null, 3, 10, 2, 1);

        /** @var Collection $items */
        $items = Page::all();
        $expectedQueryCount = count((new Page)->getConnection()->getQueryLog());

        $roots = $items->getRoots();

        static::assertCount(3, $roots);
        static::assertCount(153, $items);

        /**
         * @var int $i
         * @var Page $root
         */
        foreach ($roots as $i => $root) {

            /** @var Page $node */
            foreach ($root->children as $node) {
                static::assertSame(1, $node->getLevel());

                $_root = $node->parent()->first();

                static::assertTrue($_root->isRoot());
                static::assertTrue($root->equalTo($_root));

                foreach ($node->children as $subNode) {
                    static::assertSame(2, $subNode->getLevel());

                    $_parent = $subNode->parent()->first();
                    static::assertFalse($_parent->isRoot());
                    static::assertTrue($node->equalTo($_parent));

                    $__root = $subNode->parent()->first()->parent()->first();
                    static::assertTrue($__root->isRoot());
                    static::assertTrue($root->equalTo($__root));
                }
            }

            static::assertCount(10, $root->children);
            static::assertEquals(10, $root->descendants(1)->count());
            static::assertEquals(30, $root->descendants(2)->count());
            static::assertEquals(50, $root->descendants(3)->count());
            static::assertEquals(50, $root->descendants()->count());
        }

        static::assertCount($expectedQueryCount, $roots->first()->getConnection()->getQueryLog());
    }


    public function testMoveNodeIntoOneTree(): void
    {
        static::makeTree(null, 1, 4, 3);

        $nodesForMove = [
            'child 1.3.1' => ['Root node 1', 'appendTo', 1],
            'child 1.3.2' => ['child 1.4.2', 'prependTo', 3],
            'child 1.1.2' => ['child 1.3.2', 'prependTo', 4],
            'child 1.1.1' => ['child 1.3.2', 'insertBefore', 3],
        ];

        foreach ($nodesForMove as $srcNodeTitle => $dest) {
            [$destNodeTitle, $action, $expectedLevel] = $dest;

            /** @var Page $srcNode */
            $srcNode = Page::whereTitle($srcNodeTitle)->first();
            static::assertInstanceOf(Page::class, $srcNode);

            /** @var Page $destNode */
            $destNode = Page::whereTitle($destNodeTitle)->first();
            static::assertInstanceOf(Page::class, $destNode);

            $srcNode->$action($destNode)->save();

            /** @var Page $srcNodeCheck */
            $srcNodeCheck = Page::whereTitle($srcNodeTitle)->first();
            static::assertInstanceOf(Page::class, $srcNodeCheck);
            $srcNode->refresh();
            static::assertTrue($srcNodeCheck->equalTo($srcNode));
            static::assertEquals($expectedLevel, $srcNodeCheck->getLevel());

            /** @var Page $destNodeCheck */
            $destNodeCheck = Page::whereTitle($destNodeTitle)->first();

            if (in_array($action, ['prependTo', 'appendTo'])) {
                static::assertTrue($destNodeCheck->equalTo($srcNodeCheck->parent));
                static::assertEquals($destNodeCheck->getKey(), $srcNodeCheck->parent->getKey());
            }
            if ($action === 'insertBefore') {
                static::assertTrue($destNodeCheck->equalTo($srcNodeCheck->nextSibling()->first()));
                static::assertEquals($destNodeCheck->getKey(), $srcNodeCheck->nextSibling()->first()->getKey());
            }
        }
    }


    public function testMoveNodeIntoManyTree(): void
    {
        static::makeTree(null, 2, 4, 3);

        /** @var Page $root1 */
        $root1 = Page::root()->byTree(1)->first();
        /** @var Page $root2 */
        $root2 = Page::root()->byTree(2)->first();
        static::assertEquals(16, $root1->descendants()->count());
        static::assertEquals(16, $root2->descendants()->count());
        static::assertEquals(4, $root1->children()->count());
        static::assertEquals(4, $root2->children()->count());


        $nodesForMove = [
            'child 2.3.1' => ['Root node 1', 'appendTo', 1],
            'child 2.3.2' => ['child 1.4.2', 'prependTo', 3],
            'child 2.1.2' => ['child 1.3.2', 'prependTo', 3],
            'child 2.1.1' => ['child 1.3.2', 'insertBefore', 2],
        ];

        foreach ($nodesForMove as $srcNodeTitle => $dest) {
            [$destNodeTitle, $action, $expectedLevel] = $dest;

            /** @var Page $srcNode */
            $srcNode = Page::whereTitle($srcNodeTitle)->first();
            static::assertInstanceOf(Page::class, $srcNode);
            static::assertEquals(2, $srcNode->getTree());

            /** @var Page $destNode */
            $destNode = Page::whereTitle($destNodeTitle)->first();
            static::assertInstanceOf(Page::class, $destNode);

            $srcNode->$action($destNode)->save();

            /** @var Page $srcNodeCheck */
            $srcNodeCheck = Page::whereTitle($srcNodeTitle)->first();
            static::assertInstanceOf(Page::class, $destNode);

            $srcNode->refresh();
            static::assertTrue($srcNodeCheck->equalTo($srcNode));
            static::assertEquals($expectedLevel, $srcNodeCheck->getLevel());
            static::assertEquals(1, $srcNodeCheck->getTree());

            /** @var Page $destNodeCheck */
            $destNodeCheck = Page::whereTitle($destNodeTitle)->first();

            if (in_array($action, ['prependTo', 'appendTo'])) {
                static::assertTrue($destNodeCheck->equalTo($srcNodeCheck->parent));
                static::assertEquals($destNodeCheck->getKey(), $srcNodeCheck->parent->getKey());
            }
            if ($action === 'insertBefore') {
                static::assertTrue($destNodeCheck->equalTo($srcNodeCheck->nextSibling()->first()));
                static::assertEquals($destNodeCheck->getKey(), $srcNodeCheck->nextSibling()->first()->getKey());
            }
        }

        /** @var Page $root1 */
        $root1 = Page::root()->byTree(1)->first();

        static::assertInstanceOf(Page::class, $root1);
        static::assertCount(5, $root1->children);
        static::assertEquals(20, $root1->descendants()->count());

        /** @var Page $root2 */
        $root2 = Page::root()->byTree(2)->first();

        static::assertInstanceOf(Page::class, $root2);
        static::assertCount(4, $root2->children);
        static::assertEquals(12, $root2->descendants()->count());
    }

    public function testRemoveNodesManyTree(): void
    {
        static::makeTree(null, 3, 3, 6);

        /** @var Page $root1 */
        $root1 = Page::root()->byTree(1)->first();
        /** @var Page $root2 */
        $root2 = Page::root()->byTree(2)->first();
        /** @var Page $root3 */
        $root3 = Page::root()->byTree(3)->first();

        static::assertEquals(21, $root1->descendants()->count());
        static::assertEquals(21, $root2->descendants()->count());
        static::assertEquals(21, $root3->descendants()->count());

        static::assertEquals(3, $root1->children()->count());
        static::assertEquals(3, $root2->children()->count());
        static::assertEquals(3, $root3->children()->count());

        $nodesForReMove = [
            'child 1.3.1',
            'child 1.3.2',
            'child 1.3',
            'child 1.2',
        ];

        foreach ($nodesForReMove as $nodeTitle) {
            /** @var Page $node */
            $node = Page::whereTitle($nodeTitle)->first();
            static::assertInstanceOf(Page::class, $node);

            /** @var Page $parent */
            $parent = $node->parent;
            $parentChildrenCount = $parent->children()->count();

            $children = $node->children;
            $childrenCount = $children->count();

            static::assertTrue($node->delete());
            $parent->refresh();
            $this->assertEquals($parentChildrenCount - 1 + $childrenCount, $parent->children()->count());

            /** @var Page $child */
            foreach ($children as $child) {
                $child->refresh();
                static::assertTrue($parent->equalTo($child->parent));
                static::assertEquals($parent->getKey(), $child->parent->getKey());
            }
        }

        $root1->refresh();
        $root2->refresh();
        $root3->refresh();

        static::assertEquals(17, $root1->descendants()->count());
        static::assertEquals(21, $root2->descendants()->count());
        static::assertEquals(21, $root3->descendants()->count());

        static::assertEquals(11, $root1->children()->count());
        static::assertEquals(3, $root2->children()->count());
        static::assertEquals(3, $root3->children()->count());
    }

}
