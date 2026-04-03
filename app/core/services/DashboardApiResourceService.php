<?php

namespace App\Core\services;


use App\Core\http\Request;
use App\Core\security\Security;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\ContactMessage;
use App\Models\Continent;
use App\Models\Language;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;use BadMethodCallException;
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
 * Encapsulates generic Dashboard API resource metadata, payload preparation,
 * and resource-specific custom actions.
 */
class DashboardApiResourceService
{
    private const APP_MODELS = [
        'users' => User::class,
        'blog' => BlogPost::class,
        'blog-posts' => BlogPost::class,
        'blog-categories' => BlogCategory::class,
        'blog-tags' => BlogTag::class,
        'pages' => Page::class,
        'navigation-menus' => NavigationMenu::class,
        'contact-messages' => ContactMessage::class,
        'roles' => Role::class,
        'permissions' => Permission::class,
        'languages' => Language::class,
        'continents' => Continent::class,
        'regions' => Region::class,
    ];

    private const APP_ACTIONS = [
        'users' => ['ban', 'unban', 'approve'],
    ];

    private const VALIDATION_RULES = [
        'users' => [
            'create' => [
                'username' => 'required|minLength:3|maxLength:30',
                'email' => 'required|email',
                'password' => 'required|minLength:8',
                'first_name' => 'maxLength:50',
                'last_name' => 'maxLength:50',
            ],
            'update' => [
                'username' => 'required|minLength:3|maxLength:30',
                'email' => 'required|email',
                'first_name' => 'maxLength:50',
                'last_name' => 'maxLength:50',
                'password' => 'minLength:8',
            ],
        ],
        'blog' => [
            'create' => [
                'title' => 'required|minLength:1|maxLength:255',
                'slug' => 'required|minLength:1|maxLength:255',
                'content' => 'required',
                'status' => 'required',
            ],
            'update' => [
                'title' => 'required|minLength:1|maxLength:255',
                'slug' => 'required|minLength:1|maxLength:255',
                'content' => 'required',
                'status' => 'required',
            ],
        ],
        'blog-posts' => [
            'create' => [
                'title' => 'required|minLength:1|maxLength:255',
                'slug' => 'required|minLength:1|maxLength:255',
                'content' => 'required',
                'status' => 'required',
            ],
            'update' => [
                'title' => 'required|minLength:1|maxLength:255',
                'slug' => 'required|minLength:1|maxLength:255',
                'content' => 'required',
                'status' => 'required',
            ],
        ],
        'blog-categories' => [
            'create' => [
                'name' => 'required|minLength:1|maxLength:255',
                'slug' => 'required|minLength:1|maxLength:255',
            ],
            'update' => [
                'name' => 'required|minLength:1|maxLength:255',
                'slug' => 'required|minLength:1|maxLength:255',
            ],
        ],
        'blog-tags' => [
            'create' => [
                'name' => 'required|minLength:1|maxLength:100',
                'slug' => 'required|minLength:1|maxLength:100',
            ],
            'update' => [
                'name' => 'required|minLength:1|maxLength:100',
                'slug' => 'required|minLength:1|maxLength:100',
            ],
        ],
        'pages' => [
            'create' => [
                'title' => 'required|minLength:1|maxLength:255',
                'slug' => 'required|minLength:1|maxLength:255',
                'route' => 'required|minLength:1|maxLength:255',
            ],
            'update' => [
                'title' => 'required|minLength:1|maxLength:255',
                'slug' => 'required|minLength:1|maxLength:255',
                'route' => 'required|minLength:1|maxLength:255',
            ],
        ],
    ];

    public function getModelClass(string $app): ?string
    {
        return self::APP_MODELS[$app] ?? null;
    }

    public function getValidationRules(string $app, string $operation): array
    {
        return self::VALIDATION_RULES[$app][$operation] ?? [];
    }

    public function actionAllowed(string $app, string $action): bool
    {
        return isset(self::APP_ACTIONS[$app]) && in_array($action, self::APP_ACTIONS[$app], true);
    }

    public function prepareData(string $app, array $data, Request $request, int $currentUserId = 1): array
    {
        $prepared = [];

        foreach ($data as $key => $value) {
            if (in_array($key, ['_token', '_method', 'csrf_token'], true)) {
                continue;
            }

            if ($key === 'email') {
                $prepared[$key] = Security::sanitize($value, 'email');
                continue;
            }

            if (in_array($key, ['username', 'title', 'name', 'slug', 'first_name', 'last_name'], true)) {
                $prepared[$key] = Security::sanitize($value, 'string');
                continue;
            }

            if (in_array($key, ['id', 'parent_id', 'author_id', 'menu_order', 'sort_order', 'views'], true)) {
                $prepared[$key] = (int) $value;
                continue;
            }

            if (in_array($key, ['is_active', 'is_in_menu', 'newsletter'], true)) {
                $prepared[$key] = $request->has($key);
                continue;
            }

            $prepared[$key] = $value;
        }

        if ($app === 'users' && isset($data['status'])) {
            $prepared['status'] = Security::sanitize($data['status'], 'string');
        }

        if (in_array($app, ['blog', 'blog-posts'], true)) {
            if (isset($data['title']) && empty($data['slug'])) {
                $prepared['slug'] = str_slug($data['title']);
            }

            if (!isset($prepared['author_id'])) {
                $prepared['author_id'] = $currentUserId;
            }
        }

        return $prepared;
    }

    public function handleCustomAction(string $app, string $action, $resource, int $currentUserId = 0): array
    {
        return match ($app) {
            'users' => $this->handleUserAction($action, $resource, $currentUserId),
            default => throw new Exception("No handler for action '{$action}' in app '{$app}'"),
        };
    }

    private function handleUserAction(string $action, User $user, int $currentUserId): array
    {
        switch ($action) {
            case 'ban':
                if ($user->id == $currentUserId) {
                    throw new Exception('Cannot ban your own account');
                }
                $user->ban();
                return $user->toArray();

            case 'unban':
                $user->unban();
                return $user->toArray();

            case 'approve':
                $user->approve();
                return $user->toArray();

            default:
                throw new Exception("Unknown action '{$action}' for users");
        }
    }
}


if (!\class_exists('DashboardApiResourceService', false) && !\interface_exists('DashboardApiResourceService', false) && !\trait_exists('DashboardApiResourceService', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardApiResourceService', 'DashboardApiResourceService');
}
