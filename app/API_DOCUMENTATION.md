# API Dokumentacija

API omogućava pristup sadržaju (pages, menus, posts, categories, tags, languages) preko token autentifikacije.

## Važno - API URL-ovi

API rute **ne zahtevaju jezik prefix** u URL-u. Možete pristupiti API-ju na dva načina:

1. **Direktno (preporučeno):** `https://aleksandar.pro/api/auth/login`
2. **Sa jezikom (takođe radi):** `https://aleksandar.pro/de/api/auth/login` ili `https://aleksandar.pro/sr/api/auth/login`

Ruter automatski detektuje API rute i preskače jezik prefix, tako da oba pristupa rade identično.

## Autentifikacija

### Login
**POST** `/api/auth/login`

Dobija API token koristeći username/password.

**Request Body:**
```json
{
  "username": "your_username",
  "password": "your_password",
  "token_name": "My API Token",  // opciono
  "expires_in": 3600  // opciono, sekundi (null = nikad ne ističe)
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

### Logout
**POST** `/api/auth/logout`

Poništava (revoke) trenutni API token.

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

### Get Current User
**GET** `/api/auth/me`

Dobija informacije o trenutno autentifikovanom korisniku.

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

## Pages (Stranice)

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

### Delete Post
**DELETE** `/api/posts/{id}`

## Categories (Kategorije)

### List Categories
**GET** `/api/categories?language_code=sr`

### Get Single Category
**GET** `/api/categories/{id}`

### Create Category
**POST** `/api/categories`

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

