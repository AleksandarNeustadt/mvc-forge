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
 * Dashboard language route adapter.
 */
class DashboardLanguageController extends DashboardController
{
}


if (!\class_exists('DashboardLanguageController', false) && !\interface_exists('DashboardLanguageController', false) && !\trait_exists('DashboardLanguageController', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardLanguageController', 'DashboardLanguageController');
}
