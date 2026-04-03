<?php

namespace App\Controllers;


use App\Core\http\Input;
use App\Core\logging\Logger;
use App\Core\mvc\Controller;
use App\Core\security\CSRF;
use App\Core\security\Security;
use App\Core\view\Form;
use App\Models\ContactMessage;
use App\Models\Page;
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

// mvc/controllers/MainController.php

class MainController extends Controller {

    /**
     * Homepage - checks for homepage page from Page Manager first
     *
     * Route: GET /
     */
    public function home(): void {
        // Access language from global router if needed
        global $router;
        $lang = $router->lang ?? 'sr';

        // First, check if there's an active homepage page in the database
        // This allows Page Manager to override the default under construction page
        if (class_exists('Page')) {
            Logger::debug("MainController::home() - Checking for homepage page in database for language: {$lang}");
            
            // Get homepage for current language
            $homepagePage = Page::findByRoute('/', $lang);
            
            if ($homepagePage && $homepagePage->is_active && ($homepagePage->application ?? null) === 'homepage') {
                Logger::debug("MainController::home() - Found homepage page: ID={$homepagePage->id}, title={$homepagePage->title}");
                Logger::debug("MainController::home() - Delegating to PageController");
                $pageController = new PageController();
                $pageController->show();
                return;
            } else {
                Logger::debug("MainController::home() - No homepage page found for language {$lang}, showing under construction");
            }
        }

        // If no homepage page found or not configured, show under construction
        if ($this->wantsJson()) {
            $this->success([
                'page' => 'home',
                'status' => 'under_construction',
                'lang' => $lang
            ]);
        }

        // Render under construction view
        $this->view('under_construction');
    }

    /**
     * API status endpoint
     *
     * Route: GET /api/status
     * Middleware: ['cors']
     */
    public function apiStatus(): void {
        $this->success([
            'status' => 'online',
            'version' => '1.0.0',
            'timestamp' => time()
        ]);
    }

    /**
     * Search endpoint with optional query parameter
     *
     * Route: GET /search/{query?}
     */
    public function search($query = null): void {
        // Get query from URL parameter or request query string
        if (!$query) {
            $query = $this->request->query('q', '');
        }

        $results = [];

        if ($query) {
            // Mock search results (TODO: implement actual search)
            $results = [
                ['title' => 'Result 1 for: ' . $query, 'url' => '/result/1'],
                ['title' => 'Result 2 for: ' . $query, 'url' => '/result/2'],
                ['title' => 'Result 3 for: ' . $query, 'url' => '/result/3'],
            ];
        }

        if ($this->wantsJson()) {
            $this->success([
                'query' => $query,
                'results' => $results,
                'count' => count($results)
            ]);
        }

        $this->view('under_construction', [
            'query' => $query,
            'results' => $results
        ]);
    }

    /**
     * Contact page - handles both GET and POST
     * 
     * SECURITY: Requires authentication, CSRF protection, honeypot, rate limiting, form timing
     *
     * Route: GET|POST /contact
     */
    public function contact(): void {
        // Check if user is authenticated (REQUIRED for POST requests only)
        $isAuthenticated = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
        
        // For POST requests, authentication is required
        if ($this->request->isPost() && !$isAuthenticated) {
            if ($this->wantsJson()) {
                $this->error('Morate biti prijavljeni da biste poslali poruku.', null, 401);
            }
            
            // Redirect to login with return URL
            global $router;
            $lang = $router->lang ?? 'sr';
            // Get current route (could be dynamic from Page Manager)
            $currentRoute = $router->getUri() ?? '/contact';
            $_SESSION['redirect_after_login'] = "/{$lang}{$currentRoute}";
            $this->redirect("/{$lang}/login");
            return;
        }
        
        // For GET requests, show form (disabled for guests, enabled for authenticated users)
        if (!$this->request->isPost()) {
            if ($this->wantsJson()) {
                $this->success(['message' => 'Contact form endpoint', 'authenticated' => $isAuthenticated]);
            }
            $this->view('contact');
            return;
        }
        
        // From here on, we know it's a POST request and user is authenticated

        if ($this->request->isPost()) {
            // SECURITY: Verify CSRF token
            if (!CSRF::verify()) {
                Logger::warning('Contact form CSRF verification failed', ['ip' => Input::ip()]);
                if ($this->wantsJson()) {
                    $this->error('CSRF token verification failed', null, 403);
                }
                Form::redirectBack(['_csrf' => ['CSRF token verification failed. Please refresh the page and try again.']], $_POST);
                return;
            }

            // SECURITY: Check honeypot field (should be empty)
            $honeypot = $_POST['website_url'] ?? '';
            if (!Security::validateHoneypot($honeypot)) {
                // Bot detected - silently fail (don't reveal the honeypot)
                Logger::warning('Spam contact form attempt detected (honeypot)', ['ip' => Input::ip(), 'user_id' => $_SESSION['user_id']]);
                // Redirect without error message to avoid revealing the honeypot
                Form::redirectBack([], $_POST);
                return;
            }

            // SECURITY: Validate form timing (must take at least 5 seconds)
            if (!Security::validateFormTiming('contact', 5)) {
                Logger::warning('Spam contact form attempt detected (timing)', ['ip' => Input::ip(), 'user_id' => $_SESSION['user_id']]);
                $errors = ['_spam' => ['Molimo popunite formu pažljivo.']];
                Form::redirectBack($errors, $_POST);
                return;
            }

            // Validate input
            $errors = Security::validateAll($_POST, [
                'subject' => 'required|minLength:3|maxLength:255',
                'message' => 'required|minLength:10|maxLength:5000',
            ]);

            if (!empty($errors)) {
                if ($this->wantsJson()) {
                    $this->validationError($errors);
                }
                Form::redirectBack($errors, $_POST);
                return;
            }

            // Get current user
            $user = User::find($_SESSION['user_id']);
            if (!$user) {
                if ($this->wantsJson()) {
                    $this->error('User not found', null, 404);
                }
                $this->abort(404, 'User not found');
            }

            // Sanitize input
            $subject = Security::sanitize($_POST['subject'] ?? '', 'string');
            $message = Security::sanitize($_POST['message'] ?? '', 'string');

            // SECURITY: Additional content validation - check for spam patterns
            $spamPatterns = [
                '/\b(viagra|cialis|casino|poker|loan|credit|debt|free money|click here|buy now)\b/i',
                '/\b(http:\/\/|https:\/\/|www\.)/i',  // URLs in message
            ];
            
            foreach ($spamPatterns as $pattern) {
                if (preg_match($pattern, $message)) {
                    Logger::warning('Spam contact form attempt detected (content pattern)', [
                        'ip' => Input::ip(), 
                        'user_id' => $_SESSION['user_id'],
                        'pattern' => $pattern
                    ]);
                    $errors = ['message' => ['Poruka sadrži nedozvoljen sadržaj.']];
                    Form::redirectBack($errors, $_POST);
                    return;
                }
            }

            try {
                // Save contact message to database
                $contactMessage = ContactMessage::create([
                    'user_id' => $user->id,
                    'name' => $user->getFullName() ?: $user->username,
                    'email' => $user->email,
                    'subject' => $subject,
                    'message' => $message,
                    'ip_address' => Input::ip(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    'status' => 'unread'
                ]);

                // Log successful submission
                Logger::info('Contact form submitted', [
                    'user_id' => $user->id,
                    'message_id' => $contactMessage->id,
                    'ip' => Input::ip()
                ]);

                // Regenerate CSRF token after successful submission
                CSRF::regenerate();

                if ($this->wantsJson()) {
                    $this->success(['message_id' => $contactMessage->id], 'Poruka je uspešno poslata!');
                }

                // Redirect with success message
                global $router;
                $lang = $router->lang ?? 'sr';
                // Get current route (could be dynamic from Page Manager)
                $currentRoute = $router->getUri() ?? '/contact';
                Form::flashSuccess('Vaša poruka je uspešno poslata! Odgovorićemo vam u najkraćem mogućem roku.');
                $this->redirect("/{$lang}{$currentRoute}");
                
            } catch (Exception $e) {
                Logger::error('Failed to save contact message', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                if ($this->wantsJson()) {
                    $this->error('Greška pri slanju poruke. Molimo pokušajte ponovo.', null, 500);
                }

                $errors = ['_system' => ['Greška pri slanju poruke. Molimo pokušajte ponovo.']];
                Form::redirectBack($errors, $_POST);
            }
        } else {
            // Show contact form
            if ($this->wantsJson()) {
                $this->success(['message' => 'Contact form endpoint']);
            }

            $this->view('contact');
        }
    }

    /**
     * 404 Not Found page
     */
    public function notFound(): void {
        if ($this->wantsJson()) {
            $this->error('Not Found', null, 404);
        }

        $this->view('404');
    }
}


if (!\class_exists('MainController', false) && !\interface_exists('MainController', false) && !\trait_exists('MainController', false)) {
    \class_alias(__NAMESPACE__ . '\\MainController', 'MainController');
}
