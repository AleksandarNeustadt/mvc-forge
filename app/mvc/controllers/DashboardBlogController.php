<?php

namespace App\Controllers;

use BadMethodCallException;
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
 * Dashboard blog post/category/tag/media route adapter.
 */
class DashboardBlogController extends DashboardController
{
}


if (!\class_exists('DashboardBlogController', false) && !\interface_exists('DashboardBlogController', false) && !\trait_exists('DashboardBlogController', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardBlogController', 'DashboardBlogController');
}
