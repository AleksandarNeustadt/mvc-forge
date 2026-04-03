<?php

namespace App\Core\view;


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
 * Table Builder Class (for VIEW/HTML)
 * 
 * Fluent API for building HTML tables in views (similar to FormBuilder)
 * 
 * Usage:
 *   echo Table::open()
 *       ->header(['Name', 'Email', 'Actions'])
 *       ->row(['John', 'john@example.com', '<button>Edit</button>'])
 *       ->row(['Jane', 'jane@example.com', '<button>Edit</button>'])
 *       ->close();
 */
class TableBuilder
{
    private string $tableId = '';
    private string $tableClass = '';
    private array $tableAttributes = [];
    private array $headers = [];
    private array $rows = [];
    private array $footer = [];
    private bool $striped = false;
    private bool $hover = true;
    private bool $bordered = false;
    private ?string $caption = null;

    // Theme colors for styling
    private array $theme = [
        'table' => 'w-full border-collapse',
        'tableBordered' => 'border border-slate-700',
        'tableStriped' => '',
        'thead' => 'bg-slate-900/50',
        'tbody' => '',
        'tfoot' => 'bg-slate-900/30',
        'th' => 'px-6 py-4 text-left text-sm font-semibold text-slate-300 border-b border-slate-700/50',
        'td' => 'px-6 py-4 text-sm text-slate-300 border-b border-slate-700/30',
        'tdStriped' => 'bg-slate-800/30',
        'tdHover' => 'hover:bg-slate-700/30',
        'caption' => 'text-sm text-slate-400 mb-2',
    ];

    /**
     * Create new TableBuilder instance
     */
    public function __construct()
    {
    }

    /**
     * Static factory method
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Set table ID
     */
    public function id(string $id): self
    {
        $this->tableId = $id;
        return $this;
    }

    /**
     * Set table class
     */
    public function class(string $class): self
    {
        $this->tableClass = $class;
        return $this;
    }

    /**
     * Add table attribute
     */
    public function attribute(string $name, string $value): self
    {
        $this->tableAttributes[$name] = $value;
        return $this;
    }

    /**
     * Set table headers
     */
    public function header(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Add table row
     */
    public function row(array $cells, ?string $rowClass = null): self
    {
        $this->rows[] = [
            'cells' => $cells,
            'class' => $rowClass
        ];
        return $this;
    }

    /**
     * Add multiple rows
     */
    public function rows(array $rows): self
    {
        foreach ($rows as $row) {
            if (is_array($row)) {
                $this->row($row);
            }
        }
        return $this;
    }

    /**
     * Set table footer
     */
    public function footer(array $footer): self
    {
        $this->footer = $footer;
        return $this;
    }

    /**
     * Enable striped rows
     */
    public function striped(bool $enabled = true): self
    {
        $this->striped = $enabled;
        return $this;
    }

    /**
     * Enable hover effect
     */
    public function hover(bool $enabled = true): self
    {
        $this->hover = $enabled;
        return $this;
    }

    /**
     * Enable borders
     */
    public function bordered(bool $enabled = true): self
    {
        $this->bordered = $enabled;
        return $this;
    }

    /**
     * Set table caption
     */
    public function caption(string $caption): self
    {
        $this->caption = $caption;
        return $this;
    }

    /**
     * Render and return table HTML
     */
    public function render(): string
    {
        $html = $this->renderOpen();
        $html .= $this->renderCaption();
        $html .= $this->renderHeader();
        $html .= $this->renderBody();
        $html .= $this->renderFooter();
        $html .= $this->renderClose();

        return $html;
    }

    /**
     * Render opening table tag
     */
    private function renderOpen(): string
    {
        $classes = [$this->theme['table']];
        
        if ($this->bordered) {
            $classes[] = $this->theme['tableBordered'];
        }
        
        if ($this->tableClass) {
            $classes[] = $this->tableClass;
        }

        $attrs = ['class' => implode(' ', $classes)];
        
        if ($this->tableId) {
            $attrs['id'] = $this->tableId;
        }

        foreach ($this->tableAttributes as $name => $value) {
            $attrs[$name] = $value;
        }

        $attrsStr = '';
        foreach ($attrs as $name => $value) {
            $attrsStr .= ' ' . $name . '="' . Security::escape($value) . '"';
        }

        return '<table' . $attrsStr . '>';
    }

    /**
     * Render caption
     */
    private function renderCaption(): string
    {
        if (!$this->caption) {
            return '';
        }

        return '<caption class="' . $this->theme['caption'] . '">' . Security::escape($this->caption) . '</caption>';
    }

    /**
     * Render table header
     */
    private function renderHeader(): string
    {
        if (empty($this->headers)) {
            return '';
        }

        $html = '<thead class="' . $this->theme['thead'] . '">';
        $html .= '<tr>';

        foreach ($this->headers as $header) {
            $html .= '<th class="' . $this->theme['th'] . '">';
            $html .= is_string($header) ? Security::escape($header) : $header;
            $html .= '</th>';
        }

        $html .= '</tr>';
        $html .= '</thead>';

        return $html;
    }

    /**
     * Render table body
     */
    private function renderBody(): string
    {
        if (empty($this->rows)) {
            return '<tbody></tbody>';
        }

        $html = '<tbody class="' . $this->theme['tbody'] . '">';

        foreach ($this->rows as $index => $row) {
            $rowClass = $row['class'] ?? '';
            $classes = [$this->theme['td']];
            
            if ($this->striped && $index % 2 === 1) {
                $classes[] = $this->theme['tdStriped'];
            }
            
            if ($this->hover) {
                $classes[] = $this->theme['tdHover'];
            }
            
            if ($rowClass) {
                $classes[] = $rowClass;
            }

            $html .= '<tr class="' . implode(' ', $classes) . '">';

            foreach ($row['cells'] as $cell) {
                $html .= '<td>';
                $html .= is_string($cell) ? Security::escape($cell) : $cell;
                $html .= '</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody>';

        return $html;
    }

    /**
     * Render table footer
     */
    private function renderFooter(): string
    {
        if (empty($this->footer)) {
            return '';
        }

        $html = '<tfoot class="' . $this->theme['tfoot'] . '">';
        $html .= '<tr>';

        foreach ($this->footer as $cell) {
            $html .= '<td class="' . $this->theme['td'] . '">';
            $html .= is_string($cell) ? Security::escape($cell) : $cell;
            $html .= '</td>';
        }

        $html .= '</tr>';
        $html .= '</tfoot>';

        return $html;
    }

    /**
     * Render closing table tag
     */
    private function renderClose(): string
    {
        return '</table>';
    }

    /**
     * Output table (for chaining)
     */
    public function close(): void
    {
        echo $this->render();
    }
}

/**
 * Table Facade - Static helper for TableBuilder
 */
class Table
{
    /**
     * Create and open a new table
     */
    public static function open(): TableBuilder
    {
        return new TableBuilder();
    }

    /**
     * Quick table with headers and rows
     */
    public static function make(array $headers, array $rows = [], array $options = []): string
    {
        $builder = new TableBuilder();
        
        if (isset($options['id'])) {
            $builder->id($options['id']);
        }
        
        if (isset($options['class'])) {
            $builder->class($options['class']);
        }
        
        if (isset($options['striped'])) {
            $builder->striped($options['striped']);
        }
        
        if (isset($options['hover'])) {
            $builder->hover($options['hover']);
        }
        
        if (isset($options['bordered'])) {
            $builder->bordered($options['bordered']);
        }
        
        if (isset($options['caption'])) {
            $builder->caption($options['caption']);
        }

        return $builder->header($headers)->rows($rows)->render();
    }
}


if (!\class_exists('TableBuilder', false) && !\interface_exists('TableBuilder', false) && !\trait_exists('TableBuilder', false)) {
    \class_alias(__NAMESPACE__ . '\\TableBuilder', 'TableBuilder');
}

if (!\class_exists('Table', false) && !\interface_exists('Table', false) && !\trait_exists('Table', false)) {
    \class_alias(__NAMESPACE__ . '\\Table', 'Table');
}
