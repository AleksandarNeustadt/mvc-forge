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
 * Dashboard contact-message route adapter.
 */
class DashboardContactMessageController extends DashboardController
{
}


if (!\class_exists('DashboardContactMessageController', false) && !\interface_exists('DashboardContactMessageController', false) && !\trait_exists('DashboardContactMessageController', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardContactMessageController', 'DashboardContactMessageController');
}
