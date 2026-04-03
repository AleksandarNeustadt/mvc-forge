<?php

namespace App\Core\services;


use App\Core\config\Env;
use App\Core\logging\Logger;use BadMethodCallException;
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
 * Email Service
 * 
 * Handles email sending using PHPMailer with SMTP support
 * 
 * Usage:
 *   EmailService::sendVerificationEmail($user, $token);
 *   EmailService::sendPasswordResetEmail($user, $token);
 *   EmailService::send('to@example.com', 'Subject', 'Body', 'html');
 */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\SMTP;

class EmailService
{
    private static ?PHPMailer $mailer = null;
    private static bool $enabled = true;

    /**
     * Initialize PHPMailer instance
     */
    private static function init(): PHPMailer
    {
        if (self::$mailer !== null) {
            return self::$mailer;
        }

        // Check if PHPMailer is available
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            Logger::error('PHPMailer class not found. Make sure composer install was run.');
            self::$enabled = false;
            throw new Exception('PHPMailer not available');
        }

        self::$mailer = new PHPMailer(true);

        try {
            // Server settings
            $useSmtp = Env::get('MAIL_USE_SMTP', false);
            
            Logger::debug('EmailService init', [
                'use_smtp' => $useSmtp,
                'mail_host' => Env::get('MAIL_HOST', 'not set'),
                'mail_port' => Env::get('MAIL_PORT', 'not set')
            ]);
            
            if ($useSmtp) {
                // SMTP configuration
                self::$mailer->isSMTP();
                self::$mailer->Host = Env::get('MAIL_HOST', 'localhost');
                self::$mailer->Port = (int) Env::get('MAIL_PORT', 1025);
                
                // For MailHog (localhost:1025), no encryption or auth needed
                $encryption = Env::get('MAIL_ENCRYPTION', '');
                if (!empty($encryption) && $encryption !== 'false') {
                    self::$mailer->SMTPSecure = $encryption; // tls or ssl
                }
                // If encryption is empty or 'false', leave it unset (no encryption for MailHog)
                
                $mailAuth = Env::get('MAIL_AUTH', false);
                if ($mailAuth && $mailAuth !== 'false') {
                    self::$mailer->SMTPAuth = true;
                    self::$mailer->Username = Env::get('MAIL_USERNAME', '');
                    self::$mailer->Password = Env::get('MAIL_PASSWORD', '');
                } else {
                    self::$mailer->SMTPAuth = false;
                }
                
                // SMTP debug output is disabled to prevent "headers already sent" errors
                // Debug information is logged via Logger::debug() instead
                self::$mailer->SMTPDebug = SMTP::DEBUG_OFF;
                
                // Timeout settings for MailHog
                self::$mailer->Timeout = 10;
            } else {
                // Use PHP mail() function (fallback)
                self::$mailer->isMail();
            }

            // Sender
            self::$mailer->setFrom(
                Env::get('MAIL_FROM_ADDRESS', 'noreply@aleksandar.pro'),
                Env::get('MAIL_FROM_NAME', Env::get('BRAND_NAME', 'aleksandar.pro'))
            );

            // Character set
            self::$mailer->CharSet = 'UTF-8';
            self::$mailer->Encoding = 'base64';

        } catch (PHPMailerException $e) {
            Logger::error('EmailService initialization failed', ['error' => $e->getMessage()]);
            self::$enabled = false;
        }

        return self::$mailer;
    }

    /**
     * Send email verification email
     */
    public static function sendVerificationEmail($user, string $token): bool
    {
        if (!self::$enabled) {
            Logger::warning('EmailService is disabled, skipping verification email', ['user_id' => $user->id ?? null]);
            return false;
        }

        try {
            $mailer = self::init();
            
            // Reset mailer state
            $mailer->clearAddresses();
            $mailer->clearAttachments();
            $mailer->clearCustomHeaders();
            $mailer->clearReplyTos();

            global $router;
            $lang = $router->lang ?? 'sr';
            $verificationUrl = Env::get('APP_URL', 'https://aleksandar.pro') . "/{$lang}/verify-email/{$token}";

            // Recipient
            $mailer->addAddress($user->email, $user->first_name . ' ' . $user->last_name);

            // Content
            $mailer->isHTML(true);
            $mailer->Subject = __('email.verification.subject', 'Verify Your Email Address');
            
            // Load email template
            $body = self::renderTemplate('verification', [
                'user' => $user,
                'verification_url' => $verificationUrl,
                'token' => $token,
                'expires_in' => '24 hours'
            ]);

            $mailer->Body = $body;
            $mailer->AltBody = strip_tags($body);

            // Send
            Logger::debug('Attempting to send verification email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'to' => $user->email,
                'subject' => $mailer->Subject,
                'smtp_host' => $mailer->Host ?? 'N/A',
                'smtp_port' => $mailer->Port ?? 'N/A'
            ]);
            
            $result = $mailer->send();
            
            if ($result) {
                Logger::info('Verification email sent successfully', ['user_id' => $user->id, 'email' => $user->email]);
            } else {
                Logger::error('Failed to send verification email - send() returned false', [
                    'user_id' => $user->id ?? null,
                    'email' => $user->email ?? null,
                    'error_info' => $mailer->ErrorInfo ?? 'No error info available'
                ]);
            }
            
            return $result;

        } catch (PHPMailerException $e) {
            Logger::error('Failed to send verification email - Exception', [
                'user_id' => $user->id ?? null,
                'email' => $user->email ?? null,
                'exception_message' => $e->getMessage(),
                'error_info' => $mailer->ErrorInfo ?? 'No error info available',
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        } catch (Exception $e) {
            Logger::error('Failed to send verification email - General Exception', [
                'user_id' => $user->id ?? null,
                'email' => $user->email ?? null,
                'exception_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send password reset email
     */
    public static function sendPasswordResetEmail($user, string $token): bool
    {
        if (!self::$enabled) {
            Logger::warning('EmailService is disabled, skipping password reset email', ['user_id' => $user->id ?? null]);
            return false;
        }

        try {
            $mailer = self::init();
            
            // Reset mailer state
            $mailer->clearAddresses();
            $mailer->clearAttachments();
            $mailer->clearCustomHeaders();
            $mailer->clearReplyTos();

            global $router;
            $lang = $router->lang ?? 'sr';
            $resetUrl = Env::get('APP_URL', 'https://aleksandar.pro') . "/{$lang}/reset-password?token={$token}";

            // Recipient
            $mailer->addAddress($user->email, $user->first_name . ' ' . $user->last_name);

            // Content
            $mailer->isHTML(true);
            $mailer->Subject = __('email.password_reset.subject', 'Reset Your Password');
            
            // Load email template
            $body = self::renderTemplate('password-reset', [
                'user' => $user,
                'reset_url' => $resetUrl,
                'token' => $token,
                'expires_in' => '1 hour'
            ]);

            $mailer->Body = $body;
            $mailer->AltBody = strip_tags($body);

            // Send
            $result = $mailer->send();
            
            if ($result) {
                Logger::info('Password reset email sent', ['user_id' => $user->id, 'email' => $user->email]);
            }
            
            return $result;

        } catch (PHPMailerException $e) {
            Logger::error('Failed to send password reset email', [
                'user_id' => $user->id ?? null,
                'email' => $user->email ?? null,
                'error' => $mailer->ErrorInfo ?? $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send welcome email
     */
    public static function sendWelcomeEmail($user): bool
    {
        if (!self::$enabled) {
            Logger::warning('EmailService is disabled, skipping welcome email', ['user_id' => $user->id ?? null]);
            return false;
        }

        try {
            $mailer = self::init();
            
            // Reset mailer state
            $mailer->clearAddresses();
            $mailer->clearAttachments();
            $mailer->clearCustomHeaders();
            $mailer->clearReplyTos();

            // Recipient
            $mailer->addAddress($user->email, $user->first_name . ' ' . $user->last_name);

            // Content
            $mailer->isHTML(true);
            $mailer->Subject = __('email.welcome.subject', 'Welcome to ' . Env::get('BRAND_NAME', 'aleksandar.pro'));
            
            // Load email template
            $body = self::renderTemplate('welcome', [
                'user' => $user
            ]);

            $mailer->Body = $body;
            $mailer->AltBody = strip_tags($body);

            // Send
            $result = $mailer->send();
            
            if ($result) {
                Logger::info('Welcome email sent', ['user_id' => $user->id, 'email' => $user->email]);
            }
            
            return $result;

        } catch (PHPMailerException $e) {
            Logger::error('Failed to send welcome email', [
                'user_id' => $user->id ?? null,
                'email' => $user->email ?? null,
                'error' => $mailer->ErrorInfo ?? $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send generic email
     */
    public static function send(string $to, string $subject, string $body, string $type = 'html', ?string $toName = null): bool
    {
        if (!self::$enabled) {
            Logger::warning('EmailService is disabled, skipping email', ['to' => $to]);
            return false;
        }

        try {
            $mailer = self::init();
            
            // Reset mailer state
            $mailer->clearAddresses();
            $mailer->clearAttachments();
            $mailer->clearCustomHeaders();
            $mailer->clearReplyTos();

            // Recipient
            $mailer->addAddress($to, $toName ?? '');

            // Content
            $mailer->isHTML($type === 'html');
            $mailer->Subject = $subject;
            $mailer->Body = $body;
            
            if ($type === 'html') {
                $mailer->AltBody = strip_tags($body);
            }

            // Send
            $result = $mailer->send();
            
            if ($result) {
                Logger::info('Email sent', ['to' => $to, 'subject' => $subject]);
            }
            
            return $result;

        } catch (PHPMailerException $e) {
            Logger::error('Failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $mailer->ErrorInfo ?? $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Render email template
     */
    private static function renderTemplate(string $template, array $data = []): string
    {
        $templatePath = dirname(__DIR__, 2) . '/mvc/views/emails/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            Logger::error('Email template not found', ['template' => $template]);
            // Return simple fallback
            return self::getFallbackTemplate($template, $data);
        }

        // Extract data for template
        extract($data);
        
        // Start output buffering
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    /**
     * Get fallback template if file doesn't exist
     */
    private static function getFallbackTemplate(string $template, array $data): string
    {
        $user = $data['user'] ?? null;
        $userName = $user ? ($user->first_name . ' ' . $user->last_name) : 'User';
        
        switch ($template) {
            case 'verification':
                $url = $data['verification_url'] ?? '#';
                return "
                    <h2>Verify Your Email Address</h2>
                    <p>Hello {$userName},</p>
                    <p>Please click the link below to verify your email address:</p>
                    <p><a href=\"{$url}\">{$url}</a></p>
                    <p>This link will expire in 24 hours.</p>
                ";
            
            case 'password-reset':
                $url = $data['reset_url'] ?? '#';
                return "
                    <h2>Reset Your Password</h2>
                    <p>Hello {$userName},</p>
                    <p>You requested to reset your password. Click the link below:</p>
                    <p><a href=\"{$url}\">{$url}</a></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                ";
            
            case 'welcome':
                $brandName = Env::get('BRAND_NAME', 'aleksandar.pro');
                return "
                    <h2>Welcome to {$brandName}!</h2>
                    <p>Hello {$userName},</p>
                    <p>Thank you for joining us!</p>
                ";
            
            default:
                return "<p>Email content</p>";
        }
    }
}


if (!\class_exists('EmailService', false) && !\interface_exists('EmailService', false) && !\trait_exists('EmailService', false)) {
    \class_alias(__NAMESPACE__ . '\\EmailService', 'EmailService');
}
