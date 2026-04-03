<?php

namespace Tests\Support;

use App\Core\config\Env;
use App\Core\database\Database;
use App\Models\ApiToken;
use App\Models\Language;
use App\Models\Page;
use App\Models\User;
use RuntimeException;
use Throwable;

final class TestDatabaseManager
{
    private const TEST_DATABASE_SUFFIX = '_test';

    private const TABLES_TO_CLEAR = [
        'api_tokens',
        'blog_post_categories',
        'blog_post_tags',
        'blog_posts',
        'blog_categories',
        'blog_tags',
        'contact_messages',
        'pages',
        'navigation_menus',
        'user_role',
        'role_permission',
        'roles',
        'permissions',
        'password_history',
        'audit_logs',
        'ip_tracking',
        'ip_geo_cache',
        'ip_services',
        'languages',
        'regions',
        'continents',
        'users',
    ];

    /**
     * @return array{admin_user_id:int,api_token:string,language_id:int,page_id:int}
     */
    public static function resetAndSeed(): array
    {
        self::assertSafeTestingDatabase();
        self::clearFixtureTables();

        return self::seedFixtures();
    }

    public static function assertSafeTestingDatabase(): void
    {
        $appEnv = (string) Env::get('APP_ENV', '');
        $database = (string) Env::get('DB_DATABASE', '');

        if ($appEnv !== 'testing') {
            throw new RuntimeException("Refusing test DB reset outside APP_ENV=testing (current: {$appEnv}).");
        }

        if ($database === '' || !str_ends_with($database, self::TEST_DATABASE_SUFFIX)) {
            throw new RuntimeException(
                "Refusing test DB reset for non-test database name '{$database}'. Expected suffix '" . self::TEST_DATABASE_SUFFIX . "'."
            );
        }
    }

    public static function clearFixtureTables(): void
    {
        $pdo = Database::connection();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach (self::TABLES_TO_CLEAR as $table) {
                if (self::tableExists($table)) {
                    Database::execute('TRUNCATE TABLE ' . Database::quoteIdentifier($table));
                }
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * @return array{admin_user_id:int,api_token:string,language_id:int,page_id:int}
     */
    public static function seedFixtures(): array
    {
        return Database::transaction(static function (): array {
            $admin = User::createUser([
                'username' => 'test-admin',
                'email' => 'admin@test.local',
                'password' => 'TestPassword123!@#',
                'first_name' => 'Test',
                'last_name' => 'Admin',
                'newsletter' => false,
                'email_verified_at' => time(),
                'approved_at' => time(),
                'status' => 'active',
            ]);

            $language = new Language([
                'code' => 'sr',
                'name' => 'Serbian',
                'native_name' => 'Srpski',
                'flag' => 'rs',
                'country_code' => 'RS',
                'is_active' => 1,
                'is_site_language' => 1,
                'is_default' => 1,
                'sort_order' => 1,
            ]);
            $language->save();

            $page = new Page([
                'title' => 'O Autoru',
                'slug' => 'o-autoru',
                'route' => '/o-autoru',
                'page_type' => 'custom',
                'content' => '<p>Seedovana test stranica.</p>',
                'template' => 'page',
                'meta_title' => 'O Autoru',
                'meta_description' => 'Seedovana test stranica.',
                'meta_keywords' => 'test,seed',
                'is_active' => 1,
                'is_in_menu' => 1,
                'menu_order' => 1,
                'parent_page_id' => null,
                'navbar_id' => null,
                'blog_post_id' => null,
                'blog_category_id' => null,
                'blog_tag_id' => null,
                'language_id' => $language->id,
            ]);
            $page->save();

            $apiToken = ApiToken::createToken((int) $admin->id, 'phpunit-seed-token');

            return [
                'admin_user_id' => (int) $admin->id,
                'api_token' => (string) $apiToken->token,
                'language_id' => (int) $language->id,
                'page_id' => (int) $page->id,
            ];
        });
    }

    private static function tableExists(string $table): bool
    {
        try {
            $row = Database::selectOne(
                'SELECT 1 AS table_exists
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                   AND table_type = "BASE TABLE"
                 LIMIT 1',
                [$table]
            );

            return $row !== null;
        } catch (Throwable) {
            return false;
        }
    }
}
