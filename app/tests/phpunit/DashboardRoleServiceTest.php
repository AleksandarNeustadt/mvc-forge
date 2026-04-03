<?php

namespace Tests\PhpUnit;

use App\Core\services\DashboardRoleService;
use App\Models\Role;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DashboardRoleServiceTest extends TestCase
{
    public function testNormalizeRoleDataGeneratesSlugAndCoercesTypes(): void
    {
        $service = new DashboardRoleService();

        $normalized = $service->normalizeRoleData([
            'name' => 'Content Editor',
            'description' => 'Editorial access',
            'priority' => '9',
            'permissions' => 'invalid-shape',
        ]);

        self::assertSame('Content Editor', $normalized['name']);
        self::assertSame('content-editor', $normalized['slug']);
        self::assertSame('Editorial access', $normalized['description']);
        self::assertSame(9, $normalized['priority']);
        self::assertSame([], $normalized['permissions']);
    }

    public function testValidateSystemRoleMutationRejectsRenamingSystemRole(): void
    {
        $service = new DashboardRoleService();
        $role = new Role([
            'name' => 'Admin',
            'slug' => 'admin',
            'is_system' => 1,
        ]);
        $role->id = 1;

        self::assertSame(
            ['error' => ['Cannot modify system role name or slug']],
            $service->validateSystemRoleMutation($role, [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
            ])
        );
    }

    public function testDeleteRoleRejectsSystemRoleBeforeDatabaseMutation(): void
    {
        $service = new DashboardRoleService();
        $role = new Role([
            'name' => 'Admin',
            'slug' => 'admin',
            'is_system' => 1,
        ]);
        $role->id = 1;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete system role');

        $service->deleteRole($role);
    }
}
