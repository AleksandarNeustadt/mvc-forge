# 🎨 Layout System Documentation

## Overview

Kompletno responzivni layout sistem sa sticky navbar-om, dinamičkim menu-om, i footer-om. Layout automatski centrira sadržaj na stranicama bez scroll-a i prilagođava se svim veličinama ekrana.

---

## 📐 Struktura

```
┌─────────────────────────────────────┐
│     Sticky Navbar (Fixed Top)       │  ← 64px visina
├─────────────────────────────────────┤
│                                     │
│                                     │
│        Main Content Area            │  ← flex-grow (puni prostor)
│     (Centered if no scroll)         │
│                                     │
│                                     │
├─────────────────────────────────────┤
│     Footer (Bottom)                 │  ← ~88px visina
└─────────────────────────────────────┘
```

---

## 🎯 Ključne karakteristike

### 1. **ENV Loader**
- Brand name i sve konfiguracije dolaze iz `.env` fajla
- Jednostavno upravljanje konfiguracijama
- Sigurnost: `.env` fajl je git-ignored

**Lokacija:** `core/classes/Env.php`

**Primer korišćenja:**
```php
Env::get('BRAND_NAME', 'default-value')
```

### 2. **Responsive Navbar**

**Desktop verzija:**
```
┌─────────────────────────────────────────────────────┐
│  aleksandar.pro  |  Project Blog About Contact  | 🌐 │
└─────────────────────────────────────────────────────┘
```

**Mobile verzija:**
```
┌─────────────────────────────────────┐
│  aleksandar.pro        ☰  🌐        │
└─────────────────────────────────────┘
        ▼ (hamburger menu)
┌─────────────────────────────────────┐
│  Project                            │
│  Blog                               │
│  About                              │
│  Contact                            │
└─────────────────────────────────────┘
```

**Karakteristike:**
- Sticky pozicija (uvek vidljiv)
- Brand name iz ENV
- Desktop: horizontalni menu
- Mobile: hamburger menu
- Language selector sa 30 jezika
- Hover efekti i animacije

### 3. **Navigation Menu**

Menu stavke:
- **Project** - `/[lang]/project`
- **Blog** - `/[lang]/blog`
- **About** - `/[lang]/about`
- **Contact** - `/[lang]/contact`

Automatski prelazi sa desktop na mobile layout na `md` breakpoint-u (768px).

### 4. **Footer**

**Layout:**
```
┌─────────────────────────────────────────────────────┐
│  Betrieben von Vite + PHP MVC + Tailwind            │
│                                   🔒 Privacy Policy  │
│  © 2025 aleksandar.pro. All rights reserved.        │
└─────────────────────────────────────────────────────┘
```

**Karakteristike:**
- Ne-sticky (ostaje na dnu sadržaja)
- Na stranicama bez scroll-a, donji border footera = donji border ekrana
- Responsive: stack-uje se na mobilnim uređajima
- Privacy Policy link iz ENV

### 5. **Full-Height Centering**

Layout automatski:
- Zauzima punu visinu ekrana (`100vh`)
- Centrira sadržaj vertikalno i horizontalno
- Uzima u obzir navbar (64px) i footer (88px)
- Flexbox sistem za perfektno centriranje

**Formula:**
```css
min-height: calc(100vh - 64px - 88px)
```

---

## 📱 Responsive Breakpoints

| Breakpoint | Width    | Navbar         | Menu         |
|-----------|----------|----------------|--------------|
| Mobile    | < 768px  | Compressed     | Hamburger    |
| Tablet    | 768-1024 | Full           | Horizontal   |
| Desktop   | > 1024px | Full + Spacing | Horizontal   |

**Tailwind CSS klase:**
- `sm:` - 640px
- `md:` - 768px
- `lg:` - 1024px
- `xl:` - 1280px

---

## 🔧 Konfiguracija

### `.env` fajl

```env
# Brand Settings
BRAND_NAME="aleksandar.pro"
BRAND_TAGLINE="Dark Protocol"

# Privacy & Legal
PRIVACY_POLICY_URL="https://policies.google.com/privacy"
```

### Layout struktur

**Fajlovi:**
```
core/
├── views/
│   ├── layout.php                    # Glavni layout wrapper
│   ├── components/
│   │   ├── header.php                # Navbar komponenta
│   │   └── footer.php                # Footer komponenta
│   └── pages/
│       └── under_construction.php    # Primer stranice
```

---

## 🎨 Stilizacija

### Tailwind klase

**Navbar:**
- `fixed top-0` - Sticky pozicija
- `backdrop-blur-md` - Blur efekat
- `border-b border-slate-800/50` - Donji border

**Footer:**
- `border-t border-slate-800/50` - Gornji border
- `bg-slate-950/80` - Transparentna pozadina
- `backdrop-blur-md` - Blur efekat

**Main Content:**
- `flex-grow` - Zauzima dostupan prostor
- `items-center justify-center` - Centrira sadržaj

### Custom CSS

Ako treba dodati custom stilove, dodaj ih u:
```
resources/css/app.css
```

---

## 🚀 Testiranje

### Desktop test
1. Otvori `http://aleksandar.pro/` u browseru
2. Proveri:
   - ✅ Navbar je sticky (scroll down pa up)
   - ✅ Menu stavke su horizontalne
   - ✅ Brand name je iz ENV
   - ✅ Footer je na dnu
   - ✅ Sadržaj je centriran

### Mobile test
1. Otvori DevTools (F12)
2. Toggle device toolbar (Ctrl+Shift+M)
3. Izaberi mobilni uređaj (iPhone, Pixel)
4. Proveri:
   - ✅ Hamburger menu je vidljiv
   - ✅ Menu se otvara/zatvara
   - ✅ Sve stavke su accessible
   - ✅ Footer se stack-uje vertikalno

### Responsive test
```bash
# Testiraj različite širine:
- 320px (iPhone SE)
- 375px (iPhone 12)
- 768px (iPad)
- 1024px (Desktop)
- 1920px (Full HD)
```

---

## 🐛 Troubleshooting

### Problem: Footer nije na dnu
**Rešenje:** Proveri da `<html>` i `<body>` imaju `h-full` klasu:
```html
<html class="h-full">
<body class="h-full flex flex-col">
```

### Problem: Sadržaj nije centriran
**Rešenje:** Main wrapper mora imati:
```html
<div class="flex-grow flex flex-col items-center justify-center">
```

### Problem: Hamburger menu ne radi
**Rešenje:** Proveri da je JavaScript učitan u `header.php`:
```javascript
document.getElementById('mobile-menu-toggle')
```

### Problem: Brand name nije iz ENV
**Rešenje:** Proveri da je Env loader učitan u `index.php`:
```php
Env::load(__DIR__ . '/../.env');
```

---

## 📚 Dodatne informacije

### Accessibility
- Svi interaktivni elementi imaju `aria-label`
- Hamburger menu ima `aria-expanded`
- Keyboard navigation podržan
- Focus states optimizovani

### Performance
- Backdrop blur optimizovan za GPU
- CSS transitions umesto JS animacija
- Minimal repaints/reflows
- Cache busting za assets

### Browser Support
- Chrome/Edge: ✅
- Firefox: ✅
- Safari: ✅
- Mobile browsers: ✅

---

## 🔮 Budući razvoj

Planirana poboljšanja:
- [ ] Smooth scroll behavior
- [ ] Active link highlighting
- [ ] Breadcrumb navigation
- [ ] Skip to content link
- [ ] Dark/Light theme toggle (već postoji color picker)
- [ ] Scroll progress indicator
