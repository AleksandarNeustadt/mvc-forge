<?php

$roots = [
    dirname(__DIR__) . '/bootstrap',
    dirname(__DIR__) . '/core',
    dirname(__DIR__) . '/mvc',
    dirname(__DIR__) . '/routes',
    dirname(__DIR__) . '/scripts',
    dirname(__DIR__) . '/tests',
];

$errors = [];

foreach ($roots as $root) {
    if (!is_dir($root)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
            continue;
        }

        $path = $fileInfo->getPathname();
        $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1';
        $output = [];
        $exitCode = 0;

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            $errors[] = $path . PHP_EOL . implode(PHP_EOL, $output);
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL . PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}

echo "ALL_PHP_LINT_OK\n";
