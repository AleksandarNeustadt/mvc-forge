# 🔐 MongoDB Atlas Password Setup

## 📍 Gde se Password Postavlja?

Password se postavlja **TOKOM KREIRANJA Database User-a** u MongoDB Atlas dashboard-u.

## 🔍 Da li Imate Password?

### Scenario 1: Sećate se Password-a ✅

Ako se sećate password-a koji ste postavili tokom kreiranja user-a, samo ga koristite!

### Scenario 2: Zaboravili ste Password ❌

MongoDB Atlas **NE ČUVA** stare password-e iz bezbednosnih razloga. Morate kreirati **NOVOG USER-a**.

---

## 🆕 Kako Kreirati Novog User-a (sa Password-om)

### Korak 1: Idite u Database Access

1. U MongoDB Atlas dashboard-u
2. Kliknite na **"Database Access"** u levom meniju
3. (ili idite direktno na: https://cloud.mongodb.com/v2#/security/database/users)

### Korak 2: Dodajte Novog User-a

1. Kliknite **"Add New Database User"** ili **"CREATE DATABASE USER"**
2. Unesite:
   - **Username**: (npr. `aleksandar_app_user`)
   - **Password**: 
     - Možete generisati automatski (kliknite "Autogenerate Secure Password")
     - **ILI** unesite svoj password (jak password - kombinacija slova, brojeva, karaktera)
   
   **VAŽNO:** **SAČUVAJTE PASSWORD ODMAH!** Nećete moći da ga vidite ponovo!

3. **Database User Privileges**: Odaberite **"Atlas Admin"** ili **"Read and write to any database"**

4. Kliknite **"Add User"** ili **"CREATE USER"**

### Korak 3: Sačuvajte Credentials

**ODMAH nakon kreiranja**, sačuvajte:
- ✅ Username
- ✅ Password (ako ste koristili autogenerate, kopirajte ga)
- ✅ Connection String (sa novim username-om i password-om)

---

## 🔄 Alternativa: Reset Password Postojećeg User-a

Ako želite da **resetujete password** postojećeg user-a (umesto kreiranja novog):

1. Idite u **"Database Access"**
2. Pronađite postojećeg user-a (npr. `your_username`)
3. Kliknite na **"..."** (tri tačke) pored user-a
4. Odaberite **"Edit User"**
5. Scroll down do **"Password"** sekcije
6. Kliknite **"Edit Password"**
7. Unesite novi password (ili kliknite "Autogenerate Secure Password")
8. Kliknite **"Update User"**

**VAŽNO:** Sačuvajte novi password!

---

## ✅ Finalni Connection String Format

Nakon što imate username i password, vaš connection string treba da izgleda ovako:

```
mongodb+srv://USERNAME:PASSWORD@aleksandarmain.fob5jfi.mongodb.net/DATABASE_NAME?retryWrites=true&w=majority
```

**Primer:**
```
mongodb+srv://aleksandar_app_user:mojJakPassword123!@aleksandarmain.fob5jfi.mongodb.net/aleksandar_pro?retryWrites=true&w=majority
```

**Važne napomene:**
- Zamenite `<db_password>` sa **stvarnim password-om** (bez `<>`)
- Zamenite `USERNAME` sa vašim username-om
- Dodajte **database name** pre `?` (npr. `/aleksandar_pro`)
- Password može sadržati specijalne karaktere - **NE escape-ujte ih** u connection string-u (MongoDB driver će to automatski uraditi)

---

## 🔒 Bezbednosni Saveti

1. ✅ **Koristite jak password** (min 12 karaktera, kombinacija slova, brojeva, karaktera)
2. ✅ **Sačuvajte password na sigurnom mestu** (password manager)
3. ✅ **NE commit-ujte password u Git** (koristite `.env` fajl koji je u `.gitignore`)
4. ✅ **Razmotrite različite user-e** za development i production

---

## 📝 Za Vaš Slučaj

Trenutni connection string:
```
mongodb+srv://your_username:<db_password>@aleksandarmain.fob5jfi.mongodb.net/?appName=AleksandarMain
```

**Akcije:**
1. Idite u MongoDB Atlas → Database Access
2. Pronađite user-a `your_username`
3. **ILI** kreirajte novog user-a sa password-om koji ćete znati
4. Dodajte password u connection string
5. Dodajte database name (npr. `/aleksandar_pro`) pre `?`

**Finalni format:**
```
mongodb+srv://your_username:VAŠ_PASSWORD@aleksandarmain.fob5jfi.mongodb.net/aleksandar_pro?retryWrites=true&w=majority
```

