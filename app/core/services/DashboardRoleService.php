<?php

namespace App\Core\services;


use App\Core\database\Database;
use App\Models\Permission;
use App\Models\Role;use BadMethodCallException;
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
 * Service layer for dashboard role and permission management.
 */
class DashboardRoleService
{
    public function getRoleList(): array
    {
        return Role::all();
    }

    public function getPermissionGroups(): array
    {
        return Permission::groupedByCategory();
    }

    public function buildEditFormData(Role $role): array
    {
        $rolePermissions = $role->permissions();
        $rolePermissionIds = array_map(
            static fn($permission): ?int => isset($permission->id) ? (int) $permission->id : null,
            $rolePermissions
        );

        return [
            'role' => $role->toArray(),
            'permissions' => $this->getPermissionGroups(),
            'rolePermissionIds' => array_values(array_filter($rolePermissionIds)),
        ];
    }

    public function normalizeRoleData(array $data): array
    {
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = str_slug($data['name']);
        }

        $data['name'] = $data['name'] ?? '';
        $data['slug'] = $data['slug'] ?? '';
        $data['description'] = $data['description'] ?? '';
        $data['priority'] = isset($data['priority']) ? (int) $data['priority'] : 0;
        $data['permissions'] = is_array($data['permissions'] ?? null) ? $data['permissions'] : [];

        return $data;
    }

    public function slugExists(string $slug, ?int $excludeRoleId = null): bool
    {
        $query = Role::query()->where('slug', $slug);

        if ($excludeRoleId !== null) {
            $query->where('id', '!=', $excludeRoleId);
        }

        return $query->exists();
    }

    public function validateSystemRoleMutation(Role $role, array $data): array
    {
        if ($role->is_system && (($data['name'] ?? '') !== $role->name || ($data['slug'] ?? '') !== $role->slug)) {
            return ['error' => ['Cannot modify system role name or slug']];
        }

        return [];
    }

    public function validateRoleData(array $data, ?Role $role = null): array
    {
        $errors = [];
        $excludeRoleId = $role ? (int) $role->id : null;

        if ($this->slugExists((string) ($data['slug'] ?? ''), $excludeRoleId)) {
            $errors['slug'] = ['This slug is already taken'];
        }

        if ($role) {
            $errors = array_merge($errors, $this->validateSystemRoleMutation($role, $data));
        }

        return $errors;
    }

    public function syncPermissions(Role $role, array $data): void
    {
        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions'] ?? []);
        }
    }

    public function detachRoleRelations(int $roleId): void
    {
        Database::transaction(static function () use ($roleId): void {
            Database::table('role_permission')
                ->where('role_id', $roleId)
                ->delete();

            Database::table('user_role')
                ->where('role_id', $roleId)
                ->delete();
        });
    }

    public function createRole(array $data): Role
    {
        $role = Role::create($data);
        $this->syncPermissions($role, $data);

        return $role;
    }

    public function updateRole(Role $role, array $data): Role
    {
        $role->update($data);
        $this->syncPermissions($role, $data);

        return $role;
    }

    public function deleteRole(Role $role): void
    {
        if ($role->is_system) {
            throw new InvalidArgumentException('Cannot delete system role');
        }

        Database::transaction(function () use ($role): void {
            $this->detachRoleRelations((int) $role->id);
            $role->delete();
        });
    }
}


if (!\class_exists('DashboardRoleService', false) && !\interface_exists('DashboardRoleService', false) && !\trait_exists('DashboardRoleService', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardRoleService', 'DashboardRoleService');
}
