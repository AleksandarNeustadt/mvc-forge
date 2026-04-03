"""
Primer Python skripte za korišćenje API-ja
"""
import requests
import json

# Base URL
BASE_URL = 'https://aleksandar.pro/api'

# 1. LOGIN - Dobijanje tokena
print("🔐 Logovanje...")
login_response = requests.post(
    f'{BASE_URL}/auth/login',
    json={
        'username': 'laponac84',
        'password': '84Lokos.'
    },
    headers={
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
)

if login_response.status_code != 200:
    print(f"❌ Greška kod logina: {login_response.status_code}")
    print(f"Odgovor: {login_response.text}")
    exit(1)

login_data = login_response.json()
if not login_data.get('success'):
    print(f"❌ Login neuspešan: {login_data.get('message')}")
    exit(1)

# Izvuci token iz odgovora
token = login_data['data']['token']
print(f"✅ Login uspešan! Token: {token[:20]}...")

# 2. KORIŠĆENJE TOKENA - Svi sledeći zahtevi moraju imati Authorization header
headers = {
    'Authorization': f'Bearer {token}',
    'Accept': 'application/json',
    'Content-Type': 'application/json'
}

# 3. PREUZIMANJE JEZIKA
print("\n🌍 Preuzimanje jezika...")
languages_response = requests.get(
    f'{BASE_URL}/languages',
    headers=headers
)

if languages_response.status_code == 200:
    languages_data = languages_response.json()
    languages = languages_data.get('data', [])
    print(f"✅ Pronađeno {len(languages)} jezika:")
    for lang in languages:
        print(f"  - {lang['code']}: {lang['name']} ({lang['native_name']})")
else:
    print(f"❌ Greška: {languages_response.status_code}")
    print(f"Odgovor: {languages_response.text}")

# 4. PREUZIMANJE STRANICA NA SRPSKOM
print("\n📄 Preuzimanje stranica na srpskom...")
pages_response = requests.get(
    f'{BASE_URL}/pages?language_code=sr',
    headers=headers
)

if pages_response.status_code == 200:
    pages_data = pages_response.json()
    pages = pages_data.get('data', [])
    print(f"✅ Pronađeno {len(pages)} stranica:")
    for page in pages[:5]:  # Prikaži prvih 5
        print(f"  - {page['title']} ({page['slug']})")
else:
    print(f"❌ Greška: {pages_response.status_code}")
    print(f"Odgovor: {pages_response.text}")

# 5. PREUZIMANJE POSTOVA NA SRPSKOM
print("\n📝 Preuzimanje postova na srpskom...")
posts_response = requests.get(
    f'{BASE_URL}/posts?language_code=sr',
    headers=headers
)

if posts_response.status_code == 200:
    posts_data = posts_response.json()
    posts = posts_data.get('data', [])
    print(f"✅ Pronađeno {len(posts)} postova:")
    for post in posts[:5]:  # Prikaži prvih 5
        print(f"  - {post['title']} (status: {post['status']})")
else:
    print(f"❌ Greška: {posts_response.status_code}")
    print(f"Odgovor: {posts_response.text}")

# 6. KREIRANJE NOVE STRANICE NA HOLANDskom (primer)
print("\n✍️  Kreiranje nove stranice na holandskom...")
new_page = {
    'title': 'Nieuwe Test Pagina',
    'content': 'Dit is de inhoud van de test pagina...',
    'language_code': 'nl',
    'is_active': True,
    'is_in_menu': False
}

create_response = requests.post(
    f'{BASE_URL}/pages',
    json=new_page,
    headers=headers
)

if create_response.status_code == 201:
    created_data = create_response.json()
    print(f"✅ Stranica kreirana: ID {created_data['data']['id']}")
else:
    print(f"❌ Greška: {create_response.status_code}")
    print(f"Odgovor: {create_response.text}")

print("\n✅ Sve operacije završene!")

