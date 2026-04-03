<?php

namespace App\Core\services;


use App\Core\database\Database;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Language;use BadMethodCallException;
use Closure;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Error;
use ErrorException;
use Exception;
use FilesystemIterator;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;
use Throwable;
use stdClass;

/**
 * Service layer for dashboard blog post management.
 */
class DashboardBlogPostService
{
    private DashboardBlogTaxonomyService $blogTaxonomyService;

    public function __construct(?DashboardBlogTaxonomyService $blogTaxonomyService = null)
    {
        $this->blogTaxonomyService = $blogTaxonomyService ?? new DashboardBlogTaxonomyService();
    }

    public function getPostList(): array
    {
        $postsData = Database::select(
            "SELECT bp.*, u.username as author_name,
                    l.code as language_code, l.name as language_name, l.flag as language_flag
             FROM blog_posts bp
             LEFT JOIN users u ON bp.author_id = u.id
             LEFT JOIN languages l ON bp.language_id = l.id
             ORDER BY bp.created_at DESC"
        );

        $posts = [];
        foreach ($postsData as $postData) {
            $posts[] = [
                ...$postData,
                'author_name' => $postData['author_name'] ?? 'Unknown',
                'language' => $postData['language_code'] ? [
                    'code' => $postData['language_code'],
                    'name' => $postData['language_name'],
                    'flag' => $postData['language_flag'],
                ] : null,
            ];
        }

        return $posts;
    }

    public function buildCreateFormData(): array
    {
        return [
            'categories' => $this->buildCategoryOptions(),
            ...$this->blogTaxonomyService->buildTagFormData(),
        ];
    }

    public function buildEditFormData(BlogPost $post): array
    {
        $categories = $this->buildCategoryOptions($post->language_id ? (int) $post->language_id : null);

        $selectedCategoryIds = array_map(
            static fn(array $category): ?int => isset($category['id']) ? (int) $category['id'] : null,
            $post->categories()
        );

        return [
            'post' => $post->toArray(),
            'categories' => $categories,
            'selectedCategoryIds' => array_values(array_filter($selectedCategoryIds)),
            ...$this->blogTaxonomyService->buildTagFormData(),
        ];
    }

    public function buildPreviewData(BlogPost $post, string $lang): array
    {
        $postArray = $post->toArray();
        $postArray['categories'] = is_array($post->categories()) ? $post->categories() : [];
        $postArray['tags'] = is_array($post->tags()) ? $post->tags() : [];

        $author = $post->author();
        $postArray['author'] = $author ? $author->toArray() : null;

        if ($post->language_id && class_exists('Language')) {
            $language = Language::find($post->language_id);
            if ($language) {
                $postArray['language'] = [
                    'id' => $language->id,
                    'code' => $language->code,
                    'name' => $language->name,
                    'native_name' => $language->native_name,
                ];
            }
        }

        return [
            'blogPost' => $postArray,
            'page' => [
                'title' => $post->title,
                'meta_title' => $post->meta_title ?? $post->title,
                'meta_description' => $post->meta_description ?? $post->excerpt,
                'meta_keywords' => $post->meta_keywords ?? '',
            ],
            'isPreview' => true,
            'lang' => $lang,
        ];
    }

    public function normalizeSlug(string $slug, string $fallbackTitle): string
    {
        return $this->blogTaxonomyService->normalizeSlug($slug, $fallbackTitle);
    }

    public function postSlugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = BlogPost::query()->where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function validatePostInput(array|bool $validation, array $input, ?int $excludeId = null): array|bool
    {
        $errors = is_array($validation) ? $validation : [];
        $slug = $this->normalizeSlug(
            (string) ($input['slug'] ?? ''),
            (string) ($input['title'] ?? '')
        );

        if ($slug !== '' && $this->postSlugExists($slug, $excludeId)) {
            $errors['slug'] = array_merge(
                $errors['slug'] ?? [],
                ['A post with this slug already exists']
            );
        }

        return empty($errors) ? true : $errors;
    }

    public function applyPostData(BlogPost $post, array $input, int $authorId, ?string $slug = null): void
    {
        $post->title = $input['title'] ?? '';
        $post->slug = $slug ?? $this->normalizeSlug((string) ($input['slug'] ?? ''), (string) ($input['title'] ?? ''));
        $post->excerpt = $input['excerpt'] ?? '';
        $post->content = $input['content'] ?? '';
        $post->featured_image = $input['featured_image'] ?? '';
        $post->status = $input['status'] ?? 'draft';

        if (!$post->author_id) {
            $post->author_id = $authorId;
        }

        if ($post->status === 'published') {
            $publishedAt = $input['published_at'] ?? null;
            if ($publishedAt) {
                $post->published_at = is_numeric($publishedAt) ? (int) $publishedAt : strtotime((string) $publishedAt);
            } elseif (!$post->published_at) {
                $post->published_at = time();
            }
        } elseif (!$post->exists) {
            $post->published_at = null;
        }

        $post->meta_title = $input['meta_title'] ?? '';
        $post->meta_description = $input['meta_description'] ?? '';
        $post->meta_keywords = $input['meta_keywords'] ?? '';
        $post->views = (int) ($post->views ?? 0);

        $languageId = $input['language_id'] ?? null;
        $post->language_id = (!empty($languageId) && $languageId !== '0') ? (int) $languageId : null;
    }

    public function syncPostCategories(BlogPost $post, mixed $categoryIds): void
    {
        if (is_array($categoryIds)) {
            $post->syncCategories($categoryIds);
        }
    }

    public function deletePostRelations(int $postId): void
    {
        Database::transaction(static function () use ($postId): void {
            Database::execute("DELETE FROM blog_post_categories WHERE blog_post_id = ?", [$postId]);
            Database::execute("DELETE FROM blog_post_tags WHERE blog_post_id = ?", [$postId]);
        });
    }

    public function savePost(BlogPost $post, array $input, int $authorId): BlogPost
    {
        $slug = $this->normalizeSlug(
            (string) ($input['slug'] ?? ''),
            (string) ($input['title'] ?? '')
        );

        $this->applyPostData($post, $input, $authorId, $slug);
        $post->save();
        $this->syncPostCategories($post, $input['categories'] ?? []);

        return $post;
    }

    public function deletePost(BlogPost $post): void
    {
        Database::transaction(function () use ($post): void {
            $this->deletePostRelations((int) $post->id);
            $post->delete();
        });
    }

    private function buildCategoryOptions(?int $languageId = null): array
    {
        $query = BlogCategory::query();
        if ($languageId !== null) {
            $query->where('language_id', $languageId);
        }

        $categories = [];
        foreach ($query->get() as $category) {
            $categoryId = $category['id'] ?? null;
            if ($categoryId) {
                $categories[$categoryId] = $category['name'] ?? '';
            }
        }

        return $categories;
    }
}


if (!\class_exists('DashboardBlogPostService', false) && !\interface_exists('DashboardBlogPostService', false) && !\trait_exists('DashboardBlogPostService', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardBlogPostService', 'DashboardBlogPostService');
}
