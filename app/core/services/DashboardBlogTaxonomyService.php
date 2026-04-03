<?php

namespace App\Core\services;


use App\Core\database\Database;
use App\Core\database\QueryBuilder;
use App\Models\BlogCategory;
use App\Models\BlogTag;
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
 * Service layer for dashboard blog category and tag management.
 */
class DashboardBlogTaxonomyService
{
    public function getCategoryList(): array
    {
        $categoriesData = Database::select(
            "SELECT c.*, p.name as parent_name,
                    l.code as language_code, l.name as language_name, l.flag as language_flag
             FROM blog_categories c
             LEFT JOIN blog_categories p ON c.parent_id = p.id
             LEFT JOIN languages l ON c.language_id = l.id
             ORDER BY c.sort_order ASC, c.name ASC"
        );

        $categories = [];
        foreach ($categoriesData as $categoryData) {
            $category = new BlogCategory();
            $category->fill($categoryData);
            $category->exists = true;

            $categories[] = [
                ...$categoryData,
                'parent_name' => $categoryData['parent_name'] ?? null,
                'language' => $categoryData['language_code'] ? [
                    'code' => $categoryData['language_code'],
                    'name' => $categoryData['language_name'],
                    'flag' => $categoryData['language_flag'],
                ] : null,
                'depth' => $category->getDepth(),
            ];
        }

        return $categories;
    }

    public function buildCategoryFormData(?int $excludeCategoryId = null): array
    {
        return [
            'parentCategories' => $this->buildParentCategoryOptions($excludeCategoryId),
            ...$this->buildLanguageOptions(),
        ];
    }

    public function buildCategoryEditFormData(BlogCategory $category): array
    {
        return [
            'category' => $category->toArray(),
            ...$this->buildCategoryFormData((int) $category->id),
        ];
    }

    public function buildCategoryPreviewData(BlogCategory $category, string $lang): array
    {
        $categoryArray = $category->toArray();
        $postsArray = is_array($category->posts()) ? $category->posts() : [];

        foreach ($postsArray as &$postArray) {
            $postSlug = $postArray['slug'] ?? '';
            $categorySlug = $category->slug ?? '';
            if ($postSlug === '') {
                continue;
            }

            $postArray['url'] = $categorySlug !== ''
                ? "/{$lang}/{$categorySlug}/{$postSlug}"
                : "/{$lang}/{$postSlug}";
        }
        unset($postArray);

        if ($category->language_id && class_exists('Language')) {
            $language = Language::find($category->language_id);
            if ($language) {
                $categoryArray['language'] = [
                    'id' => $language->id,
                    'code' => $language->code,
                    'name' => $language->name,
                    'native_name' => $language->native_name,
                ];
            }
        }

        if ($category->parent_id) {
            $parent = BlogCategory::find($category->parent_id);
            if ($parent) {
                $categoryArray['parent'] = $parent->toArray();
            }
        }

        return [
            'category' => $categoryArray,
            'posts' => $postsArray,
            'page' => [
                'title' => $category->name,
                'meta_title' => $category->meta_title ?? $category->name,
                'meta_description' => $category->meta_description ?? $category->description,
            ],
            'displayOptions' => [
                'style' => 'list',
                'posts_per_page' => 10,
                'show_excerpt' => true,
                'show_featured_image' => true,
                'grid_columns' => 3,
            ],
            'isPreview' => true,
        ];
    }

    public function buildTagFormData(): array
    {
        return $this->buildLanguageOptions();
    }

    public function buildTagEditFormData(BlogTag $tag): array
    {
        return [
            'tag' => $tag->toArray(),
            ...$this->buildTagFormData(),
        ];
    }

    public function normalizeSlug(string $slug, string $fallbackValue): string
    {
        $slug = trim($slug);
        return $slug !== '' ? str_slug($slug) : str_slug($fallbackValue);
    }

    public function categorySlugExists(string $slug, ?int $excludeId = null): bool
    {
        return $this->slugExists(BlogCategory::query(), $slug, $excludeId);
    }

    public function tagSlugExists(string $slug, ?int $excludeId = null): bool
    {
        return $this->slugExists(BlogTag::query(), $slug, $excludeId);
    }

    public function validateCategoryInput(
        array|bool $validation,
        array $input,
        ?BlogCategory $category = null
    ): array|bool {
        $errors = is_array($validation) ? $validation : [];
        $slug = $this->normalizeSlug(
            (string) ($input['slug'] ?? ''),
            (string) ($input['name'] ?? '')
        );

        if ($slug !== '' && $this->categorySlugExists($slug, $category ? (int) $category->id : null)) {
            $errors['slug'] = array_merge(
                $errors['slug'] ?? [],
                ['A category with this slug already exists']
            );
        }

        if ($category) {
            $parentId = !empty($input['parent_id']) ? (int) $input['parent_id'] : null;
            $parentErrors = $this->validateCategoryParent($category, $parentId);
            foreach ($parentErrors as $field => $fieldErrors) {
                $errors[$field] = array_merge($errors[$field] ?? [], $fieldErrors);
            }
        }

        return empty($errors) ? true : $errors;
    }

    public function validateTagInput(array|bool $validation, array $input, ?int $excludeId = null): array|bool
    {
        $errors = is_array($validation) ? $validation : [];
        $slug = $this->normalizeSlug(
            (string) ($input['slug'] ?? ''),
            (string) ($input['name'] ?? '')
        );

        if ($slug !== '' && $this->tagSlugExists($slug, $excludeId)) {
            $errors['slug'] = array_merge(
                $errors['slug'] ?? [],
                ['A tag with this slug already exists']
            );
        }

        return empty($errors) ? true : $errors;
    }

    public function validateCategoryParent(BlogCategory $category, ?int $parentId): array
    {
        if ($parentId === null) {
            return [];
        }

        if ((int) $category->id === $parentId) {
            return ['parent_id' => ['A category cannot be its own parent']];
        }

        $parent = BlogCategory::find($parentId);
        if (!$parent) {
            return [];
        }

        foreach ($category->getAllChildren() as $descendant) {
            $descendantId = is_array($descendant)
                ? (int) ($descendant['id'] ?? 0)
                : (int) ($descendant->id ?? 0);

            if ($descendantId === $parentId) {
                return ['parent_id' => ['Cannot set parent to a descendant category']];
            }
        }

        return [];
    }

    public function applyCategoryData(BlogCategory $category, array $input, ?string $slug = null): void
    {
        $category->name = $input['name'] ?? '';
        $category->slug = $slug ?? $this->normalizeSlug((string) ($input['slug'] ?? ''), (string) ($input['name'] ?? ''));
        $category->description = $input['description'] ?? '';
        $category->parent_id = !empty($input['parent_id']) ? (int) $input['parent_id'] : null;
        $category->image = $input['image'] ?? '';
        $category->meta_title = $input['meta_title'] ?? '';
        $category->meta_description = $input['meta_description'] ?? '';
        $category->sort_order = (int) ($input['sort_order'] ?? 0);

        $languageId = $input['language_id'] ?? null;
        $category->language_id = ($languageId !== null && $languageId !== '') ? (int) $languageId : null;
    }

    public function saveCategory(BlogCategory $category, array $input): BlogCategory
    {
        $slug = $this->normalizeSlug(
            (string) ($input['slug'] ?? ''),
            (string) ($input['name'] ?? '')
        );
        $this->applyCategoryData($category, $input, $slug);
        $category->save();

        return $category;
    }

    public function validateCategoryDeletion(BlogCategory $category): array
    {
        if (!empty($category->children())) {
            return ['general' => ['Cannot delete category with child categories. Please delete or move children first.']];
        }

        return [];
    }

    public function deleteCategoryRelations(int $categoryId): void
    {
        Database::execute("DELETE FROM blog_post_categories WHERE blog_category_id = ?", [$categoryId]);
    }

    public function deleteCategory(BlogCategory $category): void
    {
        $errors = $this->validateCategoryDeletion($category);
        if (!empty($errors)) {
            throw new InvalidArgumentException(
                $errors['general'][0] ?? 'Cannot delete category'
            );
        }

        Database::transaction(function () use ($category): void {
            $this->deleteCategoryRelations((int) $category->id);
            $category->delete();
        });
    }

    public function getTagList(): array
    {
        $tagsData = Database::select(
            "SELECT t.*, COUNT(bpt.blog_post_id) as post_count,
                    l.code as language_code, l.name as language_name, l.flag as language_flag
             FROM blog_tags t
             LEFT JOIN blog_post_tags bpt ON t.id = bpt.blog_tag_id
             LEFT JOIN languages l ON t.language_id = l.id
             GROUP BY t.id
             ORDER BY t.name ASC"
        );

        $tags = [];
        foreach ($tagsData as $tagData) {
            $tags[] = [
                ...$tagData,
                'post_count' => (int) ($tagData['post_count'] ?? 0),
                'language' => $tagData['language_code'] ? [
                    'code' => $tagData['language_code'],
                    'name' => $tagData['language_name'],
                    'flag' => $tagData['language_flag'],
                ] : null,
            ];
        }

        return $tags;
    }

    public function applyTagData(BlogTag $tag, array $input, ?string $slug = null): void
    {
        $tag->name = $input['name'] ?? '';
        $tag->slug = $slug ?? $this->normalizeSlug((string) ($input['slug'] ?? ''), (string) ($input['name'] ?? ''));
        $tag->description = $input['description'] ?? '';

        $languageId = $input['language_id'] ?? null;
        $tag->language_id = ($languageId !== null && $languageId !== '') ? (int) $languageId : null;
    }

    public function saveTag(BlogTag $tag, array $input): BlogTag
    {
        $slug = $this->normalizeSlug(
            (string) ($input['slug'] ?? ''),
            (string) ($input['name'] ?? '')
        );
        $this->applyTagData($tag, $input, $slug);
        $tag->save();

        return $tag;
    }

    public function deleteTagRelations(int $tagId): void
    {
        Database::execute("DELETE FROM blog_post_tags WHERE blog_tag_id = ?", [$tagId]);
    }

    public function deleteTag(BlogTag $tag): void
    {
        Database::transaction(function () use ($tag): void {
            $this->deleteTagRelations((int) $tag->id);
            $tag->delete();
        });
    }

    private function buildParentCategoryOptions(?int $excludeCategoryId = null): array
    {
        $options = ['' => '-- No Parent (Root Category) --'];

        foreach (BlogCategory::all() as $category) {
            if ($excludeCategoryId !== null && (int) $category->id === $excludeCategoryId) {
                continue;
            }

            $options[$category->id] = $category->name;
        }

        return $options;
    }

    private function buildLanguageOptions(): array
    {
        $languages = [];
        $languagesData = [];

        if (!class_exists('Language')) {
            return [
                'languages' => $languages,
                'languagesData' => $languagesData,
            ];
        }

        $flagCodes = [
            'sr' => 'rs', 'hr' => 'hr', 'bg' => 'bg', 'ro' => 'ro', 'sl' => 'si', 'el' => 'gr', 'mk' => 'mk',
            'en' => 'gb', 'de' => 'de', 'fr' => 'fr', 'es' => 'es', 'it' => 'it', 'pt' => 'pt', 'nl' => 'nl',
            'pl' => 'pl', 'ru' => 'ru', 'uk' => 'ua', 'cs' => 'cz', 'sk' => 'sk', 'hu' => 'hu',
            'sv' => 'se', 'da' => 'dk', 'no' => 'no', 'fi' => 'fi',
            'lt' => 'lt', 'et' => 'ee', 'lv' => 'lv',
            'zh' => 'cn', 'ja' => 'jp', 'ko' => 'kr', 'tr' => 'tr',
        ];

        $languages[''] = '-- Select Language --';
        foreach (Language::getActive() as $language) {
            $code = strtolower($language->code ?? '');
            $languages[$language->id] = $language->name . ' (' . $language->native_name . ')';
            $languagesData[$language->id] = [
                'code' => $language->code,
                'name' => $language->name,
                'native_name' => $language->native_name,
                'flag_code' => $flagCodes[$code] ?? 'xx',
            ];
        }

        return [
            'languages' => $languages,
            'languagesData' => $languagesData,
        ];
    }

    private function slugExists(QueryBuilder $query, string $slug, ?int $excludeId = null): bool
    {
        $query->where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}


if (!\class_exists('DashboardBlogTaxonomyService', false) && !\interface_exists('DashboardBlogTaxonomyService', false) && !\trait_exists('DashboardBlogTaxonomyService', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardBlogTaxonomyService', 'DashboardBlogTaxonomyService');
}
