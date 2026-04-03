# Page Manager & Blog System - Plan Implementacije

## 🎯 Pregled Sistema

### 1. Page Manager
- **CRUD operacije** za upravljanje stranicama
- **Dinamičko rutiranje** - dodavanje ruta u runtime
- **Dinamički view-ovi** - renderovanje različitih tipova sadržaja
- **Povezivanje sa aplikacijama** - stranice mogu prikazati blog, kategoriju, tag, itd.

### 2. Blog Aplikacija
- **CRUD za blog postove** (title, slug, content, image, published_at, etc.)
- **Hijerarhijske kategorije** - neograničeno nivoa (kategorija -> podkategorija -> ...)
- **Tag sistem** - many-to-many veza
- **SEO-friendly slug-ovi**

### 3. Integracija
- Stranice mogu prikazati:
  - Jedan blog post
  - Listu blog postova (sa filtrima)
  - Kategoriju (hijerarhijski)
  - Tag (svi postovi sa tim tagom)

### 4. Frontend Navigacija
- Dinamička navigacija koja čita stranice iz Page Manager-a
- Zamena hardkodiranih linkova (Project, Blog, Account, Contact)

---

## 📊 Database Struktura

### 1. `pages` tabela (Page Manager)
```sql
CREATE TABLE pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    route VARCHAR(255) NOT NULL UNIQUE,  -- e.g., '/blog', '/about'
    page_type VARCHAR(50) NOT NULL,  -- 'custom', 'blog_list', 'blog_post', 'blog_category', 'blog_tag'
    content TEXT,  -- HTML content for 'custom' pages
    template VARCHAR(100),  -- Template name for rendering
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords VARCHAR(255),
    is_active BOOLEAN DEFAULT 1,
    is_in_menu BOOLEAN DEFAULT 1,
    menu_order INTEGER DEFAULT 0,
    parent_page_id INTEGER NULL,  -- For submenu items
    blog_post_id INTEGER NULL,  -- FK to blog_posts (if page_type = 'blog_post')
    blog_category_id INTEGER NULL,  -- FK to blog_categories (if page_type = 'blog_category')
    blog_tag_id INTEGER NULL,  -- FK to blog_tags (if page_type = 'blog_tag')
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL,
    FOREIGN KEY (parent_page_id) REFERENCES pages(id) ON DELETE SET NULL,
    FOREIGN KEY (blog_post_id) REFERENCES blog_posts(id) ON DELETE SET NULL,
    FOREIGN KEY (blog_category_id) REFERENCES blog_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (blog_tag_id) REFERENCES blog_tags(id) ON DELETE SET NULL
);
CREATE INDEX idx_pages_slug ON pages(slug);
CREATE INDEX idx_pages_route ON pages(route);
CREATE INDEX idx_pages_active ON pages(is_active);
CREATE INDEX idx_pages_menu ON pages(is_in_menu, menu_order);
```

### 2. `blog_categories` tabela (Hijerarhijska struktura)
```sql
CREATE TABLE blog_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    parent_id INTEGER NULL,  -- For hierarchical structure
    image VARCHAR(255),
    meta_title VARCHAR(255),
    meta_description TEXT,
    sort_order INTEGER DEFAULT 0,
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL,
    FOREIGN KEY (parent_id) REFERENCES blog_categories(id) ON DELETE CASCADE
);
CREATE INDEX idx_blog_categories_slug ON blog_categories(slug);
CREATE INDEX idx_blog_categories_parent ON blog_categories(parent_id);
```

### 3. `blog_posts` tabela
```sql
CREATE TABLE blog_posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    excerpt TEXT,  -- Short description
    content TEXT NOT NULL,  -- Full content (HTML/Markdown)
    featured_image VARCHAR(255),
    status VARCHAR(20) DEFAULT 'draft',  -- 'draft', 'published', 'archived'
    published_at INTEGER NULL,
    author_id INTEGER NOT NULL,  -- FK to users
    views INTEGER DEFAULT 0,
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords VARCHAR(255),
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX idx_blog_posts_slug ON blog_posts(slug);
CREATE INDEX idx_blog_posts_status ON blog_posts(status);
CREATE INDEX idx_blog_posts_published ON blog_posts(published_at);
CREATE INDEX idx_blog_posts_author ON blog_posts(author_id);
```

### 4. `blog_tags` tabela
```sql
CREATE TABLE blog_tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL
);
CREATE INDEX idx_blog_tags_slug ON blog_tags(slug);
```

### 5. `blog_post_tags` pivot tabela (Many-to-Many)
```sql
CREATE TABLE blog_post_tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    blog_post_id INTEGER NOT NULL,
    blog_tag_id INTEGER NOT NULL,
    created_at INTEGER NOT NULL,
    FOREIGN KEY (blog_post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (blog_tag_id) REFERENCES blog_tags(id) ON DELETE CASCADE,
    UNIQUE(blog_post_id, blog_tag_id)
);
CREATE INDEX idx_post_tags_post ON blog_post_tags(blog_post_id);
CREATE INDEX idx_post_tags_tag ON blog_post_tags(blog_tag_id);
```

### 6. `blog_post_categories` pivot tabela (Many-to-Many - post može imati više kategorija)
```sql
CREATE TABLE blog_post_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    blog_post_id INTEGER NOT NULL,
    blog_category_id INTEGER NOT NULL,
    created_at INTEGER NOT NULL,
    FOREIGN KEY (blog_post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (blog_category_id) REFERENCES blog_categories(id) ON DELETE CASCADE,
    UNIQUE(blog_post_id, blog_category_id)
);
CREATE INDEX idx_post_categories_post ON blog_post_categories(blog_post_id);
CREATE INDEX idx_post_categories_category ON blog_post_categories(blog_category_id);
```

---

## 🏗️ Models

### 1. Page Model (`mvc/models/Page.php`)
```php
class Page extends Model {
    protected $table = 'pages';
    protected $fillable = [
        'title', 'slug', 'route', 'page_type', 'content', 'template',
        'meta_title', 'meta_description', 'meta_keywords',
        'is_active', 'is_in_menu', 'menu_order', 'parent_page_id',
        'blog_post_id', 'blog_category_id', 'blog_tag_id'
    ];
    
    // Relationships
    public function parentPage() { }
    public function childPages() { }
    public function blogPost() { }
    public function blogCategory() { }
    public function blogTag() { }
    
    // Scopes
    public function scopeActive($query) { }
    public function scopeInMenu($query) { }
    public function scopeOrdered($query) { }
}
```

### 2. BlogCategory Model (`mvc/models/BlogCategory.php`)
```php
class BlogCategory extends Model {
    protected $table = 'blog_categories';
    protected $fillable = [
        'name', 'slug', 'description', 'parent_id', 'image',
        'meta_title', 'meta_description', 'sort_order'
    ];
    
    // Relationships
    public function parent() { }
    public function children() { }
    public function posts() { }  // Through blog_post_categories
    
    // Methods for hierarchical structure
    public function getBreadcrumbs() { }  // Get path from root
    public function getDepth() { }  // Get nesting level
    public function getAllChildren() { }  // Recursive children
}
```

### 3. BlogPost Model (`mvc/models/BlogPost.php`)
```php
class BlogPost extends Model {
    protected $table = 'blog_posts';
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'featured_image',
        'status', 'published_at', 'author_id',
        'meta_title', 'meta_description', 'meta_keywords'
    ];
    
    // Relationships
    public function author() { }  // Belongs to User
    public function categories() { }  // Many-to-Many
    public function tags() { }  // Many-to-Many
    
    // Scopes
    public function scopePublished($query) { }
    public function scopeDraft($query) { }
}
```

### 4. BlogTag Model (`mvc/models/BlogTag.php`)
```php
class BlogTag extends Model {
    protected $table = 'blog_tags';
    protected $fillable = ['name', 'slug', 'description'];
    
    // Relationships
    public function posts() { }  // Many-to-Many
}
```

---

## 🔌 Dynamic Router System

### Problem
- Rute se registruju u `routes/web.php` na compile time
- Potrebno dinamičko dodavanje ruta u runtime iz Page Manager-a

### Rešenje

#### 1. DynamicRouteRegistry (`core/routing/DynamicRouteRegistry.php`)
```php
class DynamicRouteRegistry {
    private static array $dynamicRoutes = [];
    
    public static function register(string $route, string $handler, array $options = []) { }
    public static function unregister(string $route) { }
    public static function getRoutes(): array { }
    public static function findRoute(string $uri): ?array { }
}
```

#### 2. Router Enhancement
- Modifikovati `Router::dispatch()` da proverava i dynamic routes
- Integrisati sa `RouteCollection`

#### 3. Page Route Handler
- Generic handler koji čita Page iz baze i renderuje odgovarajući view
- Različiti view-ovi za različite `page_type`-ove

---

## 🎨 Page Templates System

### Template Types
1. **custom** - Prikazuje `content` polje (HTML)
2. **blog_list** - Lista svih blog postova
3. **blog_post** - Jedan blog post (preko `blog_post_id`)
4. **blog_category** - Postovi u kategoriji (hijerarhijski)
5. **blog_tag** - Postovi sa tagom

### View Structure
```
mvc/views/pages/
  ├── custom.php          # Generic HTML content
  ├── blog/
  │   ├── list.php        # List of posts
  │   ├── single.php      # Single post
  │   ├── category.php    # Category archive
  │   └── tag.php         # Tag archive
```

---

## 🎛️ Dashboard Controllers

### 1. PageManagerController (`mvc/controllers/PageManagerController.php`)
- `index()` - Lista svih stranica
- `create()` - Forma za kreiranje
- `store()` - Čuvanje nove stranice
- `edit($id)` - Forma za izmenu
- `update($id)` - Ažuriranje
- `destroy($id)` - Brisanje
- **Route validation** - Provera da li route već postoji
- **Slug generation** - Automatski generisanje slug-a

### 2. BlogController (`mvc/controllers/BlogController.php`)
- `index()` - Lista postova
- `create()` - Forma za kreiranje
- `store()` - Čuvanje
- `edit($id)` - Forma za izmenu
- `update($id)` - Ažuriranje
- `destroy($id)` - Brisanje

### 3. BlogCategoryController (`mvc/controllers/BlogCategoryController.php`)
- CRUD operacije
- **Hijerarhijsko prikazivanje** - Tree view
- **Drag & drop reordering** (opciono)

### 4. BlogTagController (`mvc/controllers/BlogTagController.php`)
- CRUD operacije
- Autocomplete za tagove

---

## 🧭 Frontend Navigation System

### Problem
- Trenutno hardkodirano: Project, Blog, Account, Contact
- Potrebno dinamičko čitanje iz Page Manager-a

### Rešenje

#### 1. Navigation Service (`core/services/Navigation.php`)
```php
class Navigation {
    public static function getMenuItems(): array {
        // Get pages where is_in_menu = 1, is_active = 1
        // Order by menu_order, parent_page_id
        // Build hierarchical structure
    }
    
    public static function getBreadcrumbs(string $currentRoute): array {
        // Build breadcrumb trail
    }
}
```

#### 2. Header Component Update
- Ukloniti hardkodirane linkove
- Koristiti `Navigation::getMenuItems()`
- Renderovati hijerarhijsku strukturu (ako ima parent_page_id)

#### 3. Cache System (za performanse)
- Cache menu items
- Invalidate kada se Page ažurira

---

## 📋 Implementation Steps

### Phase 1: Database & Models (Foundation)
1. ✅ Kreirati migracije za sve tabele
2. ✅ Kreirati Model klase (Page, BlogCategory, BlogPost, BlogTag)
3. ✅ Implementirati relationships
4. ✅ Testirati model metode

### Phase 2: Dynamic Router System
1. ✅ Kreirati `DynamicRouteRegistry` klasu
2. ✅ Integrisati sa `Router` klasom
3. ✅ Testirati dinamičko dodavanje ruta

### Phase 3: Page Manager Dashboard
1. ✅ Kreirati `PageManagerController`
2. ✅ Kreirati view-ove za CRUD
3. ✅ Implementirati route validation
4. ✅ Dodati u dashboard sidebar

### Phase 4: Blog System Dashboard
1. ✅ Kreirati `BlogController`, `BlogCategoryController`, `BlogTagController`
2. ✅ Kreirati view-ove za CRUD
3. ✅ Implementirati hijerarhijsko prikazivanje kategorija
4. ✅ Dodati u dashboard sidebar

### Phase 5: Page Templates & Rendering
1. ✅ Kreirati template view-ove
2. ✅ Implementirati generic page handler
3. ✅ Testirati renderovanje različitih tipova stranica

### Phase 6: Integration
1. ✅ Povezati Page Manager sa Blog sistemom
2. ✅ Omogućiti kreiranje stranica za blog postove/kategorije/tagove
3. ✅ Testirati end-to-end flow

### Phase 7: Frontend Navigation
1. ✅ Kreirati `Navigation` service
2. ✅ Ažurirati header component
3. ✅ Implementirati hijerarhijsku navigaciju
4. ✅ Dodati cache sistem

### Phase 8: Polish & Optimization
1. ✅ SEO optimizacija (meta tags)
2. ✅ Performance optimizacija (caching, queries)
3. ✅ UI/UX improvements
4. ✅ Error handling

---

## 🔧 Technical Considerations

### 1. Route Conflicts
- Validacija da route ne postoji pre dodavanja
- Provera konflikata sa statičkim rutama
- Reserved routes list (/dashboard, /api, /login, etc.)

### 2. Slug Uniqueness
- Automatsko dodavanje brojeva ako slug već postoji
- Validation na model level

### 3. Hierarchical Categories
- Recursive queries za hijerarhiju
- Breadcrumb generation
- Tree view za dashboard

### 4. Performance
- Cache menu items
- Eager loading relationships
- Database indexes

### 5. SEO
- Meta tags po stranici
- Canonical URLs
- Sitemap generation (future)

---

## 📝 Notes

- **Page Types**: Može se proširiti u budućnosti (npr. 'product_list', 'product_single')
- **Multi-language**: Može se dodati kasnije (i18n za pages)
- **Permissions**: Može se dodati (ko može da edituje stranice)
- **Versioning**: Može se dodati (history stranica)

