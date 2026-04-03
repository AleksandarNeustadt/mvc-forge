<?php

namespace App\Controllers;


use App\Core\database\Database;
use App\Core\database\DatabaseBuilder;
use App\Core\http\Input;
use App\Core\logging\Logger;
use App\Core\mvc\Controller;
use App\Core\security\CSRF;
use App\Core\security\Security;
use App\Core\services\EmailService;
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

/**
 * AuthController - Handles authentication (login, register, logout)
 */
class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLogin(): void
    {
        $this->view('login');
    }

    /**
     * Handle login form submission
     */
    public function login(): void
    {
        // Validate input
        $errors = Security::validateAll($_POST, [
            'email' => 'required|email',
            'password' => 'required|minLength:6',
        ]);

        if (!empty($errors)) {
            Form::redirectBack($errors, $_POST);
        }

        // Sanitize input
        $data = [
            'email' => Security::sanitize($_POST['email'] ?? '', 'email'),
            'password' => $_POST['password'] ?? '', // Don't sanitize password
            'remember' => isset($_POST['remember']),
            'ip' => Input::ip(),
            'user_agent' => Input::userAgent(),
            'csrf_valid' => CSRF::verify(),
        ];

        // Find user by email
        $user = User::findByEmail($data['email']);
        
        if (!$user) {
            $errors = ['email' => ['Email or password is incorrect']];
            Form::redirectBack($errors, $this->request->all());
        }
        
        // Check account status BEFORE password verification (prevent timing attacks)
        if ($user->isBanned()) {
            Logger::warning('Login attempt for banned user', ['user_id' => $user->id, 'email' => $data['email'], 'ip' => $data['ip']]);
            $errors = ['email' => ['This account has been banned.']];
            Form::redirectBack($errors, $this->request->all());
        }
        
        if ($user->isPending()) {
            Logger::info('Login attempt for pending user', ['user_id' => $user->id, 'email' => $data['email'], 'ip' => $data['ip']]);
            $errors = ['email' => ['Your account is pending approval. Please wait for admin approval.']];
            Form::redirectBack($errors, $this->request->all());
        }
        
        // Verify password
        if (!$user->verifyPassword($data['password'])) {
            // Increment failed login attempts
            $user->incrementFailedLoginAttempts();
            
            Logger::warning('Failed login attempt', ['user_id' => $user->id, 'email' => $data['email'], 'ip' => $data['ip']]);
            $errors = ['email' => ['Email or password is incorrect']];
            Form::redirectBack($errors, $this->request->all());
        }
        
        // Check if account is locked
        if ($user->isLocked()) {
            Logger::warning('Login attempt for locked account', ['user_id' => $user->id, 'email' => $data['email'], 'ip' => $data['ip']]);
            $errors = ['email' => ['Account is temporarily locked due to too many failed login attempts. Please try again later.']];
            Form::redirectBack($errors, $this->request->all());
        }
        
        // Reset failed login attempts on successful login
        $user->resetFailedLoginAttempts();
        
        // Update last login
        $user->updateLastLogin($data['ip']);
        
        // Create session - store user data for header display
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_username'] = $user->username;
        $_SESSION['user_first_name'] = $user->first_name ?? null;
        $_SESSION['user_last_name'] = $user->last_name ?? null;
        $_SESSION['user_avatar'] = $user->avatar ?? null;
        
        // Store IP and user agent for session hijacking protection
        $_SESSION['login_ip'] = $data['ip'];
        $_SESSION['login_user_agent'] = $data['user_agent'];
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID for security (ALWAYS, not just for remember me)
        session_regenerate_id(true);
        
        // Remember me (extend session lifetime)
        if ($data['remember']) {
            // Extend session cookie lifetime to 30 days using setcookie
            // Cannot use ini_set() after session_start()
            $sessionLifetime = 30 * 24 * 60 * 60; // 30 days in seconds

            // Preserve SameSite/Secure/HttpOnly flags from bootstrap session policy.
            $cookieParams = session_get_cookie_params();
            setcookie(
                session_name(),
                session_id(),
                [
                    'expires' => time() + $sessionLifetime,
                    'path' => $cookieParams['path'] ?? '/',
                    'domain' => $cookieParams['domain'] ?? '',
                    'secure' => (bool) ($cookieParams['secure'] ?? false),
                    'httponly' => true,
                    'samesite' => $cookieParams['samesite'] ?? 'Lax',
                ]
            );
        }
        
        // Log successful login
        AuditLog::log('user.login', 'User', $user->id, null, ['ip' => $data['ip'], 'user_agent' => $data['user_agent']]);
        Logger::info('User logged in', ['user_id' => $user->id, 'username' => $user->username, 'ip' => $data['ip']]);
        
        // Redirect to home
        global $router;
        $lang = $router->lang ?? 'sr';
        $this->redirect("/{$lang}");
    }

    /**
     * Show registration form
     */
    public function showRegister(): void
    {
        // Set form start time for anti-spam timing validation
        Security::setFormStartTime('register');
        $this->view('register');
    }

    /**
     * Handle registration form submission
     */
    public function register(): void
    {
        // SPAM PREVENTION: Check honeypot field (should be empty)
        $honeypot = $_POST['website_url'] ?? '';
        if (!Security::validateHoneypot($honeypot)) {
            // Bot detected - silently fail (don't reveal the honeypot)
            Logger::warning('Spam registration attempt detected (honeypot)', ['ip' => Input::ip()]);
            // Redirect without error message to avoid revealing the honeypot
            Form::redirectBack([], $_POST);
            return;
        }

        // SPAM PREVENTION: Validate form timing (must take at least 3 seconds)
        if (!Security::validateFormTiming('register', 3)) {
            Logger::warning('Spam registration attempt detected (timing)', ['ip' => Input::ip()]);
            $errors = ['_spam' => ['Molimo popunite formu pažljivo.']];
            Form::redirectBack($errors, $_POST);
            return;
        }

        // Validate input
        $errors = Security::validateAll($_POST, [
            'first_name' => 'required|minLength:2|maxLength:50',
            'last_name' => 'required|minLength:2|maxLength:50',
            'username' => 'required|minLength:3|maxLength:30|alphanumeric',
            'email' => 'required|email',
            'password' => 'required', // minLength is checked in validatePasswordStrength
            'password_confirmation' => 'required',
            'terms' => 'required',
        ]);
        
        // SPAM PREVENTION: Check for disposable email domains
        $email = $_POST['email'] ?? '';
        if (!empty($email) && Security::isDisposableEmail($email)) {
            $errors['email'] = array_merge($errors['email'] ?? [], ['Korišćenje privremenih email adresa nije dozvoljeno.']);
        }
        
        // SPAM PREVENTION: Check if username appears to be a random string
        $username = $_POST['username'] ?? '';
        if (!empty($username) && Security::isRandomUsername($username)) {
            $errors['username'] = array_merge($errors['username'] ?? [], ['Korisničko ime ne sme biti nasumični string. Molimo koristite smisleno ime.']);
        }
        
        // Validate password strength (includes minLength check)
        $password = $_POST['password'] ?? '';
        $passwordStrengthErrors = Security::validatePasswordStrength($password, 8);
        if (!empty($passwordStrengthErrors)) {
            $errors['password'] = array_merge($errors['password'] ?? [], $passwordStrengthErrors);
        }

        // Check password confirmation
        if (($_POST['password'] ?? '') !== ($_POST['password_confirmation'] ?? '')) {
            $errors['password_confirmation'][] = 'Lozinke se ne poklapaju.';
        }

        if (!empty($errors)) {
            Form::redirectBack($errors, $_POST);
        }

        // Sanitize input
        $data = [
            'first_name' => Security::sanitize($_POST['first_name'] ?? '', 'string'),
            'last_name' => Security::sanitize($_POST['last_name'] ?? '', 'string'),
            'username' => Security::sanitize($_POST['username'] ?? '', 'alphanumeric'),
            'email' => Security::sanitize($_POST['email'] ?? '', 'email'),
            'password' => $_POST['password'] ?? '',
            'password_hash' => Security::hashPassword($_POST['password'] ?? ''),
            'newsletter' => isset($_POST['newsletter']),
            'ip' => Input::ip(),
            'csrf_valid' => CSRF::verify(),
        ];

        // Check if username/email already exists
        if (User::emailExists($data['email'])) {
            $errors = ['email' => ['Email already exists']];
            Form::redirectBack($errors, $_POST);
        }
        
        if (User::usernameExists($data['username'])) {
            $errors = ['username' => ['Username already exists']];
            Form::redirectBack($errors, $_POST);
        }

        // Handle avatar upload
        $avatarPath = null;
        if (Input::hasFile('avatar')) {
            $uploadDir = dirname(__DIR__, 2) . '/storage/avatars';
            
            // Ensure directory exists
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            
            $avatarResult = Form::handleUpload('avatar', $uploadDir, [
                'maxSize' => 2 * 1024 * 1024, // 2MB
                'allowedTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                'filename' => $data['username'] . '_' . time(),
            ]);

            if (isset($avatarResult['error'])) {
                Form::redirectBack(['avatar' => [$avatarResult['error']]], $_POST);
            }
            
            if (isset($avatarResult['path'])) {
                $avatarPath = $avatarResult['path'];
            }
        }

        try {
            // Begin transaction
            Database::beginTransaction();
            
            // Create user
            $user = User::createUser([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'], // Will be hashed in createUser()
                'avatar' => $avatarPath,
                'newsletter' => $data['newsletter'],
                'status' => 'pending', // Require approval by default
            ]);
            
            // Save initial password to history (for password reuse prevention)
            try {
                $user->savePasswordToHistory($user->password_hash);
            } catch (Exception $e) {
                // Ignore - password history table might not exist yet
                Logger::warning('Failed to save initial password to history', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
            
            // Commit transaction
            Database::commit();
            
            // Generate email verification token (if columns exist)
            $columns = DatabaseBuilder::getTableColumns('users');
            $columnNames = array_column($columns, 'name');
            
            Logger::debug('Checking email verification columns', [
                'columns_found' => $columnNames,
                'has_token_column' => in_array('email_verification_token', $columnNames),
                'has_expires_column' => in_array('email_verification_expires_at', $columnNames)
            ]);
            
            if (in_array('email_verification_token', $columnNames) && in_array('email_verification_expires_at', $columnNames)) {
                $verificationToken = Security::generateToken(32);
                $user->email_verification_token = $verificationToken;
                $user->email_verification_expires_at = time() + (24 * 60 * 60); // 24 hours
                $user->save();
                
                Logger::info('Email verification token generated', [
                    'user_id' => $user->id,
                    'token_preview' => substr($verificationToken, 0, 8) . '...'
                ]);
            } else {
                $verificationToken = null;
                Logger::warning('Email verification columns not found in database', [
                    'user_id' => $user->id,
                    'available_columns' => $columnNames
                ]);
            }
            
            // Log audit
            AuditLog::log('user.created', 'User', $user->id, null, $user->toArray());
            Logger::info('User registered', ['user_id' => $user->id, 'username' => $user->username, 'email' => $user->email]);
            
            // Send verification email
            if ($verificationToken) {
                Logger::info('Attempting to send verification email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'token_preview' => substr($verificationToken, 0, 8) . '...'
                ]);
                
                $emailSent = EmailService::sendVerificationEmail($user, $verificationToken);
                
                if ($emailSent) {
                    Logger::info('Verification email sent successfully', ['user_id' => $user->id, 'email' => $user->email]);
                } else {
                    Logger::error('Failed to send verification email', ['user_id' => $user->id, 'email' => $user->email]);
                }
            } else {
                Logger::warning('Email verification token not generated - cannot send email', [
                    'user_id' => $user->id,
                    'reason' => 'Email verification columns not found in database'
                ]);
            }
            
            // Redirect to login with success message
            global $router;
            $lang = $router->lang ?? 'sr';
            
            // Flash success message
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['registration_success'] = 'Registration successful! Please wait for admin approval.';
            
            $this->redirect("/{$lang}/login");
        } catch (Exception $e) {
            // Rollback transaction on error
            Database::rollback();
            
            Logger::error('Registration failed', ['error' => $e->getMessage(), 'email' => $data['email']]);
            
            $errors = ['general' => ['Registration failed. Please try again.']];
            Form::redirectBack($errors, $_POST);
        }
    }

    /**
     * Handle logout
     */
    public function logout(): void
    {
        // Verify CSRF token
        if (!CSRF::verify($this->request->input('_csrf_token'))) {
            http_response_code(403);
            echo 'CSRF token validation failed';
            exit;
        }

        // Destroy session
        if (session_status() === PHP_SESSION_ACTIVE) {
            $cookieParams = session_get_cookie_params();
            session_unset();
            session_destroy();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 3600,
                    'path' => $cookieParams['path'] ?? '/',
                    'domain' => $cookieParams['domain'] ?? '',
                    'secure' => (bool) ($cookieParams['secure'] ?? false),
                    'httponly' => true,
                    'samesite' => $cookieParams['samesite'] ?? 'Lax',
                ]
            );
        }

        // Regenerate session
        session_start();
        session_regenerate_id(true);

        // Redirect to home
        global $router;
        $lang = $router->lang ?? 'sr';
        $this->redirect("/{$lang}");
    }

    /**
     * Show forgot password form
     */
    public function showForgotPassword(): void
    {
        $this->view('forgot_password');
    }

    /**
     * Handle forgot password form submission
     */
    public function forgotPassword(): void
    {
        $errors = Security::validateAll($_POST, [
            'email' => 'required|email',
        ]);

        if (!empty($errors)) {
            Form::redirectBack($errors, $_POST);
        }

        $email = Security::sanitize($_POST['email'] ?? '', 'email');
        
        // Find user by email
        $user = User::findByEmail($email);
        
        // Always show success message (prevent email enumeration)
        // But only process if user exists
        if ($user) {
            try {
                // Generate reset token
                $resetToken = Security::generateToken(32);
                $resetTokenExpiry = time() + (60 * 60); // 1 hour from now
                
                // Store token in database
                // Check if columns exist first (backward compatibility)
                $columns = DatabaseBuilder::getTableColumns('users');
                $columnNames = array_column($columns, 'name');
                
                $updateData = [];
                if (in_array('password_reset_token', $columnNames)) {
                    $updateData['password_reset_token'] = $resetToken;
                }
                if (in_array('password_reset_expires_at', $columnNames)) {
                    $updateData['password_reset_expires_at'] = $resetTokenExpiry;
                }
                
                if (!empty($updateData)) {
                    Database::table('users')
                        ->where('id', $user->id)
                        ->update($updateData);
                }
                
                // Log audit
                AuditLog::log('user.password_reset_requested', 'User', $user->id, null, ['ip' => Input::ip()]);
                Logger::info('Password reset requested', ['user_id' => $user->id, 'email' => $email]);
                
                // Send email with reset link
                EmailService::sendPasswordResetEmail($user, $resetToken);
            } catch (Exception $e) {
                Logger::error('Password reset failed', ['error' => $e->getMessage(), 'email' => $email]);
            }
        }
        
        // Always show success (prevent email enumeration)
        global $router;
        $lang = $router->lang ?? 'sr';
        
        // Flash success message
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['password_reset_sent'] = 'If the email exists, a password reset link has been sent.';
        
        $this->redirect("/{$lang}/login");
    }
    
    /**
     * Verify email with token
     */
    public function verifyEmail(string $token): void
    {
        // Find user by verification token
        $columns = DatabaseBuilder::getTableColumns('users');
        $columnNames = array_column($columns, 'name');
        
        if (!in_array('email_verification_token', $columnNames)) {
            // Email verification not set up yet
            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/login");
            return;
        }
        
        $user = Database::table('users')
            ->where('email_verification_token', $token)
            ->where('email_verification_expires_at', '>', time())
            ->first();
        
        if (!$user) {
            // Invalid or expired token
            global $router;
            $lang = $router->lang ?? 'sr';
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['verification_error'] = 'Invalid or expired verification token.';
            
            $this->redirect("/{$lang}/login");
            return;
        }
        
        // Verify email
        $userModel = User::find($user['id']);
        if ($userModel) {
            $userModel->verifyEmail();
            
            AuditLog::log('user.email_verified', 'User', $userModel->id, null, ['ip' => Input::ip()]);
            Logger::info('Email verified', ['user_id' => $userModel->id, 'email' => $userModel->email]);
            
            global $router;
            $lang = $router->lang ?? 'sr';
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['verification_success'] = 'Email verified successfully! You can now log in.';
            
            $this->redirect("/{$lang}/login");
        }
    }
}


if (!\class_exists('AuthController', false) && !\interface_exists('AuthController', false) && !\trait_exists('AuthController', false)) {
    \class_alias(__NAMESPACE__ . '\\AuthController', 'AuthController');
}
