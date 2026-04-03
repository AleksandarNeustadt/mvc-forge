<?php

namespace Tests\PhpUnit;

use App\Core\services\DashboardPageService;
use App\Models\Page;
use PHPUnit\Framework\TestCase;

final class DashboardPageServiceTest extends TestCase
{
    private DashboardPageService $service;

    protected function setUp(): void
    {
        $this->service = new DashboardPageService();
    }

    public function testNormalizeRouteUsesSlugFallbackAndContactOverride(): void
    {
        self::assertSame('/o-autoru', $this->service->normalizeRoute('', 'o-autoru'));
        self::assertSame('/custom-path', $this->service->normalizeRoute('custom-path', 'o-autoru'));
        self::assertSame('/contact', $this->service->normalizeRoute('/ignored', 'kontakt', 'contact'));
    }

    public function testNormalizeOptionalIdConvertsEmptyValuesToNull(): void
    {
        self::assertNull($this->service->normalizeOptionalId(null));
        self::assertNull($this->service->normalizeOptionalId(''));
        self::assertNull($this->service->normalizeOptionalId('0'));
        self::assertNull($this->service->normalizeOptionalId(0));
        self::assertSame(12, $this->service->normalizeOptionalId('12'));
    }

    public function testMapPageTypeForStorageTranslatesBlogPageTypes(): void
    {
        self::assertSame('blog_post', $this->service->mapPageTypeForStorage('single_post'));
        self::assertSame('blog_category', $this->service->mapPageTypeForStorage('category'));
        self::assertSame('blog_tag', $this->service->mapPageTypeForStorage('tag'));
        self::assertSame('blog_list', $this->service->mapPageTypeForStorage('list'));
        self::assertSame('custom', $this->service->mapPageTypeForStorage('custom'));
    }

    public function testBuildDisplayOptionsSupportsHomepageAndBlogListingModes(): void
    {
        self::assertSame([
            'enable_blog_slider' => true,
            'blog_slider_posts' => [5, 9],
            'enable_login_form' => false,
            'enable_contact_form' => true,
        ], $this->service->buildDisplayOptions('homepage', 'custom', [
            'homepage_enable_blog_slider' => '1',
            'homepage_blog_slider_posts' => ['5', '', '9', 'not-numeric'],
            'homepage_enable_contact_form' => 1,
        ]));

        self::assertSame([
            'style' => 'grid',
            'grid_columns' => 4,
            'posts_per_page' => 12,
            'show_excerpt' => true,
            'show_featured_image' => false,
        ], $this->service->buildDisplayOptions('blog', 'category', [
            'display_style' => 'grid',
            'grid_columns' => '4',
            'posts_per_page' => '12',
            'show_excerpt' => '1',
            'show_featured_image' => '',
        ]));

        self::assertNull($this->service->buildDisplayOptions('blog', 'single_post', []));
    }

    public function testApplyBlogAssociationsAssignsOnlyExpectedRelation(): void
    {
        $page = new Page([
            'blog_post_id' => 1,
            'blog_category_id' => 2,
            'blog_tag_id' => 3,
        ]);

        $this->service->applyBlogAssociations($page, 'category', [
            'blog_category_id' => '15',
        ]);

        self::assertNull($page->blog_post_id);
        self::assertSame(15, $page->blog_category_id);
        self::assertNull($page->blog_tag_id);
    }

    public function testPreparePageForEditMapsStoredBlogTypeAndDisplayOptions(): void
    {
        $page = new Page([
            'application' => 'blog',
            'page_type' => 'blog_category',
            'display_options' => json_encode([
                'style' => 'grid',
                'grid_columns' => 2,
                'posts_per_page' => 8,
                'show_excerpt' => true,
                'show_featured_image' => false,
            ]),
        ]);

        $prepared = $this->service->preparePageForEdit($page);

        self::assertSame('category', $prepared['page_type']);
        self::assertSame('grid', $prepared['display_style']);
        self::assertSame(2, $prepared['grid_columns']);
        self::assertSame(8, $prepared['posts_per_page']);
        self::assertTrue($prepared['show_excerpt']);
        self::assertFalse($prepared['show_featured_image']);
    }
}
