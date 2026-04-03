<?php

namespace Tests\PhpUnit;

use App\Core\database\QueryBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class QueryBuilderTest extends TestCase
{
    public function testToSqlBuildsQuotedQueryWithWhereAnyLikeGroup(): void
    {
        $sql = (new QueryBuilder('users'))
            ->select(['users.id', 'users.username as author_name'])
            ->where('status', 'active')
            ->whereAnyLike(['username', 'email'], '%alek%')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->offset(10)
            ->toSql();
        $sql = preg_replace('/\s+/', ' ', trim($sql));

        self::assertStringContainsString('SELECT `users`.`id`, `users`.`username` AS `author_name` FROM `users`', $sql);
        self::assertStringContainsString("WHERE `status` = 'active' AND (`username` LIKE '%alek%' OR `email` LIKE '%alek%')", $sql);
        self::assertStringContainsString('ORDER BY `created_at` DESC', $sql);
        self::assertStringContainsString('LIMIT 5', $sql);
        self::assertStringContainsString('OFFSET 10', $sql);
    }

    public function testInvalidIdentifiersAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new QueryBuilder('users; DROP TABLE users');
    }
}
