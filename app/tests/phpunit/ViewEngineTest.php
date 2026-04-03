<?php

namespace Tests\PhpUnit;

use App\Core\view\ViewEngine;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ViewEngineTest extends TestCase
{
    private string $tempViewRoot = '';
    private string $tempCacheRoot = '';
    private string $originalViewPath = '';
    private string $originalCachePath = '';

    protected function setUp(): void
    {
        $this->tempViewRoot = sys_get_temp_dir() . '/ap_view_test_' . bin2hex(random_bytes(8));
        $this->tempCacheRoot = sys_get_temp_dir() . '/ap_view_cache_' . bin2hex(random_bytes(8));
        mkdir($this->tempViewRoot . '/pages', 0777, true);
        mkdir($this->tempCacheRoot, 0777, true);

        $ref = new ReflectionClass(ViewEngine::class);
        $viewPath = $ref->getProperty('viewPath');
        $cachePath = $ref->getProperty('cachePath');

        $this->originalViewPath = $viewPath->getValue();
        $this->originalCachePath = $cachePath->getValue();

        $viewPath->setValue(null, $this->tempViewRoot);
        $cachePath->setValue(null, $this->tempCacheRoot);
    }

    protected function tearDown(): void
    {
        $ref = new ReflectionClass(ViewEngine::class);
        $ref->getProperty('viewPath')->setValue(null, $this->originalViewPath);
        $ref->getProperty('cachePath')->setValue(null, $this->originalCachePath);

        $this->removeTree($this->tempViewRoot);
        $this->removeTree($this->tempCacheRoot);
    }

    public function testTemplateRenderCompilesConditionalsAndEscapedEcho(): void
    {
        file_put_contents(
            $this->tempViewRoot . '/pages/demo.template.php',
            '@if($show)<p>{{ $message }}</p>@else<p>hidden</p>@endif'
        );

        $html = ViewEngine::render('pages/demo', [
            'show' => true,
            'message' => '<b>hello</b>',
        ]);

        self::assertStringContainsString('<p>&lt;b&gt;hello&lt;/b&gt;</p>', $html);
        self::assertStringNotContainsString('<p>hidden</p>', $html);
    }

    private function removeTree(string $path): void
    {
        if ($path === '' || !is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($path);
    }
}
