<?php

namespace App\Core\services;


use App\Core\database\Database;
use App\Models\IpTracking;use BadMethodCallException;
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
 * Service layer for dashboard IP tracking analytics.
 */
class DashboardIpTrackingService
{
    public function buildDashboardData(int $page, string $ipFilter = '', int $perPage = 50): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $ipStats = IpTracking::getIpStats(100);

        $allRecentEntries = IpTracking::getRecent(1000);
        if ($ipFilter !== '') {
            $allRecentEntries = array_values(array_filter(
                $allRecentEntries,
                static fn(array $entry): bool => ($entry['ip_address'] ?? '') === $ipFilter
            ));
        }

        $total = count($allRecentEntries);

        return [
            'recentEntries' => array_slice($allRecentEntries, $offset, $perPage),
            'ipStats' => $ipStats,
            'hourlyStats' => IpTracking::getHourlyStats(24),
            'countryStats' => IpTracking::getCountryStats(10),
            'suspiciousCount' => Database::table('ip_tracking')
                ->where('is_suspicious', 1)
                ->count(),
            'totalRequests' => $total,
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
            'ipFilter' => $ipFilter,
        ];
    }
}


if (!\class_exists('DashboardIpTrackingService', false) && !\interface_exists('DashboardIpTrackingService', false) && !\trait_exists('DashboardIpTrackingService', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardIpTrackingService', 'DashboardIpTrackingService');
}
