# Contact Permissions Setup

## Dodavanje Contact Permissions u Sistem

### 1. Sync Permissions

Pokrenite migraciju za sync permissions:

```bash
php core/database/migrations/013_sync_permissions.php
```

Ovo će automatski dodati Contact permissions u bazu:
- `contact.view` - View Contact Messages
- `contact.manage` - Manage Contact Messages (read, reply, delete)
- `contact.submit` - Submit Contact Form (requires authentication)

### 2. Dodela Permissions Role-ovima

Idite na Dashboard → Users → Roles i dodelite permissions:

**Za Admin/Super Admin role:**
- ✅ `contact.view` - mogu da vide poruke
- ✅ `contact.manage` - mogu da upravljaju porukama

**Za User role (opciono):**
- ✅ `contact.submit` - mogu da šalju poruke (već imaju jer su autentifikovani)

### 3. Guest Role (Opciono)

Ako želite da kreirate Guest role za goste:
1. Idite na Dashboard → Users → Roles → Create Role
2. Name: `Guest`
3. Slug: `guest`
4. Description: `Unauthenticated users`
5. Priority: `200` (najniži prioritet)
6. Permissions: Ne dodeljujte permissions (gosti nemaju pristup)

**Napomena:** Guest role nije neophodan jer gosti nemaju autentifikaciju i automatski nemaju pristup. Contact forma je disabled za goste i prikazuje login/register linkove.

## Kako funkcioniše

### Za Goste (neautentifikovani korisnici):
- ✅ Forma se prikazuje ali je **disabled**
- ✅ Prikazuje se **login/register** link
- ✅ Ne mogu da pošalju poruku

### Za Prijavljene Korisnike:
- ✅ Forma je **enabled**
- ✅ Mogu da pošalju poruku
- ✅ Sve zaštite su aktivne (CSRF, honeypot, rate limiting, itd.)

### Za Admin/Moderator:
- ✅ Mogu da vide poruke u Dashboard → Kontakt Poruke
- ✅ Mogu da upravljaju porukama (označe kao pročitano, odgovoreno, obrišu)

