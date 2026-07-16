# Book Studio

Author a book as a set of chapters, style it, and export it to a polished PDF — with OCR-assisted content entry, per-book typography, and Arabic/RTL support.

## Overview

Book Studio lets an authenticated user create a book, write and reorder its chapters (with drag-and-drop), and configure per-book layout and typography — template, fonts, text direction, language, font size, line height, and whether to show page numbers, a table of contents, and chapter headers. When ready, the book is exported to a PDF rendered in one of four templates (classic, elegant, manuscript, modern), including correct Arabic/RTL rendering. Chapter content can also be seeded by uploading a scanned image and running it through OCR instead of typing it out.

The app ships with a small admin back-office (dashboard stats, user management, a contact-message inbox) and supports English, Arabic, and French across both the backend and the client.

## Tech stack

- **Laravel 12** on PHP 8.2+, MySQL
- **Laravel Sanctum** for auth — stateful SPA cookie auth for the web app plus token auth for API clients, from one codebase
- **Blade + Alpine.js 3 + Tailwind v4 (Vite)** on the frontend — Blade renders page shells, Alpine + `fetch` drive the JSON API. This is a deliberate choice over a full SPA framework: the app's interaction surface (forms, drag-and-drop reorder, a settings panel) doesn't need client-side routing or a virtual DOM, so a heavier framework would add build complexity without a matching benefit
- **mpdf** for PDF generation (4 templates, RTL/Arabic-aware)
- **dedoc/scramble** for auto-generated OpenAPI docs from the existing route/request/resource definitions
- **Pest** for testing

## Key engineering decisions

- **OTP-based email verification and password reset** instead of Laravel's default signed-URL flow, because the same verification path has to work for both the cookie-based web session and token-based API clients — a short-lived code works uniformly across both, a signed URL does not.
- **Google OAuth alongside password auth**, isolated behind a dedicated `GoogleAuthService` so the rest of the auth flow (OTP, session/token issuance) doesn't need to know which identity provider was used.
- **Enum-backed casts on `BookSetting`** (template, fonts, direction, language) rather than free-form strings, so invalid layout configuration is rejected at the model boundary instead of surfacing as a broken PDF render.
- **Ownership-scoped `BookPolicy` / `ChapterPolicy` plus a single `admin` Gate**, instead of a full roles-and-permissions package — the app only has two authorization shapes (owns-the-resource, is-an-admin), so a package would add configuration surface without covering any case Policies and a Gate don't already handle.
- **OCR via a direct HTTP call to the OCR.space API** (`OcrService`, using the `Http` facade) rather than an SDK, since the integration is a single endpoint call — a dependency would outweigh the code it replaces.
- **Parallel i18n on both sides**: backend strings in `lang/{en,ar,fr}`, frontend strings in `resources/js/i18n/{en,ar,fr}.json`, kept in sync by convention so a translation change never has to touch only one layer.

## Features

- Registration/login, email verification and password reset via OTP, Google login
- Book and chapter management, including drag-and-drop chapter reordering
- OCR-assisted chapter content entry from uploaded images
- Per-book typography/layout settings and PDF export in 4 templates, with Arabic/RTL support
- Admin panel: dashboard stats, user management, contact-message inbox
- Public contact form (in progress)
- English / Arabic / French localization

## Getting started

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure a MySQL database in `.env` (`DB_DATABASE=book_studio` by default), then:

```bash
php artisan migrate
npm install
npm run build
```

For local development, `composer run dev` starts the app server, queue listener, log viewer (Pail), and Vite dev server together.

Optional environment variables, depending on which features you exercise:

- `GOOGLE_CLIENT_ID` — enables "Sign in with Google"
- `OCRSPACE_API_KEY` / `OCRSPACE_ENDPOINT` — enables OCR-assisted chapter content extraction
- `CONTACT_RECIPIENT_EMAIL` — where contact form submissions are sent

## API docs

The JSON API (`routes/api.php`) is documented automatically from its route/request/resource definitions via [Scramble](https://scramble.dedoc.co/). With the app running locally, the generated docs are available at `/docs/api`.

## Roadmap

The current codebase is a solid core (auth, book/chapter authoring, PDF export, admin panel) with no automated test coverage yet. Planned next:

- Pest feature-test suite (auth, books/chapters, export, admin)
- Queued PDF export with status polling and a completion notification
- Public, read-only book sharing links
- Book cover images
- Full-text search across books and chapters
- A paid subscription tier via Stripe
- Chapter version history
