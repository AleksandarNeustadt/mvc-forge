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
 * Dashboard continent/region route adapter.
 */
class DashboardGeoController extends DashboardController
{
}


if (!\class_exists('DashboardGeoController', false) && !\interface_exists('DashboardGeoController', false) && !\trait_exists('DashboardGeoController', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardGeoController', 'DashboardGeoController');
}
