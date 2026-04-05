<?php

namespace Tests\PhpUnit;

use App\Core\database\Database;
use App\Models\BlogPost;
use App\Models\Language;
use App\Models\Page;
use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\TestDatabaseManager;

final class DatabaseHttpFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        TestDatabaseManager::resetAndSeed();
    }

    public function testSeededAdminCanLoginThroughHttpForm(): void
    {
        $response = $this->runHttpRequest('POST', '/sr/login', [
            '_csrf_token' => 'phpunit-login-token',
            'email' => 'admin@test.local',
            'password' => 'TestPassword123!@#',
        ], [
            '_csrf_token' => 'phpunit-login-token',
            '_csrf_token_time' => time(),
        ]);

        self::assertSame(302, $response['status']);
        self::assertStringNotContainsString('Fatal error', $response['stderr']);
        self::assertStringNotContainsString('PHP Fatal error', $response['stderr']);

        $adminRow = Database::selectOne(
            'SELECT last_login_ip FROM users WHERE email = ? LIMIT 1',
            ['admin@test.local']
        );

        self::assertSame($response['remote_addr'], $adminRow['last_login_ip'] ?? null);
    }

    public function testAuthenticatedLogoutWithValidCsrfTokenRedirects(): void
    {
        $response = $this->runHttpRequest(
            'POST',
            '/sr/logout',
            ['_csrf_token' => 'phpunit-logout-token'],
            [
                'user_id' => 1,
                'login_user_agent' => 'PHPUnit Browser',
                'last_activity' => time(),
                '_csrf_token' => 'phpunit-logout-token',
                '_csrf_token_time' => time(),
            ]
        );

        self::assertSame(302, $response['status']);
        self::assertStringNotContainsString('Fatal error', $response['stderr']);
        self::assertStringNotContainsString('PHP Fatal error', $response['stderr']);
    }

    public function testAuthenticatedDashboardPageStorePersistsPage(): void
    {
        $response = $this->runHttpRequest(
            'POST',
            '/sr/dashboard/pages',
            [
                'title' => 'Feature Smoke Page',
                'slug' => 'feature-smoke-page',
                'route' => '/feature-smoke-page',
                'application' => '',
                'page_type' => 'custom',
                'content' => '<p>Feature smoke body</p>',
                'template' => 'page',
                'meta_title' => 'Feature Smoke Page',
                'meta_description' => 'Feature smoke body',
                'meta_keywords' => 'feature,smoke',
                'is_active' => '1',
                'is_in_menu' => '1',
                'menu_order' => '5',
                'language_id' => '1',
            ],
            [
                'user_id' => 1,
                'login_user_agent' => 'PHPUnit Browser',
                'last_activity' => time(),
            ]
        );

        self::assertSame(302, $response['status']);
        self::assertStringNotContainsString('Fatal error', $response['stderr']);
        self::assertStringNotContainsString('PHP Fatal error', $response['stderr']);

        $pageRow = Database::selectOne(
            'SELECT title, slug, route, is_active, language_id FROM pages WHERE slug = ? LIMIT 1',
            ['feature-smoke-page']
        );

        self::assertIsArray($pageRow);
        self::assertSame('Feature Smoke Page', $pageRow['title'] ?? null);
        self::assertSame('/feature-smoke-page', $pageRow['route'] ?? null);
        self::assertSame(1, (int) ($pageRow['is_active'] ?? 0));
        self::assertSame(1, (int) ($pageRow['language_id'] ?? 0));
    }

    public function testLanguagePrefixedBlogListDoesNotLeakContentFromAnotherLanguage(): void
    {
        $serbianLanguage = Language::findByCode('sr');
        self::assertNotNull($serbianLanguage);

        $germanLanguage = new Language([
            'code' => 'de',
            'name' => 'German',
            'native_name' => 'Deutsch',
            'flag' => 'de',
            'country_code' => 'DE',
            'is_active' => 1,
            'is_site_language' => 1,
            'is_default' => 0,
            'sort_order' => 2,
        ]);
        $germanLanguage->save();

        $serbianPost = new BlogPost([
            'title' => 'Srpske Novosti',
            'slug' => 'srpske-novosti',
            'excerpt' => 'Tekst na srpskom',
            'content' => '<p>Tekst na srpskom</p>',
            'status' => 'published',
            'published_at' => time(),
            'author_id' => 1,
            'language_id' => $serbianLanguage->id,
        ]);
        $serbianPost->save();

        $germanPost = new BlogPost([
            'title' => 'Deutsche Nachrichten',
            'slug' => 'deutsche-nachrichten',
            'excerpt' => 'Text auf Deutsch',
            'content' => '<p>Text auf Deutsch</p>',
            'status' => 'published',
            'published_at' => time(),
            'author_id' => 1,
            'language_id' => $germanLanguage->id,
        ]);
        $germanPost->save();

        $serbianNewsPage = new Page([
            'title' => 'Novosti',
            'slug' => 'novosti',
            'route' => '/novosti',
            'page_type' => 'blog_list',
            'application' => 'blog',
            'template' => 'default',
            'meta_title' => 'Novosti',
            'meta_description' => 'Srpske novosti',
            'meta_keywords' => 'novosti',
            'is_active' => 1,
            'is_in_menu' => 1,
            'menu_order' => 1,
            'language_id' => $serbianLanguage->id,
        ]);
        $serbianNewsPage->save();

        $serbianResponse = $this->runHttpRequest('GET', '/sr/novosti');

        self::assertSame(200, $serbianResponse['status']);
        self::assertStringContainsString('Srpske Novosti', $serbianResponse['body']);
        self::assertStringNotContainsString('Deutsche Nachrichten', $serbianResponse['body']);

        $germanResponse = $this->runHttpRequest('GET', '/de/novosti');

        self::assertSame(404, $germanResponse['status']);
        self::assertStringNotContainsString('Srpske Novosti', $germanResponse['body']);
    }

    public function testDashboardAllowsSamePageSlugAndRouteForDifferentLanguages(): void
    {
        $serbianLanguage = Language::findByCode('sr');
        self::assertNotNull($serbianLanguage);

        $germanLanguage = new Language([
            'code' => 'de',
            'name' => 'German',
            'native_name' => 'Deutsch',
            'flag' => 'de',
            'country_code' => 'DE',
            'is_active' => 1,
            'is_site_language' => 1,
            'is_default' => 0,
            'sort_order' => 2,
        ]);
        $germanLanguage->save();

        $serbianPage = new Page([
            'title' => 'Novosti',
            'slug' => 'novosti',
            'route' => '/novosti',
            'page_type' => 'custom',
            'content' => '<p>Srpske novosti</p>',
            'template' => 'page',
            'is_active' => 1,
            'is_in_menu' => 1,
            'menu_order' => 1,
            'language_id' => $serbianLanguage->id,
        ]);
        $serbianPage->save();

        $response = $this->runHttpRequest(
            'POST',
            '/sr/dashboard/pages',
            [
                'title' => 'Novosti DE',
                'slug' => 'novosti',
                'route' => '/novosti',
                'application' => '',
                'page_type' => 'custom',
                'content' => '<p>Deutsche Nachrichten</p>',
                'template' => 'page',
                'meta_title' => 'Novosti DE',
                'meta_description' => 'Deutsche Nachrichten',
                'meta_keywords' => 'novosti',
                'is_active' => '1',
                'is_in_menu' => '1',
                'menu_order' => '2',
                'language_id' => (string) $germanLanguage->id,
            ],
            [
                'user_id' => 1,
                'login_user_agent' => 'PHPUnit Browser',
                'last_activity' => time(),
            ]
        );

        self::assertSame(302, $response['status']);
        self::assertSame('', $response['stderr']);

        $rows = Database::select(
            'SELECT route, slug, language_id FROM pages WHERE route = ? ORDER BY language_id ASC',
            ['/novosti']
        );

        self::assertCount(2, $rows);
        self::assertSame((int) $serbianLanguage->id, (int) ($rows[0]['language_id'] ?? 0));
        self::assertSame((int) $germanLanguage->id, (int) ($rows[1]['language_id'] ?? 0));
    }

    public function testTranslatedContentPathUsesTranslationGroupWhenAvailable(): void
    {
        $serbianLanguage = Language::findByCode('sr');
        self::assertNotNull($serbianLanguage);

        $germanLanguage = new Language([
            'code' => 'de',
            'name' => 'German',
            'native_name' => 'Deutsch',
            'flag' => 'de',
            'country_code' => 'DE',
            'is_active' => 1,
            'is_site_language' => 1,
            'is_default' => 0,
            'sort_order' => 2,
        ]);
        $germanLanguage->save();

        $translationGroupId = 'page-novosti-shared';

        $serbianPage = new Page([
            'title' => 'Novosti',
            'slug' => 'novosti',
            'route' => '/novosti',
            'page_type' => 'custom',
            'content' => '<p>Srpske novosti</p>',
            'template' => 'page',
            'is_active' => 1,
            'is_in_menu' => 1,
            'menu_order' => 1,
            'language_id' => $serbianLanguage->id,
            'translation_group_id' => $translationGroupId,
        ]);
        $serbianPage->save();

        $germanPage = new Page([
            'title' => 'Nachrichten',
            'slug' => 'nachrichten',
            'route' => '/nachrichten',
            'page_type' => 'custom',
            'content' => '<p>Deutsche Nachrichten</p>',
            'template' => 'page',
            'is_active' => 1,
            'is_in_menu' => 1,
            'menu_order' => 1,
            'language_id' => $germanLanguage->id,
            'translation_group_id' => $translationGroupId,
        ]);
        $germanPage->save();

        self::assertSame('/de/nachrichten', translated_content_path('de', '/novosti', 'sr'));
    }

    /**
     * @param array<string, mixed> $postData
     * @param array<string, mixed> $sessionData
     * @return array{status:int,body:string,stderr:string,remote_addr:string}
     */
    private function runHttpRequest(
        string $method,
        string $uri,
        array $postData = [],
        array $sessionData = [],
        string $accept = 'text/html'
    ): array {
        $indexPath = realpath(__DIR__ . '/../../../public_html/index.php');
        if ($indexPath === false) {
            throw new RuntimeException('Unable to resolve public entry point.');
        }

        $scriptPath = tempnam(sys_get_temp_dir(), 'ap_db_http_');
        if ($scriptPath === false) {
            throw new RuntimeException('Unable to create temporary DB HTTP script.');
        }

        $postExport = var_export($postData, true);
        $sessionExport = var_export($sessionData, true);
        $methodExport = var_export($method, true);
        $uriExport = var_export($uri, true);
        $acceptExport = var_export($accept, true);
        $indexPathExport = var_export($indexPath, true);
        $sessionIdExport = var_export('ap-phpunit-' . bin2hex(random_bytes(16)), true);
        $remoteAddress = '127.0.' . random_int(0, 255) . '.' . random_int(1, 254);
        $remoteAddressExport = var_export($remoteAddress, true);

        $script = <<<PHP
<?php
putenv('APP_ENV=testing');
putenv('APP_ENV_FILE=.env.testing');

session_id({$sessionIdExport});
session_start();
\$_SESSION = {$sessionExport};
\$_COOKIE[session_name()] = session_id();
session_write_close();

\$_SERVER['REQUEST_METHOD'] = {$methodExport};
\$_SERVER['REQUEST_URI'] = {$uriExport};
\$_SERVER['HTTP_HOST'] = 'localhost';
\$_SERVER['HTTP_ACCEPT'] = {$acceptExport};
\$_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Browser';
\$_SERVER['REMOTE_ADDR'] = {$remoteAddressExport};
\$_POST = {$postExport};
\$_GET = [];
\$_FILES = [];

ob_start();
register_shutdown_function(static function (): void {
    \$body = ob_get_clean();
    echo json_encode([
        'status' => http_response_code() ?: 200,
        'body' => is_string(\$body) ? \$body : '',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
});

require {$indexPathExport};
PHP;

        file_put_contents($scriptPath, $script);

        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open([PHP_BINARY, $scriptPath], $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            @unlink($scriptPath);
            throw new RuntimeException('Unable to start DB HTTP subprocess.');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        @unlink($scriptPath);

        try {
            $payload = json_decode((string) $stdout, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Invalid DB HTTP subprocess output: ' . (string) $stdout,
                0,
                $exception
            );
        }

        return [
            'status' => (int) ($payload['status'] ?? 0) ?: 200,
            'body' => (string) ($payload['body'] ?? ''),
            'stderr' => trim((string) $stderr),
            'remote_addr' => $remoteAddress,
        ];
    }
}
