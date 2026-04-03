<?php

namespace App\Core\view;


use App\Core\security\CSRF;
use App\Core\security\Security;use BadMethodCallException;
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
 * FormBuilder Class - Fluent Form Builder with Security
 *
 * Usage:
 *   echo Form::open('/login', 'POST')
 *       ->id('login-form')
 *       ->class('space-y-4')
 *       ->email('email', 'Email adresa')->required()->placeholder('vas@email.com')
 *       ->password('password', 'Lozinka')->required()->minLength(8)
 *       ->checkbox('remember', 'Zapamti me')
 *       ->submit('Prijavi se')
 *       ->close();
 */
class FormBuilder
{
    private string $action;
    private string $method;
    private string $formId = '';
    private string $formClass = '';
    private array $formAttributes = [];
    private bool $hasFiles = false;
    private array $fields = [];
    private array $errors = [];
    private array $oldValues = [];
    private ?string $currentField = null;
    private bool $csrfEnabled = true;

    // Theme colors for styling
    private array $theme = [
        'form' => 'space-y-6',
        'group' => 'space-y-2',
        'label' => 'block text-sm font-medium text-slate-300',
        'input' => 'w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-theme-primary focus:ring-1 focus:ring-theme-primary transition-colors',
        'inputError' => 'border-red-500 focus:border-red-500 focus:ring-red-500',
        'select' => 'w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-lg text-white focus:outline-none focus:border-theme-primary focus:ring-1 focus:ring-theme-primary transition-colors',
        'textarea' => 'w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-theme-primary focus:ring-1 focus:ring-theme-primary transition-colors resize-y',
        'checkbox' => 'w-5 h-5 bg-slate-800 border-slate-600 rounded text-theme-primary focus:ring-theme-primary focus:ring-offset-slate-900',
        'checkboxLabel' => 'ml-3 text-sm text-slate-300',
        'radio' => 'w-5 h-5 bg-slate-800 border-slate-600 text-theme-primary focus:ring-theme-primary focus:ring-offset-slate-900',
        'file' => 'block w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-theme-primary file:text-white hover:file:bg-theme-primary/80 file:cursor-pointer cursor-pointer',
        'filePreview' => 'mt-2 w-24 h-24 rounded-lg object-cover border border-slate-700',
        'submit' => 'w-full py-3 px-6 bg-theme-primary hover:bg-theme-primary/80 text-white font-semibold rounded-lg transition-all duration-300 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-theme-primary focus:ring-offset-2 focus:ring-offset-slate-900',
        'button' => 'py-3 px-6 bg-slate-700 hover:bg-slate-600 text-white font-medium rounded-lg transition-colors',
        'error' => 'text-red-400 text-sm mt-1',
        'help' => 'text-slate-500 text-sm mt-1',
        'required' => 'text-red-400 ml-1',
    ];

    /**
     * Create new FormBuilder instance
     */
    public function __construct(string $action = '', string $method = 'POST')
    {
        $this->action = $action;
        $this->method = strtoupper($method);
        $this->loadOldValues();
        $this->loadErrors();
    }

    /**
     * Static factory method
     */
    public static function make(string $action = '', string $method = 'POST'): self
    {
        return new self($action, $method);
    }

    /**
     * Load old input values from session (for repopulating after validation error)
     */
    private function loadOldValues(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->oldValues = $_SESSION['_old_input'] ?? [];
        unset($_SESSION['_old_input']);
    }

    /**
     * Load validation errors from session
     */
    private function loadErrors(): void
    {
        $this->errors = $_SESSION['_form_errors'] ?? [];
        unset($_SESSION['_form_errors']);
    }

    /**
     * Set form ID
     */
    public function id(string $id): self
    {
        $this->formId = $id;
        return $this;
    }

    /**
     * Set form class
     */
    public function class(string $class): self
    {
        $this->formClass = $class;
        return $this;
    }

    /**
     * Add form attribute
     */
    public function attribute(string $name, string $value): self
    {
        $this->formAttributes[$name] = $value;
        return $this;
    }

    /**
     * Enable/disable CSRF protection
     */
    public function csrf(bool $enabled = true): self
    {
        $this->csrfEnabled = $enabled;
        return $this;
    }

    /**
     * Enable file uploads
     */
    public function files(bool $enabled = true): self
    {
        $this->hasFiles = $enabled;
        return $this;
    }

    /**
     * Set external errors
     */
    public function withErrors(array $errors): self
    {
        $this->errors = array_merge($this->errors, $errors);
        return $this;
    }

    /**
     * Alias for withErrors() for backwards compatibility
     */
    public function errors(array $errors): self
    {
        return $this->withErrors($errors);
    }

    /**
     * Set old values
     */
    public function withOld(array $values): self
    {
        // When explicitly called with values, replace old values (don't merge)
        // This is used for edit forms where we want to use the model data, not session data
        $this->oldValues = $values;
        return $this;
    }

    // ========== Field Methods ==========

    /**
     * Add text input
     */
    public function text(string $name, ?string $label = null): self
    {
        return $this->addField('text', $name, $label);
    }

    /**
     * Add email input
     */
    public function email(string $name, ?string $label = null): self
    {
        return $this->addField('email', $name, $label);
    }

    /**
     * Add password input
     */
    public function password(string $name, ?string $label = null): self
    {
        return $this->addField('password', $name, $label);
    }

    /**
     * Add number input
     */
    public function number(string $name, ?string $label = null): self
    {
        return $this->addField('number', $name, $label);
    }

    /**
     * Add tel input
     */
    public function tel(string $name, ?string $label = null): self
    {
        return $this->addField('tel', $name, $label);
    }

    /**
     * Add URL input
     */
    public function url(string $name, ?string $label = null): self
    {
        return $this->addField('url', $name, $label);
    }

    /**
     * Add date input
     */
    public function date(string $name, ?string $label = null): self
    {
        return $this->addField('date', $name, $label);
    }

    /**
     * Add datetime-local input
     */
    public function datetime(string $name, ?string $label = null): self
    {
        return $this->addField('datetime-local', $name, $label);
    }

    /**
     * Add time input
     */
    public function time(string $name, ?string $label = null): self
    {
        return $this->addField('time', $name, $label);
    }

    /**
     * Add hidden input
     */
    public function hidden(string $name, string $value = ''): self
    {
        $this->addField('hidden', $name, null);
        $this->fields[$this->currentField]['value'] = $value;
        return $this;
    }

    /**
     * Add textarea
     */
    public function textarea(string $name, ?string $label = null): self
    {
        return $this->addField('textarea', $name, $label);
    }

    /**
     * Add select dropdown
     */
    public function select(string $name, ?string $label = null, array $options = []): self
    {
        $this->addField('select', $name, $label);
        $this->fields[$this->currentField]['options'] = $options;
        return $this;
    }

    /**
     * Add checkbox
     */
    public function checkbox(string $name, ?string $label = null, string $value = '1'): self
    {
        $this->addField('checkbox', $name, $label);
        $this->fields[$this->currentField]['value'] = $value;
        return $this;
    }

    /**
     * Add radio button group
     */
    public function radio(string $name, ?string $label = null, array $options = []): self
    {
        $this->addField('radio', $name, $label);
        $this->fields[$this->currentField]['options'] = $options;
        return $this;
    }

    /**
     * Add file input
     */
    public function file(string $name, ?string $label = null): self
    {
        $this->hasFiles = true;
        $this->addField('file', $name, $label);
        return $this;
    }

    /**
     * Add image upload with preview
     */
    public function image(string $name, ?string $label = null): self
    {
        $this->hasFiles = true;
        $this->addField('image', $name, $label);
        return $this;
    }

    /**
     * Add submit button
     */
    public function submit(string $text = 'Submit', ?string $name = null): self
    {
        $this->addField('submit', $name ?? 'submit', null);
        $this->fields[$this->currentField]['text'] = $text;
        return $this;
    }

    /**
     * Add button
     */
    public function button(string $text, string $type = 'button', ?string $name = null): self
    {
        $this->addField('button', $name ?? 'button', null);
        $this->fields[$this->currentField]['text'] = $text;
        $this->fields[$this->currentField]['buttonType'] = $type;
        return $this;
    }

    /**
     * Add raw HTML
     */
    public function raw(string $html): self
    {
        $this->addField('raw', 'raw_' . count($this->fields), null);
        $this->fields[$this->currentField]['html'] = $html;
        return $this;
    }

    /**
     * Add divider
     */
    public function divider(?string $text = null): self
    {
        $html = '<div class="relative my-6">';
        $html .= '<div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-700"></div></div>';
        if ($text) {
            $html .= '<div class="relative flex justify-center text-sm"><span class="px-4 bg-slate-900 text-slate-500">' . Security::escape($text) . '</span></div>';
        }
        $html .= '</div>';
        return $this->raw($html);
    }

    // ========== Field Modifiers ==========

    /**
     * Mark field as required
     */
    public function required(bool $required = true): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['required'] = $required;
        }
        return $this;
    }

    /**
     * Add placeholder
     */
    public function placeholder(string $placeholder): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['placeholder'] = $placeholder;
        }
        return $this;
    }

    /**
     * Set field value
     */
    public function value(mixed $value): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['value'] = $value;
        }
        return $this;
    }

    /**
     * Set default value (used only if no old value)
     */
    public function default(mixed $value): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['default'] = $value;
        }
        return $this;
    }

    /**
     * Set checkbox as checked by default
     */
    public function checked(bool $checked = true): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['default'] = $checked;
        }
        return $this;
    }

    /**
     * Add field class
     */
    public function fieldClass(string $class): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['class'] = $class;
        }
        return $this;
    }

    /**
     * Add wrapper class
     */
    public function wrapperClass(string $class): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['wrapperClass'] = $class;
        }
        return $this;
    }

    /**
     * Add help text
     */
    public function help(string $text): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['help'] = $text;
        }
        return $this;
    }

    /**
     * Set min value/length
     */
    public function min(int $value): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['min'] = $value;
        }
        return $this;
    }

    /**
     * Set max value/length
     */
    public function max(int $value): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['max'] = $value;
        }
        return $this;
    }

    /**
     * Set minlength for text inputs
     */
    public function minLength(int $length): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['minlength'] = $length;
        }
        return $this;
    }

    /**
     * Set maxlength for text inputs
     */
    public function maxLength(int $length): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['maxlength'] = $length;
        }
        return $this;
    }

    /**
     * Set step for number inputs
     */
    public function step(string $step): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['step'] = $step;
        }
        return $this;
    }

    /**
     * Set rows for textarea
     */
    public function rows(int $rows): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['rows'] = $rows;
        }
        return $this;
    }

    /**
     * Disable field
     */
    public function disabled(bool $disabled = true): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['disabled'] = $disabled;
        }
        return $this;
    }

    /**
     * Make field readonly
     */
    public function readonly(bool $readonly = true): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['readonly'] = $readonly;
        }
        return $this;
    }

    /**
     * Set autocomplete attribute
     */
    public function autocomplete(string $value): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['autocomplete'] = $value;
        }
        return $this;
    }

    /**
     * Add custom attribute to field
     */
    public function attr(string $name, string $value): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['attributes'][$name] = $value;
        }
        return $this;
    }

    /**
     * Set accepted file types
     */
    public function accept(string $types): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['accept'] = $types;
        }
        return $this;
    }

    /**
     * Set icon for input
     */
    public function icon(string $iconName): self
    {
        if ($this->currentField) {
            $this->fields[$this->currentField]['icon'] = $iconName;
        }
        return $this;
    }

    // ========== Rendering ==========

    /**
     * Add field to collection
     */
    private function addField(string $type, string $name, ?string $label): self
    {
        $this->currentField = $name;
        $this->fields[$name] = [
            'type' => $type,
            'name' => $name,
            'label' => $label,
            'required' => false,
            'attributes' => [],
        ];
        return $this;
    }

    /**
     * Render and return form HTML
     */
    public function render(): string
    {
        $html = $this->renderOpen();

        foreach ($this->fields as $field) {
            $html .= $this->renderField($field);
        }

        $html .= $this->renderClose();

        return $html;
    }

    /**
     * Render opening form tag
     */
    public function renderOpen(): string
    {
        $attrs = [];

        if ($this->action) {
            $attrs[] = 'action="' . Security::escape($this->action) . '"';
        }

        // Method spoofing for PUT, PATCH, DELETE
        $method = $this->method;
        if (in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
            $attrs[] = 'method="POST"';
        } else {
            $attrs[] = 'method="' . $method . '"';
        }

        if ($this->formId) {
            $attrs[] = 'id="' . Security::escape($this->formId) . '"';
        }

        $class = $this->formClass ?: $this->theme['form'];
        $attrs[] = 'class="' . Security::escape($class) . '"';

        if ($this->hasFiles) {
            $attrs[] = 'enctype="multipart/form-data"';
        }

        foreach ($this->formAttributes as $name => $value) {
            $attrs[] = Security::escape($name) . '="' . Security::escape($value) . '"';
        }

        $html = '<form ' . implode(' ', $attrs) . '>';

        // CSRF token
        if ($this->csrfEnabled && $this->method !== 'GET') {
            $html .= CSRF::field();
        }

        // Method spoofing
        if (in_array($this->method, ['PUT', 'PATCH', 'DELETE'])) {
            $html .= '<input type="hidden" name="_method" value="' . $this->method . '">';
        }

        return $html;
    }

    /**
     * Render closing form tag
     */
    public function renderClose(): string
    {
        return '</form>';
    }

    /**
     * Render single field
     */
    private function renderField(array $field): string
    {
        return match($field['type']) {
            'hidden' => $this->renderHidden($field),
            'text', 'email', 'password', 'number', 'tel', 'url', 'date', 'datetime-local', 'time' => $this->renderInput($field),
            'textarea' => $this->renderTextarea($field),
            'select' => $this->renderSelect($field),
            'checkbox' => $this->renderCheckbox($field),
            'radio' => $this->renderRadio($field),
            'file' => $this->renderFile($field),
            'image' => $this->renderImage($field),
            'submit' => $this->renderSubmit($field),
            'button' => $this->renderButton($field),
            'raw' => $field['html'] ?? '',
            default => '',
        };
    }

    /**
     * Get old value or default
     */
    private function getOldValue(string $name, mixed $default = ''): mixed
    {
        return $this->oldValues[$name] ?? $default;
    }

    /**
     * Check if field has error
     */
    private function hasError(string $name): bool
    {
        return isset($this->errors[$name]);
    }

    /**
     * Get field error
     */
    private function getError(string $name): string
    {
        $error = $this->errors[$name] ?? null;
        if (is_array($error)) {
            return $error[0] ?? '';
        }
        return $error ?? '';
    }

    /**
     * Build common attributes string
     */
    private function buildAttributes(array $field, string $baseClass): string
    {
        $attrs = [];
        $name = $field['name'];

        $attrs[] = 'name="' . Security::escape($name) . '"';
        // Use custom id if provided, otherwise use name as id
        $id = $field['attributes']['id'] ?? $name;
        $attrs[] = 'id="' . Security::escape($id) . '"';

        // Class with error state
        $class = $field['class'] ?? $baseClass;
        if ($this->hasError($name)) {
            $class .= ' ' . $this->theme['inputError'];
        }
        $attrs[] = 'class="' . Security::escape($class) . '"';

        if (!empty($field['placeholder'])) {
            $attrs[] = 'placeholder="' . Security::escape($field['placeholder']) . '"';
        }

        if (!empty($field['required'])) {
            $attrs[] = 'required';
        }

        if (!empty($field['disabled'])) {
            $attrs[] = 'disabled';
        }

        if (!empty($field['readonly'])) {
            $attrs[] = 'readonly';
        }

        if (isset($field['min'])) {
            $attrs[] = 'min="' . (int) $field['min'] . '"';
        }

        if (isset($field['max'])) {
            $attrs[] = 'max="' . (int) $field['max'] . '"';
        }

        if (isset($field['minlength'])) {
            $attrs[] = 'minlength="' . (int) $field['minlength'] . '"';
        }

        if (isset($field['maxlength'])) {
            $attrs[] = 'maxlength="' . (int) $field['maxlength'] . '"';
        }

        if (isset($field['step'])) {
            $attrs[] = 'step="' . Security::escape($field['step']) . '"';
        }

        if (isset($field['autocomplete'])) {
            $attrs[] = 'autocomplete="' . Security::escape($field['autocomplete']) . '"';
        }

        if (isset($field['accept'])) {
            $attrs[] = 'accept="' . Security::escape($field['accept']) . '"';
        }

        // Custom attributes (skip 'id' as it's already handled above)
        foreach ($field['attributes'] ?? [] as $attrName => $attrValue) {
            if ($attrName !== 'id') {
                $attrs[] = Security::escape($attrName) . '="' . Security::escape($attrValue) . '"';
            }
        }

        return implode(' ', $attrs);
    }

    /**
     * Render label
     */
    private function renderLabel(array $field): string
    {
        if (empty($field['label'])) {
            return '';
        }

        $required = !empty($field['required']) ? '<span class="' . $this->theme['required'] . '">*</span>' : '';

        return '<label for="' . Security::escape($field['name']) . '" class="' . $this->theme['label'] . '">'
            . Security::escape($field['label']) . $required
            . '</label>';
    }

    /**
     * Render error message
     */
    private function renderError(string $name): string
    {
        if (!$this->hasError($name)) {
            return '';
        }

        return '<p class="' . $this->theme['error'] . '">' . Security::escape($this->getError($name)) . '</p>';
    }

    /**
     * Render help text
     */
    private function renderHelp(array $field): string
    {
        if (empty($field['help'])) {
            return '';
        }

        return '<p class="' . $this->theme['help'] . '">' . Security::escape($field['help']) . '</p>';
    }

    /**
     * Render hidden input
     */
    private function renderHidden(array $field): string
    {
        $value = $field['value'] ?? $this->getOldValue($field['name'], $field['default'] ?? '');
        $attrs = ['type="hidden"', 'name="' . Security::escape($field['name']) . '"', 'value="' . Security::escape($value) . '"'];
        
        // Add custom attributes
        foreach ($field['attributes'] ?? [] as $attrName => $attrValue) {
            $attrs[] = Security::escape($attrName) . '="' . Security::escape($attrValue) . '"';
        }
        
        return '<input ' . implode(' ', $attrs) . '>';
    }

    /**
     * Render text-like input
     */
    private function renderInput(array $field): string
    {
        $wrapperClass = $field['wrapperClass'] ?? $this->theme['group'];
        $value = $field['type'] === 'password' ? '' : ($field['value'] ?? $this->getOldValue($field['name'], $field['default'] ?? ''));

        $hasIcon = !empty($field['icon']);

        $html = '<div class="' . $wrapperClass . '">';
        $html .= $this->renderLabel($field);

        if ($hasIcon) {
            $html .= '<div class="relative">';
            $html .= '<div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">';
            $html .= '<ion-icon name="' . Security::escape($field['icon']) . '" class="text-slate-500 text-lg"></ion-icon>';
            $html .= '</div>';
        }

        $inputClass = $this->theme['input'];
        if ($hasIcon) {
            $inputClass .= ' pl-12';
        }

        $html .= '<input type="' . $field['type'] . '" ';
        $html .= $this->buildAttributes($field, $inputClass);
        $html .= ' value="' . Security::escape($value) . '">';

        if ($hasIcon) {
            $html .= '</div>';
        }

        $html .= $this->renderError($field['name']);
        $html .= $this->renderHelp($field);
        $html .= '</div>';

        return $html;
    }

    /**
     * Render textarea
     */
    private function renderTextarea(array $field): string
    {
        $wrapperClass = $field['wrapperClass'] ?? $this->theme['group'];
        $value = $field['value'] ?? $this->getOldValue($field['name'], $field['default'] ?? '');
        $rows = $field['rows'] ?? 4;

        $html = '<div class="' . $wrapperClass . '">';
        $html .= $this->renderLabel($field);
        $html .= '<textarea ' . $this->buildAttributes($field, $this->theme['textarea']) . ' rows="' . $rows . '">';
        $html .= Security::escape($value);
        $html .= '</textarea>';
        $html .= $this->renderError($field['name']);
        $html .= $this->renderHelp($field);
        $html .= '</div>';

        return $html;
    }

    /**
     * Render select dropdown
     */
    private function renderSelect(array $field): string
    {
        $wrapperClass = $field['wrapperClass'] ?? $this->theme['group'];
        $selected = $field['value'] ?? $this->getOldValue($field['name'], $field['default'] ?? '');
        $options = $field['options'] ?? [];

        $html = '<div class="' . $wrapperClass . '">';
        $html .= $this->renderLabel($field);
        $html .= '<select ' . $this->buildAttributes($field, $this->theme['select']) . '>';

        foreach ($options as $value => $label) {
            $isSelected = (string) $value === (string) $selected ? ' selected' : '';
            $html .= '<option value="' . Security::escape($value) . '"' . $isSelected . '>' . Security::escape($label) . '</option>';
        }

        $html .= '</select>';
        $html .= $this->renderError($field['name']);
        $html .= $this->renderHelp($field);
        $html .= '</div>';

        return $html;
    }

    /**
     * Render checkbox
     */
    private function renderCheckbox(array $field): string
    {
        $wrapperClass = $field['wrapperClass'] ?? '';
        $value = $field['value'] ?? '1';
        
        // Check if checkbox should be checked:
        // 1. Use old value if exists (from validation errors)
        // 2. Otherwise use default value if set
        // 3. Otherwise check if old value matches the checkbox value
        $oldValue = $this->getOldValue($field['name'], null);
        $isChecked = false;
        
        if ($oldValue !== null) {
            // Old value exists (from validation errors or form submission)
            $isChecked = ($oldValue === $value || $oldValue === true || $oldValue === '1' || $oldValue === 1);
        } elseif (isset($field['default'])) {
            // Use default value
            $isChecked = ($field['default'] === true || $field['default'] === '1' || $field['default'] === 1);
        }
        
        $checked = $isChecked ? ' checked' : '';

        $html = '<div class="flex items-center ' . $wrapperClass . '">';
        $html .= '<input type="checkbox" ';
        $html .= 'name="' . Security::escape($field['name']) . '" ';
        $html .= 'id="' . Security::escape($field['name']) . '" ';
        $html .= 'value="' . Security::escape($value) . '" ';
        $html .= 'class="' . $this->theme['checkbox'] . '"' . $checked;

        if (!empty($field['required'])) {
            $html .= ' required';
        }

        $html .= '>';

        if (!empty($field['label'])) {
            $html .= '<label for="' . Security::escape($field['name']) . '" class="' . $this->theme['checkboxLabel'] . '">';
            $html .= Security::escape($field['label']);
            $html .= '</label>';
        }

        $html .= '</div>';
        $html .= $this->renderError($field['name']);

        return $html;
    }

    /**
     * Render radio button group
     */
    private function renderRadio(array $field): string
    {
        $wrapperClass = $field['wrapperClass'] ?? $this->theme['group'];
        $selected = $field['value'] ?? $this->getOldValue($field['name'], $field['default'] ?? '');
        $options = $field['options'] ?? [];

        $html = '<div class="' . $wrapperClass . '">';

        if (!empty($field['label'])) {
            $html .= '<label class="' . $this->theme['label'] . '">' . Security::escape($field['label']) . '</label>';
        }

        $html .= '<div class="space-y-2 mt-2">';

        foreach ($options as $value => $label) {
            $id = $field['name'] . '_' . $value;
            $checked = (string) $value === (string) $selected ? ' checked' : '';

            $html .= '<div class="flex items-center">';
            $html .= '<input type="radio" name="' . Security::escape($field['name']) . '" ';
            $html .= 'id="' . Security::escape($id) . '" ';
            $html .= 'value="' . Security::escape($value) . '" ';
            $html .= 'class="' . $this->theme['radio'] . '"' . $checked . '>';
            $html .= '<label for="' . Security::escape($id) . '" class="' . $this->theme['checkboxLabel'] . '">';
            $html .= Security::escape($label) . '</label>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= $this->renderError($field['name']);
        $html .= '</div>';

        return $html;
    }

    /**
     * Render file input
     */
    private function renderFile(array $field): string
    {
        $wrapperClass = $field['wrapperClass'] ?? $this->theme['group'];

        $html = '<div class="' . $wrapperClass . '">';
        $html .= $this->renderLabel($field);
        $html .= '<input type="file" ' . $this->buildAttributes($field, $this->theme['file']) . '>';
        $html .= $this->renderError($field['name']);
        $html .= $this->renderHelp($field);
        $html .= '</div>';

        return $html;
    }

    /**
     * Render image upload with preview
     */
    private function renderImage(array $field): string
    {
        $wrapperClass = $field['wrapperClass'] ?? $this->theme['group'];
        $name = $field['name'];
        $accept = $field['accept'] ?? 'image/*';

        $html = '<div class="' . $wrapperClass . '">';
        $html .= $this->renderLabel($field);

        $html .= '<div class="flex items-center gap-4">';

        // Preview container
        $html .= '<div id="' . $name . '_preview_container" class="hidden">';
        $html .= '<img id="' . $name . '_preview" class="' . $this->theme['filePreview'] . '" alt="Preview">';
        $html .= '</div>';

        // Default placeholder
        $html .= '<div id="' . $name . '_placeholder" class="w-24 h-24 rounded-lg border-2 border-dashed border-slate-700 flex items-center justify-center bg-slate-800/50">';
        $html .= '<ion-icon name="person" class="text-3xl text-slate-600"></ion-icon>';
        $html .= '</div>';

        $html .= '<div class="flex-1">';
        // Use data attribute instead of inline onchange handler (CSP-compliant)
        $html .= '<input type="file" ' . $this->buildAttributes($field, $this->theme['file']) . ' accept="' . Security::escape($accept) . '" data-preview-target="' . Security::escape($name) . '">';
        $html .= '</div>';

        $html .= '</div>';

        $html .= $this->renderError($name);
        $html .= $this->renderHelp($field);
        $html .= '</div>';

        // Add preview script if not already added (CSP-compliant with event listeners)
        static $scriptAdded = false;
        if (!$scriptAdded) {
            $nonce = function_exists('csp_nonce') ? csp_nonce() : '';
            $nonceAttr = $nonce ? ' nonce="' . htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8') . '"' : '';
            $html .= '<script' . $nonceAttr . '>' . "\n";
            $html .= 'function previewImage(input, name) {' . "\n";
            $html .= '    const preview = document.getElementById(name + \'_preview\');' . "\n";
            $html .= '    const previewContainer = document.getElementById(name + \'_preview_container\');' . "\n";
            $html .= '    const placeholder = document.getElementById(name + \'_preview_placeholder\');' . "\n";
            $html .= '    ' . "\n";
            $html .= '    if (input.files && input.files[0]) {' . "\n";
            $html .= '        const reader = new FileReader();' . "\n";
            $html .= '        reader.onload = function(e) {' . "\n";
            $html .= '            preview.src = e.target.result;' . "\n";
            $html .= '            previewContainer.classList.remove(\'hidden\');' . "\n";
            $html .= '            placeholder.classList.add(\'hidden\');' . "\n";
            $html .= '        };' . "\n";
            $html .= '        reader.readAsDataURL(input.files[0]);' . "\n";
            $html .= '    }' . "\n";
            $html .= '}' . "\n";
            $html .= '// Bind event listeners to file inputs with data-preview-target (CSP-compliant)' . "\n";
            $html .= 'document.addEventListener(\'DOMContentLoaded\', function() {' . "\n";
            $html .= '    const fileInputs = document.querySelectorAll(\'input[type="file"][data-preview-target]\');' . "\n";
            $html .= '    fileInputs.forEach(function(input) {' . "\n";
            $html .= '        input.addEventListener(\'change\', function() {' . "\n";
            $html .= '            const name = input.getAttribute(\'data-preview-target\');' . "\n";
            $html .= '            previewImage(input, name);' . "\n";
            $html .= '        });' . "\n";
            $html .= '    });' . "\n";
            $html .= '});' . "\n";
            $html .= '</script>' . "\n";
            $scriptAdded = true;
        }

        return $html;
    }

    /**
     * Render submit button
     */
    private function renderSubmit(array $field): string
    {
        $text = $field['text'] ?? 'Submit';
        $class = $field['class'] ?? $this->theme['submit'];
        
        // Build attributes string
        $attrs = [];
        $attrs[] = 'type="submit"';
        
        // Add disabled attribute if set
        if (!empty($field['disabled'])) {
            $attrs[] = 'disabled';
        }
        
        // Add custom attributes
        foreach ($field['attributes'] ?? [] as $attrName => $attrValue) {
            if ($attrName === 'class') {
                // Merge custom class with default class
                $class = $class . ' ' . $attrValue;
            } else {
                $attrs[] = Security::escape($attrName) . '="' . Security::escape($attrValue) . '"';
            }
        }
        
        $attrs[] = 'class="' . Security::escape($class) . '"';

        return '<button ' . implode(' ', $attrs) . '>' . Security::escape($text) . '</button>';
    }

    /**
     * Render button
     */
    private function renderButton(array $field): string
    {
        $text = $field['text'] ?? 'Button';
        $type = $field['buttonType'] ?? 'button';
        $class = $field['class'] ?? $this->theme['button'];

        return '<button type="' . $type . '" class="' . Security::escape($class) . '">' . Security::escape($text) . '</button>';
    }

    /**
     * Alias for render()
     */
    public function close(): string
    {
        return $this->render();
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->render();
    }
}


if (!\class_exists('FormBuilder', false) && !\interface_exists('FormBuilder', false) && !\trait_exists('FormBuilder', false)) {
    \class_alias(__NAMESPACE__ . '\\FormBuilder', 'FormBuilder');
}
