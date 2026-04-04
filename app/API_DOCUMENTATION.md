# API Documentation

The MVC Forge API provides token-based access to authentication, users, pages, menus, blog posts, categories, tags, and languages.

## Current API Coverage

Supported now:
- authentication and bearer tokens
- user CRUD and role assignment by `role_ids`
- page/menu/post/category/tag/language CRUD
- bulk blog post creation
- featured image upload for posts
- multilingual filtering via `language_code` / `language_id`
- translation linking via `translation_group_id`

Not exposed yet through API:
- role CRUD and permission matrix management
- navigation menu item tree editing beyond assigning pages to `navbar_id`
- global site settings such as `BRAND_NAME`, `BRAND_TAGLINE`, and `SITE_LANGUAGE_MODE`
- media library listing/deletion
- public asset build/deploy actions (`npm run build`, cache purge, migrations)

## API URLs

API routes **do not require a language prefix**, but both styles are supported:

1. **Recommended:** `https://your-domain.com/api/auth/login`
2. **Also supported:** `https://your-domain.com/en/api/auth/login` or `https://your-domain.com/sr/api/auth/login`

The router detects API requests and bypasses the language segment automatically.

## Authentication

### Login
**POST** `/api/auth/login`

Issue an API token with username/email and password credentials.

**Request Body:**
```json
{
  "username": "your_username",
  "password": "your_password",
  "token_name": "My API Token",
  "expires_in": 3600
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "abc123...",
    "user": {
      "id": 1,
      "username": "your_username",
      "email": "your@email.com",
      "first_name": "John",
      "last_name": "Doe"
    },
    "expires_at": 1234567890
  }
}
```

### Register
**POST** `/api/auth/register`

Create a public user account. Newly registered users are created with `pending` status and must be approved before they can log in.

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "username": "johndoe",
  "email": "john@example.com",
  "password": "StrongPass123!",
  "newsletter": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "User registered and pending approval",
  "data": {
    "id": 2,
    "username": "johndoe",
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "slug": "johndoe",
    "status": "pending",
    "roles": []
  }
}
```

### Logout
**POST** `/api/auth/logout`

Revoke the current API token.

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

### Get Current User
**GET** `/api/auth/me`

Return the authenticated user profile.

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response:**
```json
{
  "success": true,
  "message": "User info",
  "data": {
    "id": 1,
    "username": "admin",
    "email": "admin@example.com",
    "first_name": "Admin",
    "last_name": "User",
    "status": "active",
    "roles": [
      {
        "id": 1,
        "name": "Super Admin",
        "slug": "super-admin"
      }
    ],
    "user": {
      "id": 1,
      "username": "admin",
      "email": "admin@example.com"
    }
  }
}
```

## Users

All user management endpoints require `Authorization: Bearer YOUR_TOKEN`.

### List Users
**GET** `/api/users`

### Get User
**GET** `/api/users/{id}`

### Create User
**POST** `/api/users`

**Request Body:**
```json
{
  "first_name": "Jane",
  "last_name": "Editor",
  "username": "janeeditor",
  "email": "jane@example.com",
  "password": "StrongPass123!",
  "status": "active",
  "newsletter": false,
  "role_ids": [2, 3]
}
```

### Update User
**PUT** `/api/users/{id}`

**Request Body:**
```json
{
  "first_name": "Jane",
  "last_name": "Editor",
  "username": "janeeditor",
  "email": "jane@example.com",
  "status": "active",
  "newsletter": true,
  "role_ids": [3]
}
```

### Delete User
**DELETE** `/api/users/{id}`

## Pages (Stranice)

### Multilingual and translation rules

- Use `language_code` or `language_id` when creating or updating localized content.
- `slug` and `route` must be unique inside the same language, but the same slug/route may exist in another language.
- Use the same `translation_group_id` on translated variants of the same page/post/category/tag so the public language switcher can open the matching translation.
- If `translation_group_id` is omitted, the API generates one automatically for that record.
- To add a translation later, first read the source record, copy its `translation_group_id`, and send that value in the translated create/update request.

### List Pages
**GET** `/api/pages?language_code=sr`

Lista svih stranica. Opciono filtrirati po jeziku.

**Query Parameters:**
- `language_code` (opciono) - Filter po jeziku (npr. "sr", "en", "nl")

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response:**
```json
{
  "success": true,
  "message": "Pages retrieved",
  "data": [
    {
      "id": 1,
      "title": "Home",
      "slug": "home",
      "route": "/home",
      "page_type": "custom",
      "content": "...",
      "language": {
        "id": 1,
        "code": "sr",
        "name": "Serbian"
      },
      "created_at": 1234567890,
      "updated_at": 1234567890
    }
  ]
}
```

### Get Single Page
**GET** `/api/pages/{id}`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

### Create Page
**POST** `/api/pages`

Useful fields:
- `language_code` or `language_id`
- `translation_group_id`
- `meta_title`, `meta_description`, `meta_keywords`
- `navbar_id`, `is_in_menu`, `menu_order`, `parent_page_id`

**Request Body:**
```json
{
  "title": "My Page",
  "slug": "my-page",  // opciono, generiše se iz title ako nije navedeno
  "route": "/my-page",  // opciono, generiše se iz slug ako nije navedeno
  "page_type": "custom",
  "content": "Page content...",
  "language_code": "sr",  // opciono
  "is_active": true,
  "is_in_menu": false,
  "menu_order": 0
}
```

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```

### Update Page
**PUT** `/api/pages/{id}`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```

### Delete Page
**DELETE** `/api/pages/{id}`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

## Menus (Navigacioni meniji)

Important: this API manages menu containers (`navigation_menus`). Menu items themselves are pages assigned with `navbar_id`, `is_in_menu=true`, and `menu_order`.

### List Menus
**GET** `/api/menus?language_code=sr`

### Get Single Menu
**GET** `/api/menus/{id}`

### Create Menu
**POST** `/api/menus`

**Request Body:**
```json
{
  "name": "Main Menu",
  "position": "header",
  "language_code": "sr",
  "is_active": true,
  "menu_order": 0
}
```

### Update Menu
**PUT** `/api/menus/{id}`

### Delete Menu
**DELETE** `/api/menus/{id}`

## Posts (Blog postovi)

### List Posts
**GET** `/api/posts?language_code=sr`

### Get Single Post
**GET** `/api/posts/{id}`

### Create Post
**POST** `/api/posts`

Useful fields:
- `language_code` or `language_id`
- `translation_group_id`
- `category_ids`, `tag_ids`
- `featured_image`
- `status`, `published_at`, `meta_title`, `meta_description`, `meta_keywords`

**Request Body:**
```json
{
  "title": "My Blog Post",
  "slug": "my-blog-post",
  "excerpt": "Short description",
  "content": "Full post content...",
  "language_code": "sr",
  "status": "draft",  // "draft" ili "published"
  "published_at": 1234567890,  // opciono
  "category_ids": [1, 2],  // opciono, array ID-jeva kategorija
  "tag_ids": [1, 2, 3],  // opciono, array ID-jeva tagova
  "featured_image": "path/to/image.jpg",
  "meta_title": "SEO Title",
  "meta_description": "SEO Description"
}
```

### Update Post
**PUT** `/api/posts/{id}`

### Bulk Create Posts
**POST** `/api/posts/bulk`

Creates multiple posts in one request. Existing categories/tags are reused by slug + language; missing categories/tags are auto-created.

**Request Body:**
```json
{
  "posts": [
    {
      "title": "Serbian Post",
      "slug": "serbian-post",
      "excerpt": "Short intro",
      "content": "<p>HTML content</p>",
      "language_code": "sr",
      "status": "draft",
      "categories": ["Vesti"],
      "tags": ["forgeng", "webgpu"],
      "translation_group_id": "blog-post-shared-id",
      "category_translation_group_id": "blog-category-vesti",
      "tag_translation_group_id": "blog-tag-webgpu"
    },
    {
      "title": "German Post",
      "slug": "serbian-post",
      "excerpt": "Kurzer Einstieg",
      "content": "<p>HTML Inhalt</p>",
      "language_code": "de",
      "status": "draft",
      "categories": ["Vesti"],
      "tags": ["forgeng", "webgpu"],
      "translation_group_id": "blog-post-shared-id",
      "category_translation_group_id": "blog-category-vesti",
      "tag_translation_group_id": "blog-tag-webgpu"
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Bulk operation completed",
  "data": {
    "created": [
      {
        "id": 10,
        "title": "Serbian Post",
        "slug": "serbian-post",
        "translation_group_id": "blog-post-shared-id"
      }
    ],
    "created_count": 1,
    "errors": [],
    "error_count": 0
  }
}
```

### Upload Featured Image
**POST** `/api/posts/{id}/featured-image`

Uploaduje sliku i odmah je upisuje u `featured_image` za dati post.

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Body (multipart/form-data):**
- `file` - image fajl (`jpg`, `png`, `gif`, `webp`, max 5MB)

**cURL Example:**
```bash
curl -X POST "https://forgeng.dev/api/posts/12/featured-image" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -F "file=@/absolute/path/to/image.jpg"
```

**Response:**
```json
{
  "success": true,
  "message": "Featured image uploaded",
  "data": {
    "image": {
      "url": "/storage/uploads/blog/featured_example.jpg",
      "filename": "featured_example.jpg"
    },
    "post": {
      "id": 12,
      "title": "My Blog Post",
      "featured_image": "/storage/uploads/blog/featured_example.jpg"
    }
  }
}
```

### Delete Post
**DELETE** `/api/posts/{id}`

## Categories (Kategorije)

### List Categories
**GET** `/api/categories?language_code=sr`

### Get Single Category
**GET** `/api/categories/{id}`

### Create Category
**POST** `/api/categories`

Useful fields:
- `language_code` or `language_id`
- `translation_group_id`
- `parent_id`, `sort_order`, `image`
- `meta_title`, `meta_description`

**Request Body:**
```json
{
  "name": "Technology",
  "slug": "technology",
  "description": "Tech related posts",
  "language_code": "sr",
  "parent_id": null,  // opciono, za hijerarhiju
  "sort_order": 0
}
```

### Update Category
**PUT** `/api/categories/{id}`

### Delete Category
**DELETE** `/api/categories/{id}`

## Tags (Tagovi)

### List Tags
**GET** `/api/tags?language_code=sr`

### Get Single Tag
**GET** `/api/tags/{id}`

### Create Tag
**POST** `/api/tags`

Useful fields:
- `language_code` or `language_id`
- `translation_group_id`
- `description`

**Request Body:**
```json
{
  "name": "PHP",
  "slug": "php",
  "description": "PHP related content",
  "language_code": "sr"
}
```

### Update Tag
**PUT** `/api/tags/{id}`

### Delete Tag
**DELETE** `/api/tags/{id}`

## Languages (Jezici)

### List Languages
**GET** `/api/languages`

### Get Single Language
**GET** `/api/languages/{id}`

### Get Language by Code
**GET** `/api/languages/code/{code}`

Primer: `/api/languages/code/sr`

### Create Language
**POST** `/api/languages`

Useful fields:
- `code`, `name`, `native_name`, `flag`
- `is_active`, `is_default`, `is_site_language`
- `sort_order`, `country_code`, `region_id`, `continent_id`

**Request Body:**
```json
{
  "code": "nl",
  "name": "Dutch",
  "native_name": "Nederlands",
  "flag": "nl",
  "is_active": true,
  "is_default": false,
  "sort_order": 0
}
```

### Update Language
**PUT** `/api/languages/{id}`

### Delete Language
**DELETE** `/api/languages/{id}`

## Primeri korišćenja

### Python primer

```python
import requests

# Base URL - bez jezika prefixa!
BASE_URL = 'https://aleksandar.pro/api'

# 1. Login i dobijanje tokena
print("🔐 Logovanje...")
login_response = requests.post(f'{BASE_URL}/auth/login', json={
    'username': 'your_username',
    'password': 'your_password'
})

if login_response.status_code != 200:
    print(f"❌ Greška kod logina: {login_response.status_code}")
    exit(1)

login_data = login_response.json()
if not login_data.get('success'):
    print(f"❌ Login neuspešan: {login_data.get('message')}")
    exit(1)

# VAŽNO: Sačuvaj token iz odgovora!
token = login_data['data']['token']
print(f"✅ Login uspešan! Token: {token[:20]}...")

# 2. Pripremi headers sa tokenom za sve sledeće zahteve
headers = {
    'Authorization': f'Bearer {token}',  # ⚠️ VAŽNO: Token mora biti u svakom zahtevu!
    'Accept': 'application/json',
    'Content-Type': 'application/json'
}

# 3. Lista stranica na srpskom jeziku
print("📄 Preuzimanje stranica...")
response = requests.get(f'{BASE_URL}/pages?language_code=sr', headers=headers)
if response.status_code == 200:
    pages = response.json()['data']
    print(f"✅ Pronađeno {len(pages)} stranica")
else:
    print(f"❌ Greška: {response.status_code}")

# 4. Kreiranje nove stranice na holandskom jeziku
new_page = {
    'title': 'Nieuwe Pagina',
    'content': 'Dit is de inhoud...',
    'language_code': 'nl'
}
response = requests.post(f'{BASE_URL}/pages', json=new_page, headers=headers)

# 5. Lista postova na srpskom za prevođenje
response = requests.get(f'{BASE_URL}/posts?language_code=sr', headers=headers)
posts = response.json()['data']

# 6. Kreiranje prevedenog posta na holandskom
for post in posts:
    translated_post = {
        'title': translate(post['title'], 'sr', 'nl'),  # vaša AI funkcija za prevod
        'content': translate(post['content'], 'sr', 'nl'),
        'language_code': 'nl',
        'status': 'draft'
    }
    requests.post(f'{BASE_URL}/posts', json=translated_post, headers=headers)
```

**⚠️ VAŽNO:** Token se dobija na login i **mora** biti uključen u `Authorization: Bearer TOKEN` header-u za **sve** zaštićene API zahteve!

### cURL primer

```bash
# Base URL - bez jezika prefixa!
BASE_URL="https://aleksandar.pro/api"

# Login
TOKEN=$(curl -X POST $BASE_URL/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"your_username","password":"your_password"}' \
  | jq -r '.data.token')

# Lista stranica na srpskom
curl -X GET "$BASE_URL/pages?language_code=sr" \
  -H "Authorization: Bearer $TOKEN"

# Kreiranje nove stranice
curl -X POST $BASE_URL/pages \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Nova Stranica",
    "content": "Sadržaj...",
    "language_code": "sr"
  }'
```

## Error Responses

Svi errori vraćaju sledeći format:

```json
{
  "success": false,
  "message": "Error message",
  "error": "Detailed error description"
}
```

**HTTP Status Codes:**
- `200` - Success
- `201` - Created
- `400` - Bad Request (validation errors)
- `401` - Unauthorized (invalid/missing token)
- `403` - Forbidden (banned/pending account)
- `404` - Not Found
- `500` - Server Error

## Napomene

1. Svi zaštićeni endpoint-i zahtevaju `Authorization: Bearer TOKEN` header
2. Token se može proslijediti i kao query parametar `?api_token=TOKEN` (za testiranje)
3. Jezik se može specificirati kroz `language_code` parametar (npr. "sr", "en", "nl")
4. Za filter po jeziku, koristite `?language_code=XX` query parametar
5. Tokovi ne ističu po defaultu, ali možete postaviti `expires_in` pri login-u
6. Za objavljene blog postove koristite `status=published` i `published_at` kao Unix timestamp
7. Za prevedene varijante istog sadržaja uvek prosledite isti `translation_group_id`
8. API trenutno ne može da izvrši frontend build, migracije ili Cloudflare purge; to se radi deploy komandama van API-ja

