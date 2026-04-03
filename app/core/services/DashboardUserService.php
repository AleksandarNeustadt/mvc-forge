<?php

namespace App\Core\services;


use App\Core\cache\Cache;
use App\Core\database\Database;
use App\Core\logging\Logger;
use App\Core\security\Security;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;use BadMethodCallException;
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
 * Service layer for dashboard user-management business rules.
 */
class DashboardUserService
{
    /**
     * Build paginated user list data with eager-loaded role labels.
     */
    public function buildUserListData(int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $hasDeletedAt = $this->hasDeletedAtColumn();

        $query = Database::table('users');
        if ($hasDeletedAt) {
            $query->whereNull('deleted_at');
        }

        $total = $query->count();
        $whereClause = $hasDeletedAt ? 'WHERE u.deleted_at IS NULL' : '';

        $usersData = Database::select(
            "SELECT u.*,
                    GROUP_CONCAT(DISTINCT r.id) as role_ids,
                    GROUP_CONCAT(DISTINCT r.name) as role_names,
                    GROUP_CONCAT(DISTINCT r.slug) as role_slugs
             FROM users u
             LEFT JOIN user_role ur ON u.id = ur.user_id
             LEFT JOIN roles r ON ur.role_id = r.id
             {$whereClause}
             GROUP BY u.id
             ORDER BY u.created_at DESC
             LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );

        $users = [];
        foreach ($usersData as $userData) {
            $users[] = $this->hydrateUserListRow($userData);
        }

        $totalPages = (int) ceil($total / $perPage);

        return [
            'users' => $users,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total' => $total,
                'per_page' => $perPage,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages,
            ],
        ];
    }

    /**
     * Clear flashed form state before rendering create-user form.
     */
    public function clearFormState(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION['_old_input'], $_SESSION['_form_errors']);
    }

    /**
     * Convert user entity to a normalized array payload.
     */
    public function normalizeUserArray(User $user): array
    {
        $userArray = $user->toArray();
        if (is_object($userArray)) {
            $userArray = json_decode(json_encode($userArray), true);
        }

        return is_array($userArray) ? $userArray : [];
    }

    /**
     * Build edit-user view model.
     */
    public function buildEditFormData(User $user): array
    {
        $userRoleIds = array_map(
            static fn($role): ?int => isset($role->id) ? (int) $role->id : null,
            $user->roles()
        );

        return [
            'editUser' => $this->normalizeUserArray($user),
            'allRoles' => Role::all(),
            'userRoleIds' => array_values(array_filter($userRoleIds)),
        ];
    }

    /**
     * Validate user uniqueness constraints for email and username.
     */
    public function validateUniqueness(string $email, string $username, ?int $excludeId = null): array
    {
        $errors = [];

        $existingUserByEmail = User::findByEmail($email);
        if ($existingUserByEmail && $existingUserByEmail->id != $excludeId) {
            $errors['email'] = ['Email already exists'];
        }

        $existingUserByUsername = User::findByUsername($username);
        if ($existingUserByUsername && $existingUserByUsername->id != $excludeId) {
            $errors['username'] = ['Username already exists'];
        }

        return $errors;
    }

    /**
     * Validate create-user payload, including password strength and uniqueness.
     */
    public function validateCreateInput(array|bool $validation, array $input): array|bool
    {
        return $this->mergePasswordAndUniquenessValidation(
            $validation,
            $input,
            null,
            true
        );
    }

    /**
     * Validate update-user payload, including optional password and uniqueness.
     */
    public function validateUpdateInput(array|bool $validation, array $input, int $userId): array|bool
    {
        return $this->mergePasswordAndUniquenessValidation(
            $validation,
            $input,
            $userId,
            !empty($input['password'])
        );
    }

    /**
     * Apply scalar profile fields to a user entity.
     */
    public function applyProfileData(User $user, array $input): void
    {
        $user->username = Security::sanitize((string) ($input['username'] ?? ''), 'string');
        $user->email = Security::sanitize((string) ($input['email'] ?? ''), 'email');
        $user->first_name = Security::sanitize((string) ($input['first_name'] ?? ''), 'string') ?: null;
        $user->last_name = Security::sanitize((string) ($input['last_name'] ?? ''), 'string') ?: null;
        $user->newsletter = !empty($input['newsletter']);
        $user->status = Security::sanitize((string) ($input['status'] ?? 'active'), 'string');
    }

    /**
     * Apply status transition side effects.
     */
    public function applyStatusTransitions(User $user): void
    {
        if ($user->status === 'banned' && empty($user->banned_at)) {
            $user->ban();
        } elseif ($user->status === 'active' && !empty($user->banned_at)) {
            $user->unban();
        }

        if ($user->status === 'active' && empty($user->approved_at)) {
            $user->approve();
        }
    }

    /**
     * Generate and assign a unique user slug from username.
     */
    public function assignUniqueSlug(User $user, string $username, ?int $excludeId = null): void
    {
        $slug = str_slug($username);
        $originalSlug = $slug;
        $counter = 1;

        while (User::slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $user->slug = $slug;
    }

    /**
     * Persist a newly created user inside a transaction.
     */
    public function createUser(array $input, ?array $avatarFile): User
    {
        return Database::transaction(function () use ($input, $avatarFile): User {
            $user = new User();
            $this->applyProfileData($user, [
                'username' => $input['username'] ?? '',
                'email' => $input['email'] ?? '',
                'first_name' => $input['first_name'] ?? null,
                'last_name' => $input['last_name'] ?? null,
                'newsletter' => !empty($input['newsletter']),
                'status' => $input['status'] ?? 'active',
            ]);
            $this->assignUniqueSlug($user, (string) ($input['username'] ?? ''));
            $user->password_hash = Security::hashPassword((string) ($input['password'] ?? ''));
            $user->approved_at = $user->status === 'active' ? time() : null;

            $avatarErrors = $this->applyAvatarChanges(
                $user,
                !empty($input['delete_avatar']) && $input['delete_avatar'] == '1',
                $avatarFile,
                'avatar'
            );
            if (!empty($avatarErrors)) {
                throw new RuntimeException(implode(' ', $avatarErrors));
            }

            if (!$user->save()) {
                throw new RuntimeException('Failed to save user to database - save() returned false');
            }

            if (empty($user->id)) {
                throw new RuntimeException('User saved but ID is missing');
            }

            AuditLog::log('user.created', 'User', $user->id, null, $user->toArray());
            Logger::info('User created', ['user_id' => $user->id, 'username' => $user->username]);

            return $user;
        });
    }

    /**
     * Persist user profile/password/avatar/role changes inside a transaction.
     */
    public function updateUser(User $user, array $input, ?array $avatarFile, ?User $currentUser = null): User
    {
        $oldUserData = $user->toArray();

        return Database::transaction(function () use ($user, $input, $avatarFile, $currentUser, $oldUserData): User {
            $this->applyProfileData($user, [
                'username' => $input['username'] ?? '',
                'email' => $input['email'] ?? '',
                'first_name' => $input['first_name'] ?? null,
                'last_name' => $input['last_name'] ?? null,
                'newsletter' => !empty($input['newsletter']),
                'status' => $input['status'] ?? 'active',
            ]);
            $this->applyStatusTransitions($user);

            if (!empty($input['password'])) {
                $user->updatePassword((string) $input['password']);
            }

            $avatarErrors = $this->applyAvatarChanges(
                $user,
                !empty($input['delete_avatar']) && $input['delete_avatar'] == '1',
                $avatarFile,
                'avatar'
            );
            if (!empty($avatarErrors)) {
                throw new RuntimeException(implode(' ', $avatarErrors));
            }

            $user->save();

            $this->syncRolesIfAllowed(
                $user,
                $currentUser,
                $input['roles'] ?? [],
                array_key_exists('roles', $input)
            );

            Cache::forget("user_{$user->id}_roles");
            AuditLog::log('user.updated', 'User', $user->id, $oldUserData, $user->toArray());
            Logger::info('User updated', ['user_id' => $user->id, 'username' => $user->username]);

            return $user;
        });
    }

    public function deleteUser(User $user, int $currentUserId): array
    {
        $this->assertNotSelfAction($user, $currentUserId, 'Cannot delete your own account');

        $userData = $user->toArray();
        $user->delete();

        AuditLog::log('user.deleted', 'User', $userData['id'], $userData, null);
        Logger::info('User deleted', [
            'user_id' => $userData['id'],
            'username' => $userData['username'] ?? '',
        ]);

        return is_array($userData) ? $userData : [];
    }

    public function banUser(User $user, int $currentUserId): User
    {
        $this->assertNotSelfAction($user, $currentUserId, 'Cannot ban your own account');
        $user->ban();

        return $user;
    }

    public function unbanUser(User $user): User
    {
        $user->unban();

        return $user;
    }

    public function approveUser(User $user): User
    {
        $user->approve();

        return $user;
    }

    /**
     * Sync user roles if current actor has role-management permission.
     */
    public function syncRolesIfAllowed(User $user, ?User $currentUser, mixed $roleIds, bool $rolesProvided): void
    {
        if (!$currentUser || (!$currentUser->hasPermission('users.manage-roles') && !$currentUser->isSuperAdmin())) {
            return;
        }

        if ($rolesProvided) {
            $user->syncRoles(is_array($roleIds) ? $roleIds : []);
            return;
        }

        if ((int) $user->id !== (int) $currentUser->id) {
            $user->syncRoles([]);
        }
    }

    /**
     * Apply avatar upload/delete changes to a user entity.
     */
    public function applyAvatarChanges(
        User $user,
        bool $shouldDeleteAvatar,
        ?array $avatarFile,
        string $uploadField = 'avatar'
    ): array {
        if ($shouldDeleteAvatar) {
            $this->deleteAvatarFile($user->avatar);
            $user->avatar = null;

            return [];
        }

        if (!$avatarFile || ($avatarFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [];
        }

        try {
            $uploader = new UploadManager('storage/uploads/avatars/');
            $result = $uploader->upload($uploadField, [
                'maxSize' => 2 * 1024 * 1024,
                'allowedTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                'prefix' => 'avatar_'
            ]);

            if (!$result) {
                return $uploader->getErrors();
            }

            if (!empty($user->avatar)) {
                $this->deleteAvatarFile($user->avatar);
            }

            $user->avatar = $result['url'];

            return [];
        } catch (Exception $e) {
            error_log('Avatar upload error: ' . $e->getMessage());

            return ['Failed to upload avatar: ' . $e->getMessage()];
        }
    }

    /**
     * Delete avatar file from filesystem.
     */
    public function deleteAvatarFile(?string $avatarPath): void
    {
        if (empty($avatarPath)) {
            return;
        }

        $filePath = str_replace('/storage/uploads/avatars/', '', $avatarPath);
        $projectRoot = dirname(__DIR__, 2);
        $fullPath = $projectRoot . '/storage/uploads/avatars/' . basename($filePath);

        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    private function hasDeletedAtColumn(): bool
    {
        try {
            $columns = Database::select("SHOW COLUMNS FROM users LIKE 'deleted_at'");
            return !empty($columns);
        } catch (Exception $e) {
            return false;
        }
    }

    private function hydrateUserListRow(array $userData): array
    {
        $user = new User();
        $userArray = $user->newFromBuilder($userData)->toArray();
        if (is_object($userArray)) {
            $userArray = json_decode(json_encode($userArray), true);
        }
        $userArray = is_array($userArray) ? $userArray : [];
        $userArray['roles'] = [];

        if (empty($userData['role_ids'])) {
            return $userArray;
        }

        $roleIds = explode(',', $userData['role_ids']);
        $roleNames = explode(',', $userData['role_names'] ?? '');
        $roleSlugs = explode(',', $userData['role_slugs'] ?? '');

        foreach ($roleIds as $index => $roleId) {
            $userArray['roles'][] = [
                'id' => (int) $roleId,
                'name' => $roleNames[$index] ?? '',
                'slug' => $roleSlugs[$index] ?? '',
            ];
        }

        return $userArray;
    }

    private function mergePasswordAndUniquenessValidation(
        array|bool $validation,
        array $input,
        ?int $excludeId,
        bool $validatePassword
    ): array|bool {
        $errors = is_array($validation) ? $validation : [];

        if ($validatePassword) {
            $passwordStrengthErrors = Security::validatePasswordStrength(
                (string) ($input['password'] ?? ''),
                8
            );
            if (!empty($passwordStrengthErrors)) {
                $errors['password'] = array_merge($errors['password'] ?? [], $passwordStrengthErrors);
            }
        }

        $uniquenessErrors = $this->validateUniqueness(
            (string) ($input['email'] ?? ''),
            (string) ($input['username'] ?? ''),
            $excludeId
        );
        foreach ($uniquenessErrors as $field => $fieldErrors) {
            $errors[$field] = array_merge($errors[$field] ?? [], $fieldErrors);
        }

        return empty($errors) ? true : $errors;
    }

    private function assertNotSelfAction(User $user, int $currentUserId, string $message): void
    {
        if ((int) $user->id === $currentUserId) {
            throw new InvalidArgumentException($message);
        }
    }
}


if (!\class_exists('DashboardUserService', false) && !\interface_exists('DashboardUserService', false) && !\trait_exists('DashboardUserService', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardUserService', 'DashboardUserService');
}
