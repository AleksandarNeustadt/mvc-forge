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
 * Dashboard role/permission route adapter.
 */
class DashboardRoleController extends DashboardController
{
}


if (!\class_exists('DashboardRoleController', false) && !\interface_exists('DashboardRoleController', false) && !\trait_exists('DashboardRoleController', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardRoleController', 'DashboardRoleController');
}
