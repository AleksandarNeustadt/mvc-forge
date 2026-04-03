<?php

namespace App\Controllers;


use App\Core\cache\Cache;
use App\Core\database\Database;
use App\Core\logging\Logger;
use App\Core\mvc\Controller;
use App\Core\security\CSRF;
use App\Core\security\Security;
use App\Core\services\UploadManager;
use App\Core\view\Form;
use App\Models\AuditLog;
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

// mvc/controllers/UserController.php

class UserController extends Controller {

    /**
     * Show user profile by slug (SEO-friendly)
     *
     * Route: GET /user/{slug}
     * Example: /user/john-doe-developer
     */
    public function show($slug): void {
        // Access Request via $this->request
        $includeDetails = $this->request->query('details', false);

        // Mock user data (TODO: fetch from database by slug)
        // In real app: $user = User::findBySlug($slug);
        $user = [
            'id' => rand(1, 1000),
            'slug' => $slug,
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'email' => $slug . '@example.com',
            'created_at' => date('Y-m-d H:i:s'),
            'profile_url' => '/user/' . $slug
        ];

        if ($includeDetails) {
            $user['details'] = [
                'bio' => 'This is a test user profile',
                'location' => 'Serbia',
                'joined' => '2024'
            ];
        }

        // Return JSON or view based on request
        if ($this->wantsJson()) {
            $this->success($user);
        }

        // Render view (TODO: create user/show view)
        $this->view('under_construction', ['user' => $user]);
    }

    /**
     * Show user profile by ID (backwards compatibility)
     *
     * Route: GET /user/id/{id}
     */
    public function showById($id): void {
        // Mock user data (TODO: fetch from database by ID)
        $user = [
            'id' => $id,
            'slug' => 'user-' . $id,
            'name' => 'User #' . $id,
            'email' => 'user' . $id . '@example.com',
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($this->wantsJson()) {
            $this->success($user);
        }

        $this->view('under_construction', ['user' => $user]);
    }

    /**
     * Register new user
     *
     * Route: POST /register
     */
    public function register(): void {
        // Validate input
        $validation = $this->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
            'name' => 'required|min:2|max:100'
        ]);

        if ($validation !== true) {
            $this->validationError($validation);
        }

        // Sanitize input
        $email = $this->request->sanitize('email', 'email');
        $name = $this->request->sanitize('name', 'string');
        $password = $this->request->input('password');

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // TODO: Save to database
        // $userId = Database::insert('users', [...]);

        // Mock response
        $userId = rand(1000, 9999);

        $this->success(
            [
                'id' => $userId,
                'email' => $email,
                'name' => $name
            ],
            'User registered successfully',
            201
        );
    }

    /**
     * User profile (requires auth middleware)
     *
     * Route: GET /profile
     * Middleware: ['auth']
     */
    public function profile(): void {
        // User is authenticated (verified by AuthMiddleware)
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $this->abort(401, 'Unauthorized');
        }

        // Fetch user from database
        $user = User::find($userId);
        
        if (!$user) {
            $this->abort(404, 'User not found');
        }

        // Convert to array for view
        $userArray = $user->toArray();
        if (is_object($userArray)) {
            $userArray = json_decode(json_encode($userArray), true);
        }
        if (!is_array($userArray)) {
            $userArray = [];
        }

        if ($this->wantsJson()) {
            $this->success($userArray);
        }

        // Render profile view
        $this->view('user/profile', ['profileUser' => $userArray]);
    }

    /**
     * Show profile edit form (requires auth middleware)
     *
     * Route: GET /profile/edit
     * Middleware: ['auth']
     */
    public function editProfile(): void {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $this->abort(401, 'Unauthorized');
        }

        // Fetch user from database
        $user = User::find($userId);
        
        if (!$user) {
            $this->abort(404, 'User not found');
        }

        // Convert to array for view
        $userArray = $user->toArray();
        if (is_object($userArray)) {
            $userArray = json_decode(json_encode($userArray), true);
        }
        if (!is_array($userArray)) {
            $userArray = [];
        }

        // Render edit profile view
        $this->view('user/edit', ['profileUser' => $userArray]);
    }

    /**
     * Update user profile (requires auth middleware)
     *
     * Route: POST /profile/update or PUT /profile/update
     * Middleware: ['auth']
     */
    public function updateProfile(): void {
        // Check CSRF token
        if (!CSRF::verify()) {
            if ($this->wantsJson()) {
                $this->error('CSRF token verification failed', null, 403);
            }
            $this->abort(403, 'CSRF token verification failed');
        }

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $this->abort(401, 'Unauthorized');
        }

        // Fetch user from database
        $user = User::find($userId);
        
        if (!$user) {
            $this->abort(404, 'User not found');
        }

        // Validate input
        $validation = $this->validate([
            'username' => 'required|minLength:3|maxLength:30',
            'email' => 'required|email',
            'first_name' => 'maxLength:50',
            'last_name' => 'maxLength:50',
            // newsletter is optional checkbox - don't validate if not present
        ]);

        if ($validation !== true) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }

        // Check if email/username already exists (excluding current user)
        $email = $this->request->input('email');
        $username = $this->request->input('username');
        
        $errors = [];
        $existingUserByEmail = User::findByEmail($email);
        if ($existingUserByEmail && $existingUserByEmail->id != $userId) {
            $errors['email'] = ['Email already exists'];
        }
        
        $existingUserByUsername = User::findByUsername($username);
        if ($existingUserByUsername && $existingUserByUsername->id != $userId) {
            $errors['username'] = ['Username already exists'];
        }
        
        if (!empty($errors)) {
            if ($this->wantsJson()) {
                $this->validationError($errors);
            }
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }

        try {
            // Begin transaction
            Database::beginTransaction();
            
            // Update user (only fields user can edit - no status, no roles)
            $user->username = Security::sanitize($username, 'string');
            $user->email = Security::sanitize($email, 'email');
            $user->first_name = Security::sanitize($this->request->input('first_name'), 'string') ?: null;
            $user->last_name = Security::sanitize($this->request->input('last_name'), 'string') ?: null;
            $user->newsletter = $this->request->has('newsletter');
            
            // Update password if provided
            if ($this->request->has('password') && !empty($this->request->input('password'))) {
                $password = $this->request->input('password');
                $passwordStrengthErrors = Security::validatePasswordStrength($password, 8);
                if (!empty($passwordStrengthErrors)) {
                    Database::rollback();
                    $errors = ['password' => $passwordStrengthErrors];
                    if ($this->wantsJson()) {
                        $this->validationError($errors);
                    }
                    Form::flashErrors($errors);
                    Form::flashOld($this->request->all());
                    $this->redirectBack();
                    return;
                }
                $user->updatePassword($password);
            }

            // Handle avatar upload/delete
            $this->handleAvatarUpload($user);

            $user->save();
            
            // Commit transaction
            Database::commit();
            
            // Clear cache
            if (class_exists('Cache')) {
                Cache::forget("user_{$user->id}_roles");
            }
            
            // Log audit (if AuditLog exists)
            if (class_exists('AuditLog')) {
                try {
                    $oldUser = User::find($userId); // Get fresh data for comparison
                    AuditLog::log('user.updated', 'User', $user->id, $oldUser ? $oldUser->toArray() : null, $user->toArray());
                } catch (Exception $e) {
                    // Audit log failed, but continue
                }
            }
            
            if (class_exists('Logger')) {
                Logger::info('User profile updated', ['user_id' => $user->id, 'username' => $user->username]);
            }

            if ($this->wantsJson()) {
                $this->success($user->toArray(), 'Profile updated successfully');
            }

            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/profile");
        } catch (Exception $e) {
            // Rollback transaction on error
            Database::rollback();
            
            if ($this->wantsJson()) {
                $this->error('Failed to update profile: ' . $e->getMessage(), null, 500);
            }
            
            $errors = ['general' => ['Failed to update profile: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Handle avatar upload/delete for user profile
     */
    private function handleAvatarUpload(User $user): void {
        // Check if avatar should be deleted
        if ($this->request->has('delete_avatar') && $this->request->input('delete_avatar') == '1') {
            $this->deleteAvatarFile($user->avatar);
            $user->avatar = null;
            return;
        }

        // Check if new avatar is being uploaded
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            // Load UploadManager if not already loaded
            if (!class_exists('UploadManager')) {
                require_once __DIR__ . '/../../core/services/UploadManager.php';
            }

            try {
                $uploader = new UploadManager('storage/uploads/avatars/');
                $result = $uploader->upload('avatar', [
                    'maxSize' => 2 * 1024 * 1024, // 2MB
                    'allowedTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                    'prefix' => 'avatar_'
                ]);

                if ($result) {
                    // Delete old avatar if exists
                    if (!empty($user->avatar)) {
                        $this->deleteAvatarFile($user->avatar);
                    }
                    
                    // Update user avatar with new URL
                    $user->avatar = $result['url'];
                } else {
                    // Upload failed, add errors to form
                    $errors = $uploader->getErrors();
                    if (!empty($errors)) {
                        Form::flashErrors(['avatar' => $errors]);
                    }
                }
            } catch (Exception $e) {
                error_log('Avatar upload error: ' . $e->getMessage());
                Form::flashErrors(['avatar' => ['Failed to upload avatar: ' . $e->getMessage()]]);
            }
        }
    }

    /**
     * Delete avatar file from filesystem
     */
    private function deleteAvatarFile(?string $avatarPath): void {
        if (empty($avatarPath)) {
            return;
        }

        // Extract file path from URL
        // Avatar path might be like "/storage/uploads/avatars/avatar_xxx.jpg"
        $filePath = str_replace('/storage/uploads/avatars/', '', $avatarPath);
        $projectRoot = dirname(__DIR__, 2);
        $fullPath = $projectRoot . '/storage/uploads/avatars/' . basename($filePath);

        // Delete file if it exists
        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    /**
     * User dashboard (requires auth middleware)
     *
     * Route: GET /dashboard
     * Middleware: ['auth']
     */
    public function dashboard(): void {
        $userId = $_SESSION['user_id'] ?? null;

        $data = [
            'user_id' => $userId,
            'stats' => [
                'posts' => 42,
                'followers' => 128,
                'following' => 64
            ]
        ];

        if ($this->wantsJson()) {
            $this->success($data);
        }

        $this->view('under_construction', $data);
    }

    /**
     * API endpoint for user profile
     *
     * Route: GET /api/user/profile
     * Middleware: ['cors', 'auth']
     */
    public function apiProfile(): void {
        $userId = $_SESSION['user_id'] ?? null;

        $user = [
            'id' => $userId,
            'name' => 'API User',
            'email' => 'api@example.com',
            'api_token' => 'mock_token_12345'
        ];

        $this->success($user);
    }
}


if (!\class_exists('UserController', false) && !\interface_exists('UserController', false) && !\trait_exists('UserController', false)) {
    \class_alias(__NAMESPACE__ . '\\UserController', 'UserController');
}
