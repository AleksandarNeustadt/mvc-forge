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
 * Dashboard user-management route adapter.
 */
class DashboardUserController extends DashboardController
{
}


if (!\class_exists('DashboardUserController', false) && !\interface_exists('DashboardUserController', false) && !\trait_exists('DashboardUserController', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardUserController', 'DashboardUserController');
}
