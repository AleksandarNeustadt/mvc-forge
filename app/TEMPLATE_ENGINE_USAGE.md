# Template Engine - Uputstvo za Korišćenje

Jednostavan i moćan template engine sličan Blade-u, ali jednostavniji i brži.

## Instalacija

Template engine je automatski dostupan u `Controller::view()` metodi. Samo koristite `.template.php` ekstenziju umesto `.php`.

## Osnovna Sintaksa

### 1. Escaped Output ({{ }})

```php
{{ $variable }}
{{ $user['name'] }}
{{ $post->title ?? 'Untitled' }}
```

**Output:** Automatski escape-uje HTML (bezbedno)

### 2. Raw Output ({!! !!})

```php
{!! $htmlContent !!}
{!! $rawMarkup !!}
```

**Output:** Ne escape-uje HTML (oprezno!)

### 3. Komentari

```php
{{-- Ovo je komentar, neće biti u output-u --}}
{{-- 
    Multi-line komentar
--}}
```

### 4. Direktive za Kontrolu Toka

#### @if, @elseif, @else, @endif

```php
@if ($user)
    <p>Pozdrav, {{ $user['name'] }}!</p>
@else
    <p>Prijavite se.</p>
@endif

@if ($count > 100)
    <p>Previše!</p>
@elseif ($count > 50)
    <p>Umereno.</p>
@else
    <p>Malo.</p>
@endif
```

#### @isset, @endisset

```php
@isset($user['email'])
    <p>Email: {{ $user['email'] }}</p>
@endisset
```

#### @empty, @endempty

```php
@empty($posts)
    <p>Nema postova.</p>
@else
    <p>Ima {{ count($posts) }} postova.</p>
@endempty
```

### 5. Petlje

#### @foreach ... @endforeach

```php
@foreach ($posts as $post)
    <article>
        <h2>{{ $post['title'] }}</h2>
        <p>{{ $post['excerpt'] }}</p>
    </article>
@endforeach

{{-- Sa ključevima --}}
@foreach ($categories as $key => $category)
    <span>{{ $key }}: {{ $category['name'] }}</span>
@endforeach
```

#### @for ... @endfor

```php
@for ($i = 0; $i < 10; $i++)
    <p>Iteracija {{ $i }}</p>
@endfor
```

#### @while ... @endwhile

```php
@php $i = 0; @endphp
@while ($i < 5)
    <p>Iteracija {{ $i }}</p>
    @php $i++; @endphp
@endwhile
```

### 6. Layout Inheritance

#### @extends - Nasleđivanje layout-a

**layout.template.php:**
```php
<!DOCTYPE html>
<html>
<head>
    <title>@yield('title', 'Default Title')</title>
</head>
<body>
    <header>
        @yield('header', 'Default Header')
    </header>
    
    <main>
        @yield('content')
    </main>
    
    <footer>
        @yield('footer', 'Default Footer')
    </footer>
</body>
</html>
```

**page.template.php:**
```php
@extends('layouts.layout')

@section('title')
    Moja Stranica
@endsection

@section('content')
    <h1>Sadržaj stranice</h1>
    <p>Ovo je sadržaj.</p>
@endsection
```

### 7. @include - Uključivanje Partial-a

```php
@include('components.header')

{{-- Sa varijablama --}}
@include('components.card', ['title' => 'Naslov', 'content' => 'Sadržaj'])

{{-- Sa array-om --}}
@include('components.user', $user)
```

**components/card.template.php:**
```php
<div class="card">
    <h3>{{ $title ?? 'Default Title' }}</h3>
    <p>{{ $content ?? '' }}</p>
</div>
```

### 8. @component - Komponente sa Slot-om

```php
@component('components.alert', ['type' => 'success'])
    <strong>Uspeh!</strong> Vaša poruka je poslata.
@endcomponent
```

**components/alert.template.php:**
```php
<div class="alert alert-{{ $type ?? 'info' }}">
    {{ $slot }}
</div>
```

**Output:**
```html
<div class="alert alert-success">
    <strong>Uspeh!</strong> Vaša poruka je poslata.
</div>
```

### 9. @php ... @endphp - Raw PHP

```php
@php
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'];
    }
@endphp

<p>Ukupno: {{ $total }}</p>
```

### 10. @auth / @guest - Autentifikacija

```php
@auth
    <p>Dobrodošli, {{ $user['name'] }}!</p>
    <a href="/logout">Odjava</a>
@endauth

@guest
    <a href="/login">Prijavite se</a>
@endguest
```

## Primeri

### Primer 1: Jednostavan View

**pages/home.template.php:**
```php
<div class="container">
    <h1>{{ $pageTitle ?? 'Dobrodošli' }}</h1>
    
    @if (!empty($posts))
        <div class="posts">
            @foreach ($posts as $post)
                <article>
                    <h2>{{ $post['title'] }}</h2>
                    <p>{{ $post['excerpt'] }}</p>
                </article>
            @endforeach
        </div>
    @else
        <p>Nema postova.</p>
    @endif
</div>
```

### Primer 2: Sa Layout-om

**layouts/app.template.php:**
```php
<!DOCTYPE html>
<html lang="{{ $lang ?? 'sr' }}">
<head>
    <meta charset="UTF-8">
    <title>@yield('title') | {{ $siteName ?? 'My Site' }}</title>
    <link rel="stylesheet" href="/dist/app.css">
</head>
<body>
    @include('components.header')
    
    <main>
        @yield('content')
    </main>
    
    @include('components.footer')
    
    <script src="/dist/app.js"></script>
</body>
</html>
```

**pages/about.template.php:**
```php
@extends('layouts.app')

@section('title')
    O Nama
@endsection

@section('content')
    <div class="page">
        <h1>O Nama</h1>
        <p>Ovo je stranica o nama.</p>
    </div>
@endsection
```

### Primer 3: Kompleksna Komponenta

**components/post-card.template.php:**
```php
<article class="post-card">
    @if (!empty($featured_image))
        <img src="{{ $featured_image }}" alt="{{ $title }}">
    @endif
    
    <div class="content">
        <h2><a href="{{ $url }}">{{ $title }}</a></h2>
        
        @if (!empty($excerpt))
            <p>{{ $excerpt }}</p>
        @endif
        
        @if (!empty($categories))
            <div class="categories">
                @foreach ($categories as $category)
                    <span class="category">{{ $category['name'] }}</span>
                @endforeach
            </div>
        @endif
        
        <div class="meta">
            @isset($published_at)
                <span class="date">{{ date('d.m.Y', $published_at) }}</span>
            @endisset
            
            <a href="{{ $url }}">Pročitaj više →</a>
        </div>
    </div>
</article>
```

**Korišćenje:**
```php
@foreach ($posts as $post)
    @include('components.post-card', $post)
@endforeach
```

## Prednosti

✅ **Jednostavniji od Blade-a** - manje kompleksnosti, lako za učenje  
✅ **Brži** - direktna kompilacija u PHP  
✅ **Bezbedan** - automatsko escape-ovanje  
✅ **Fleksibilan** - podržava raw PHP gde je potrebno  
✅ **Kompatibilan** - može se koristiti paralelno sa običnim PHP view-ovima  
✅ **Cache** - automatski keširanje kompajlovanih template-a  

## Migracija sa PHP View-ova

Da migrirate postojeći PHP view u template:

**Staro (default.php):**
```php
<?php if (!empty($page['content'])): ?>
    <div><?= htmlspecialchars($page['content']) ?></div>
<?php else: ?>
    <p>No content</p>
<?php endif; ?>
```

**Novo (default.template.php):**
```php
@if (!empty($page['content']))
    <div>{{ $page['content'] }}</div>
@else
    <p>No content</p>
@endif
```

## Napredne Tehnike

### Dinamički Include

```php
@php
    $component = 'components.' . $type;
@endphp

@include($component, $data)
```

### Conditional Include

```php
@if ($showHeader)
    @include('components.header')
@endif
```

### Nested Components

```php
@component('components.card')
    @component('components.button', ['text' => 'Klikni'])
        {{ $slot }}
    @endcomponent
@endcomponent
```

## Performance

- Template-i se kompajluju u PHP kod
- Kompajlirani template-i se keširaju u `storage/cache/views/`
- Cache se automatski invalidira kada se template promeni
- Možete očistiti cache sa `ViewEngine::clearCache()`

## Očuvanje Kompatibilnosti

Možete koristiti i `.template.php` i `.php` fajlove paralelno. Ako postoji `.template.php` verzija, ona će se koristiti; inače se koristi običan PHP view.
