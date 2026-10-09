# Book Studio

[![CI](https://github.com/hibasabouh/book-studio/actions/workflows/ci.yml/badge.svg)](https://github.com/hibasabouh/book-studio/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Author a book as a set of chapters, style it, and export it to a polished PDF — with OCR-assisted content entry, per-book typography, and Arabic/RTL support.

## Overview

Book Studio lets an authenticated user create a book, write and reorder its chapters (with drag-and-drop), give it a cover image, and configure per-book layout and typography — template, fonts, text direction, language, font size, line height, and whether to show page numbers, a table of contents, and chapter headers. When ready, the book is previewed in the browser and exported to a PDF rendered in one of four templates (classic, elegant, manuscript, modern), including correct Arabic/RTL rendering and a table of contents whose entries link to their chapters. Chapter content can also be seeded by uploading a scanned image and running it through OCR instead of typing it out.

The app ships with a small admin back-office (dashboard stats, user management, a contact-message inbox) and supports English, Arabic, and French across both the backend and the client.

## Screenshots

![Book Studio — write and publish beautiful books](docs/screenshots/hero.png)

| | |
| --- | --- |
| ![Library dashboard](docs/screenshots/library.png) | ![Chapter editor](docs/screenshots/chapters.png) |
| **Library** — every book on one dashboard | **Chapters** — write, edit, and reorder by drag-and-drop |
| ![Per-book typography panel](docs/screenshots/typography.png) | ![OCR-assisted chapter entry](docs/screenshots/ocr.png) |
| **Typography** — template, font pairing, direction, spacing | **OCR** — pull text from a scanned page into a chapter |
| ![Exported Arabic PDF](docs/screenshots/export-arabic.png) | ![The same chapter in a second template](docs/screenshots/templates.png) |
| **Arabic export** — correct shaping, direction, and mixed-script runs | **Templates** — one chapter, four ways to set it |
| ![Account dashboard](docs/screenshots/dashboard.png) | ![Sign in](docs/screenshots/login.png) |
| **Dashboard** — totals and recent books at a glance | **Auth** — password, Google sign-in, OTP verification |

## Tech stack

- **Laravel 12** on PHP 8.2+, MySQL
- **Laravel Sanctum** for auth — stateful SPA cookie auth for the web app plus token auth for API clients, from one codebase
- **Blade + Alpine.js 3 + Tailwind v4 (Vite)** on the frontend — Blade renders page shells, Alpine + `fetch` drive the JSON API. This is a deliberate choice over a full SPA framework: the app's interaction surface (forms, drag-and-drop reorder, a settings panel) doesn't need client-side routing or a virtual DOM, so a heavier framework would add build complexity without a matching benefit
- **mpdf** for PDF generation (4 templates, RTL/Arabic-aware, linked table of contents)
- **pdf.js** for previewing a generated export in the browser before download
- **GD** for cover images — server-side crop, resize, and re-encode
- **dedoc/scramble** for auto-generated OpenAPI docs from the existing route/request/resource definitions
- **Pest** for testing

## Key engineering decisions

- **OTP-based email verification and password reset** instead of Laravel's default signed-URL flow, because the same verification path has to work for both the cookie-based web session and token-based API clients — a short-lived code works uniformly across both, a signed URL does not.
- **Google OAuth alongside password auth**, isolated behind a dedicated `GoogleAuthService` so the rest of the auth flow (OTP, session/token issuance) doesn't need to know which identity provider was used.
- **Enum-backed casts on `BookSetting`** (template, fonts, direction, language) rather than free-form strings, so invalid layout configuration is rejected at the model boundary instead of surfacing as a broken PDF render.
- **Ownership-scoped `BookPolicy` / `ChapterPolicy` plus a single `admin` Gate**, instead of a full roles-and-permissions package — the app only has two authorization shapes (owns-the-resource, is-an-admin), so a package would add configuration surface without covering any case Policies and a Gate don't already handle.
- **Cover uploads are re-encoded, never stored as received** (`CoverImageService`): the browser sends a crop rectangle as fractions of the source, the server re-applies it to the original at full resolution and writes a fresh JPEG at A4/150dpi. SVG is rejected outright — it can carry script and would be served from our own origin — and a dimension cap bounds GD's memory use. Re-encoding is also what strips EXIF.
- **OCR via a direct HTTP call to the OCR.space API** (`OcrService`, using the `Http` facade) rather than an SDK, since the integration is a single endpoint call — a dependency would outweigh the code it replaces.
- **Parallel i18n on both sides**: backend strings in `lang/{en,ar,fr}`, frontend strings in `resources/js/i18n/{en,ar,fr}.json`, kept in sync by convention so a translation change never has to touch only one layer.

## Features

- Registration/login, email verification and password reset via OTP, Google login
- Book and chapter management, including drag-and-drop chapter reordering
- Book cover images — framed in the browser at the printed page's ratio, then cropped and re-encoded server-side and rendered as a full-bleed cover page in the export
- OCR-assisted chapter content entry from uploaded images
- Per-book typography/layout settings and PDF export in 4 templates, with Arabic/RTL support
- A generated table of contents whose entries jump to their chapter, plus a matching PDF outline for the reader's sidebar
- In-browser preview of the generated PDF before downloading it
- Queued PDF export with status polling and a completion notification
- Admin panel: dashboard stats, user management, contact-message inbox
- Public contact form, with submissions delivered by email and kept in the admin inbox
- English / Arabic / French localization

## Getting started

Requires PHP 8.2+, Composer, Node 20+, and MySQL.

```bash
git clone https://github.com/hibasabouh/book-studio.git
cd book-studio
cp .env.example .env
```

Create the database and point `.env` at it (`DB_DATABASE=book_studio` by
default), then run the one-shot setup script:

```bash
composer setup
```

That installs the PHP and JS dependencies, generates an app key, links
`storage/app/public` into `public/storage` (uploaded images are served from
there), runs the migrations, and builds the frontend assets. The equivalent
long form is:

```bash
composer install
php artisan key:generate
php artisan storage:link
php artisan migrate
npm install
npm run build
```

For local development, `composer run dev` starts the app server, queue listener, log viewer (Pail), and Vite dev server together. PDF exports run on the queue, so if you serve the app another way, also run `php artisan queue:work` — otherwise exports stay queued.

### Demo data

```bash
php artisan db:seed
```

Seeds an admin, a demo user, 8 random users, and a couple of chaptered books
per demo account — including an Arabic/RTL one, so the export path is easy to
try. The two named accounts are stable across re-seeds:

| Email | Password | Role |
| --- | --- | --- |
| `admin@bookstudio.test` | `password` | Admin |
| `user@bookstudio.test` | `password` | User |

These are local development credentials only — never seed them anywhere reachable from the internet.

### Optional configuration

Depending on which features you exercise:

- `GOOGLE_CLIENT_ID` — enables "Sign in with Google"
- `OCRSPACE_API_KEY` / `OCRSPACE_ENDPOINT` — enables OCR-assisted chapter content extraction
- `CONTACT_RECIPIENT_EMAIL` — where contact form submissions are sent
- `SANCTUM_STATEFUL_DOMAINS` — must list the host:port you serve the app from, or cookie-authenticated requests come back 401

## Tests

```bash
composer test
```

The suite runs on an in-memory SQLite database — `phpunit.xml` overrides
`DB_CONNECTION` and `DB_DATABASE`, so running the tests never touches your
development database.

Pest feature/unit coverage spans:

- Auth: registration, login, email verification, password reset, Google sign-in, rate limiting
- Books & chapters: CRUD, authorization, reordering, cover upload/crop, OCR-assisted content, per-book settings/typography, queued PDF export (including its failure paths) and preview
- Admin: dashboard stats, user management, contact-message inbox
- Contact form: submission, attachments, validation, mail delivery, throttling
- Supporting pieces: models, enums, middleware, mail rendering, seeders, the cover-image and OTP services, file helpers, and public/authenticated pages

## API docs

The JSON API (`routes/api.php`) is documented automatically from its route/request/resource definitions via [Scramble](https://scramble.dedoc.co/). With the app running locally, the generated docs are available at `/docs/api`.

## Roadmap

The current codebase is a solid core — auth, book/chapter authoring, PDF export, admin panel — with broad Pest feature coverage across auth, books/chapters, admin, contact, and the supporting services. Planned next:

- Public, read-only book sharing links
- Full-text search across books and chapters
- A paid subscription tier via Stripe
- Chapter version history

## License

Book Studio is open source under the [MIT License](LICENSE).

The fonts bundled in `storage/fonts/` are third-party works under the SIL Open
Font License 1.1, not MIT. The OFL lets them be redistributed with this project
as long as the license text and copyright notices travel with them, which they
do — see [storage/fonts/README.md](storage/fonts/README.md).
