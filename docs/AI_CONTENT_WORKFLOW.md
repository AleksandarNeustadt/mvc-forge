# AI Content Workflow

This guide is written for the scenario: *"I installed MVC Forge on a domain and now I want an AI agent to create content, SEO structure, and publish it through the existing API/admin model."*

## What The AI Should Know About MVC Forge

- The backend is a PHP MVC/CMS application with API routes under `/api/*`.
- API authentication starts with `POST /api/auth/login`, then every protected request must send `Authorization: Bearer <TOKEN>`.
- Content is usually modeled through:
  - **Pages** for static pages and landing pages,
  - **Posts** for blog/project articles,
  - **Categories** and **Tags** for blog taxonomy,
  - **Menus** for navigation structures,
  - **Languages** for localized variants.
- API routes work without a language prefix, for example `https://example.com/api/pages`.
- Full endpoint documentation is available in [app/API_DOCUMENTATION.md](../app/API_DOCUMENTATION.md).

## Required Workflow Before Content Changes

Before the AI starts creating content, it should follow this order:

1. Log in through `POST /api/auth/login`.
2. Save the token from `data.token`.
3. Load existing languages with `GET /api/languages`.
4. Load existing pages, categories, tags, and menus to avoid duplicates:
   - `GET /api/pages?language_code=sr`
   - `GET /api/posts?language_code=sr`
   - `GET /api/categories?language_code=sr`
   - `GET /api/tags?language_code=sr`
   - `GET /api/menus?language_code=sr`
5. Only then create or update content.

## SEO Rules For AI-Generated Content

For each page or post, the AI should follow these rules:

- `title` should be natural, clear, and include the main search phrase.
- `slug` should be short, readable, lowercase, and free of unnecessary words.
- `route` for pages should be stable and semantic, for example `/seo-services`, `/about`, `/contact`.
- `meta_title` should be SEO-focused but not spammy.
- `meta_description` should clearly explain the page content and why someone should click.
- `excerpt` for blog posts should be a short intro that can also work in listing cards.
- `content` should be valid HTML with a clear `h2` / `h3` structure, paragraphs, lists, and internal links where useful.
- If multiple languages exist, create the primary-language version first, then localized variants.
- Do not publish with `status=published` until the content is logically complete and SEO fields are filled.

## Suggested Prompt For An AI Agent

You can copy and adapt this prompt:

```text
You are a content and SEO agent for a website powered by MVC Forge.

API documentation is in app/API_DOCUMENTATION.md, and the domain is https://forgeng.dev.
Use the existing API and do not modify PHP code unless explicitly requested.

Task:
1. Log in to /api/auth/login with the admin account I provide.
2. Load existing languages, pages, blog categories, tags, and menus.
3. Check whether the requested content already exists, so you do not create duplicates.
4. Create a new page or blog post with SEO-friendly title, slug, route, meta_title, meta_description, and HTML content.
5. If categories or tags are needed, create them before creating the post.
6. If the content should appear in navigation, create or update a menu record.
7. At the end, return a short report with created resources, IDs, public URLs, and anything left in draft status.

Content brief:
- Language: sr
- Content type: [page|post]
- Topic:
- Target search phrase:
- Tone of voice:
- Status: [draft|published]
- Special requirements:

Do not invent endpoints. If an endpoint or field is unclear from the documentation, tell me what is missing before proceeding.
```

## Example Prompt For A Landing Page

```text
Using the MVC Forge API on https://forgeng.dev, create a new SEO landing page in Serbian for the service "custom web application development".

Requirements:
- route: /izrada-web-aplikacija
- primary search phrase: izrada web aplikacija
- tone: professional, clear, no empty marketing filler
- content should include a hero intro, 3 main sections, an FAQ section, and a CTA linking to the contact page
- fill title, slug, meta_title, and meta_description
- publish the page as active, but do not add it to the menu until I review it

Before creating it, check whether a page with the same route or slug already exists.
At the end, return the page ID and public URL.
```

## Example Prompt For A Blog Post

```text
Using the MVC Forge API on https://forgeng.dev, create a Serbian blog post on the topic "How to choose a PHP MVC framework for an SEO-oriented website".

Requirements:
- keep status as draft first
- create or reuse the category "Web development"
- add tags "PHP", "MVC", "SEO", "CMS"
- write SEO-friendly title, slug, excerpt, meta_title, meta_description, and HTML content
- content should include an intro, comparison criteria, practical recommendations, and a conclusion
- avoid keyword stuffing

Before writing, load existing categories and tags to avoid duplicates.
At the end, return the post ID, slug, categories, and tags you used.
```

## Minimal API Example For An AI Agent

```bash
BASE_URL="https://forgeng.dev/api"

TOKEN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"YOUR_PASSWORD"}' \
  | php -r '$r=json_decode(stream_get_contents(STDIN), true); echo $r["data"]["token"] ?? "";')

curl -s "$BASE_URL/languages" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

curl -s "$BASE_URL/pages?language_code=sr" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

## When AI Should Not Publish Directly

For these cases, it is safer for the AI to prepare a draft first and publish only after manual review:

- homepage and main commercial landing pages,
- legal content, privacy policy, terms of service,
- highly sensitive SEO content where route/slug duplication would be costly,
- content that changes existing navigation or URL structure.

## Recommended Operating Workflow

1. Give the AI this guide, API documentation, and a content brief.
2. Let the AI inventory existing content first.
3. Ask the AI to propose the structure of new pages/posts.
4. Approve the proposal.
5. Let the AI create draft content through the API.
6. Review it in the admin panel.
7. Only then let the AI switch `status` to `published` or change menu placement.
