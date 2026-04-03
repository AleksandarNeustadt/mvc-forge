<?php

namespace App\Controllers;


use App\Core\mvc\Controller;use BadMethodCallException;
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

// mvc/controllers/ResourceController.php

/**
 * Example CRUD Controller
 *
 * This demonstrates how to use the Request class for CRUD operations.
 * Later, security features (CSRF, authentication, authorization) can be added.
 */
class ResourceController extends Controller {

    /**
     * GET /resource - List all resources
     */
    public function index(): void {
        // Example: Get query parameters for filtering/pagination
        $page = $this->request->query('page', 1);
        $limit = $this->request->query('limit', 10);
        $search = $this->request->query('search', '');

        // TODO: Fetch from database
        $resources = [
            ['id' => 1, 'name' => 'Resource 1'],
            ['id' => 2, 'name' => 'Resource 2'],
        ];

        // Return JSON if requested, otherwise render view
        if ($this->wantsJson()) {
            $this->success($resources, 'Resources retrieved successfully');
        }

        $this->view('resources/index', ['resources' => $resources]);
    }

    /**
     * GET /resource/{id} - Show single resource
     */
    public function show(int $id): void {
        // TODO: Fetch from database
        $resource = ['id' => $id, 'name' => "Resource {$id}"];

        if (!$resource) {
            $this->abort(404, 'Resource not found');
        }

        if ($this->wantsJson()) {
            $this->success($resource);
        }

        $this->view('resources/show', ['resource' => $resource]);
    }

    /**
     * POST /resource - Create new resource
     */
    public function store(): void {
        // Validate request
        $validation = $this->validate([
            'name' => 'required|min:3|max:255',
            'email' => 'required|email',
            'age' => 'numeric'
        ]);

        if ($validation !== true) {
            $this->validationError($validation);
        }

        // Sanitize input
        $name = $this->request->sanitize('name', 'string');
        $email = $this->request->sanitize('email', 'email');
        $age = $this->request->sanitize('age', 'int');

        // Get all validated data
        $data = $this->request->only(['name', 'email', 'age']);

        // TODO: Save to database
        $newResource = array_merge(['id' => rand(1, 1000)], $data);

        $this->success($newResource, 'Resource created successfully', 201);
    }

    /**
     * PUT/PATCH /resource/{id} - Update resource
     */
    public function update(int $id): void {
        // Validate request
        $validation = $this->validate([
            'name' => 'min:3|max:255',
            'email' => 'email',
            'age' => 'numeric'
        ]);

        if ($validation !== true) {
            $this->validationError($validation);
        }

        // TODO: Check if resource exists
        $resource = ['id' => $id, 'name' => 'Old name'];

        if (!$resource) {
            $this->abort(404, 'Resource not found');
        }

        // Get only provided fields
        $data = $this->request->only(['name', 'email', 'age']);

        // TODO: Update in database
        $updatedResource = array_merge($resource, $data);

        $this->success($updatedResource, 'Resource updated successfully');
    }

    /**
     * DELETE /resource/{id} - Delete resource
     */
    public function destroy(int $id): void {
        // TODO: Check if resource exists
        $resource = ['id' => $id];

        if (!$resource) {
            $this->abort(404, 'Resource not found');
        }

        // TODO: Delete from database

        $this->success(null, 'Resource deleted successfully');
    }

    /**
     * POST /resource/bulk - Bulk create resources
     */
    public function bulkStore(): void {
        // Get all input
        $items = $this->request->input('items', []);

        if (empty($items) || !is_array($items)) {
            $this->error('No items provided', null, 400);
        }

        $created = [];
        $errors = [];

        foreach ($items as $index => $item) {
            // Validate each item
            // TODO: Implement batch validation

            // For now, just collect them
            $created[] = array_merge(['id' => rand(1, 1000)], $item);
        }

        $this->success([
            'created' => $created,
            'errors' => $errors
        ], 'Bulk operation completed', 201);
    }

    /**
     * Example: File upload handling
     */
    public function upload(): void {
        if (!$this->request->hasFile('file')) {
            $this->error('No file uploaded', null, 400);
        }

        $file = $this->request->file('file');

        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowedTypes)) {
            $this->error('Invalid file type. Only JPEG, PNG, and GIF are allowed');
        }

        if ($file['size'] > $maxSize) {
            $this->error('File too large. Maximum size is 5MB');
        }

        // TODO: Move uploaded file to storage
        // $destination = __DIR__ . '/../../storage/uploads/' . basename($file['name']);
        // move_uploaded_file($file['tmp_name'], $destination);

        $this->success([
            'filename' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type']
        ], 'File uploaded successfully', 201);
    }
}


if (!\class_exists('ResourceController', false) && !\interface_exists('ResourceController', false) && !\trait_exists('ResourceController', false)) {
    \class_alias(__NAMESPACE__ . '\\ResourceController', 'ResourceController');
}
