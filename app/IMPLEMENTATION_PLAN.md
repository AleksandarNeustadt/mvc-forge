# Implementation Plan - Framework Enhancements

## Prioritet 1: Security (KRITIČNO)

### 1.1 Security Headers - CSP Enhancement ✅
- ✅ CSP već postoji u SecurityHeadersMiddleware
- Treba proveriti i poboljšati

### 1.2 Cookie Security ✅
- ✅ Secure cookies već postoje u index.php
- Proveriti da li su sve flag-ovi pravilno postavljeni

### 1.3 XSS Prevention Review
- Proveriti sve output mesta
- Osigurati da se koristi `htmlspecialchars()` ili `Security::escape()`

### 1.4 SQL Injection Review
- QueryBuilder koristi prepared statements ✅
- Proveriti sve direktne SQL upite

## Prioritet 2: Error Handling (VISOKO)

### 2.1 ExceptionHandler klasa
- Kreirati `core/exceptions/ExceptionHandler.php`
- Centralizovano rukovanje svim exception-ima
- Strukturisano logovanje

### 2.2 Error Pages
- Kreirati `mvc/views/errors/404.php`
- Kreirati `mvc/views/errors/500.php`
- Kreirati `mvc/views/errors/403.php`
- User-friendly dizajn

### 2.3 Enhanced Logging
- Strukturisani log format (JSON)
- Log levels (ERROR, WARNING, INFO, DEBUG)
- Log rotation

## Prioritet 3: Validation (VISOKO)

### 3.1 Request::validate() Implementation
- Implementirati validate metodu u Request klasi
- Dodati više validatora:
  - required, nullable
  - string, integer, float, boolean, array
  - email, url, ip, mac
  - min, max, minLength, maxLength, length
  - regex, alpha, alphaNumeric, numeric
  - date, dateTime, before, after
  - in, notIn, exists, unique
  - confirmed (za password confirmation)
  - custom rule support

## Prioritet 4: Additional Features (SREDNJI)

### 4.1 Email System
- `core/services/Email.php`
- SMTP support
- Template system
- Queue support (za budućnost)

### 4.2 File Upload Handler
- `core/http/FileUpload.php`
- Validacija (type, size, dimensions)
- Secure storage
- Virus scanning integration point

### 4.3 Caching System
- `core/cache/Cache.php`
- File-based cache
- TTL support
- Cache tags

### 4.4 Event/Listener System
- `core/events/Event.php`
- `core/events/EventDispatcher.php`
- Observer pattern

## Prioritet 5: Performance (NISKI)

### 5.1 Database Optimization
- Connection pooling review
- Query optimization helpers
- Index helpers

### 5.2 Asset Optimization
- Vite production build optimizacije
- Minification
- Compression

---

## Implementacija Redosled

1. ✅ Security Headers (već postoji, proveriti)
2. ✅ Cookie Security (već postoji, proveriti)
3. 🔄 Validation - Request::validate()
4. 🔄 ExceptionHandler
5. 🔄 Error Pages
6. 🔄 Enhanced Logging
7. Email System
8. File Upload
9. Caching
10. Event System
11. Performance optimizations

