# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a personal website built with a custom PHP MVC architecture and Vite for frontend asset bundling. The project uses a multilingual routing system (Serbian, English, German) and is designed with a "Dark Protocol" theme.

## Build and Development Commands

```bash
# Development server with hot module replacement
npm run dev

# Production build (outputs to public/dist/)
npm run build
```

The build process uses Vite to bundle `resources/js/app.js` and `resources/css/app.css` into `public/dist/app.js` and `public/dist/app.css`.

## Architecture

### Request Flow

1. All requests hit `public/index.php` (front controller)
2. `Router` class parses the URI and extracts language prefix (`sr`, `en`, `de`)
3. Router determines which view to render (currently hardcoded to `under_construction`)
4. `public/index.php` includes the main layout which wraps the view
5. Layout file (`core/views/layout.php`) loads built assets from `public/dist/`

### Directory Structure

```
core/
  ├── classes/       # Core classes (Router)
  ├── controllers/   # Controller classes (MainController)
  ├── models/        # Database models (currently empty)
  └── views/
      ├── layout.php       # Main HTML wrapper
      └── pages/           # Individual page views
          ├── under_construction.php
          ├── landing.php
          └── 404.php

resources/
  ├── css/           # Source CSS files
  └── js/            # Source JavaScript files
      └── app.js     # Main JS entry point

public/
  ├── index.php      # Front controller
  └── dist/          # Built assets (git-ignored)
```

### Key Components

**Router (`core/classes/Router.php`)**
- Parses URL and extracts language prefix from first path segment
- Supported languages: `sr` (Serbian), `en` (English), `de` (German)
- Default language: `sr`
- Sets `$this->view` property to determine which page to render
- Currently routes all requests to `under_construction` view

**Layout (`core/views/layout.php`)**
- Main HTML wrapper for all pages
- Loads CSS from `/dist/app.css`
- Loads JS module from `/dist/app.js`
- Includes the determined view file
- Uses lang attribute based on router's language detection

**Frontend Assets**
- Entry point: `resources/js/app.js`
- Imports CSS and ionicons library
- Vite bundles everything into `public/dist/`
- Uses ionicons for icon components (e.g., `<ion-icon name="construct-outline">`)

### Routing System

The router currently has placeholder logic. To add new routes:

1. Modify `Router::__construct()` to parse the path and set `$this->view`
2. Create corresponding view file in `core/views/pages/`
3. The layout will automatically include the view if it exists, otherwise shows 404

Example URL patterns:
- `/` → Serbian language, under_construction view
- `/en` → English language, under_construction view
- `/sr/about` → Would route to `about` view (when routing logic is added)

## Development Notes

- Site is currently in "under construction" mode - all routes show the construction page
- The project uses a custom MVC structure without a framework
- Frontend uses Vite for modern asset bundling with HMR support
- No database connection or ORM is currently configured
- Controllers exist but are not yet integrated into the routing flow
