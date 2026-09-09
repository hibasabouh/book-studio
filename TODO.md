# TODO

Working plan for the features going into the public release. The README
roadmap is the public-facing summary; this file is the detail — ordering,
open questions, and the traps we've already identified.

## Order, and why it matters

**Cover image → rich text → grammar correction.**

The dependency that drives this: applying grammar corrections into an HTML
document is a different problem from applying them into a plain string
(offsets, node boundaries, preserving formatting around a replaced word).
Building grammar correction on plain-text chapters and *then* converting to
rich text means building it twice.

Cover image goes first because it's the cheapest and unblocks README
screenshots.

---

## 1. Cover page image

Storage infrastructure already exists — `attachments` + `attachables` is a
polymorphic many-to-many, currently wired only to `ContactMessage`. Adding
covers is wiring, not new infrastructure.

- [ ] `Attachment::books()` morphedByMany + `Book` morphToMany (or a dedicated
      single-attachment relation — decide which fits better)
- [ ] Upload endpoint + request validation, reusing `upload_file()`
- [ ] Cover replace / remove, deleting the old file
- [ ] Full-bleed cover page in all 4 mpdf templates
- [ ] Graceful layout when a book has no cover
- [ ] UI: upload control + preview on the book screen

**Security (user uploads on a public repo):**

- [ ] Explicit mime allowlist — **reject SVG** (script execution → stored XSS)
- [ ] Max dimensions + file size
- [ ] Re-encode through GD/Imagick to strip EXIF

**Tests:**

- [ ] Upload accepts valid images, rejects SVG and oversized files
- [ ] Only the book owner can set/remove a cover (policy coverage)
- [ ] Export succeeds both with and without a cover, in all 4 templates
- [ ] Removing a cover deletes the stored file

---

## 2. Rich text chapters

No column migration needed — `chapters.content` is already `longText`
nullable. The cost is entirely sanitization, PDF fidelity, and RTL.

### Scope decision (2026-09-08)

Start with **inline formatting only: bold, italic, colour**, with room to add
more effects later. **Inline images are deferred** until we find a mechanism
that fits the project.

That scoping is the right instinct, and it's worth being explicit about why:
inline images are what force the jump to real HTML plus stored-file
references plus mpdf image resolution at export time. Keeping them out lets
the whole feature stay on a much smaller markup surface.

### Open question — to discuss before building

**What's the markup mechanism?** The two ends of the range:

- **Full WYSIWYG emitting HTML** (Quill / Tiptap vanilla) — familiar to
  authors, but drags in the whole sanitization problem, the pasted-from-Word
  markup problem, and mpdf fidelity risk across 4 templates.
- **A constrained format we control** — a restricted tag subset, or
  Markdown-ish, or our own tokens, rendered to a known-safe HTML subset.
  Smaller attack surface, predictable mpdf output, more work on the editor UI.

The narrower the format, the less of the risk below actually applies. Worth
deciding deliberately rather than defaulting to a WYSIWYG.

### Known traps

- **Sanitization on the way in** is mandatory if we store HTML — allowlist
  based (HTMLPurifier / `mews/purifier`). This is the first thing a reviewer
  looks for in a public Laravel repo with an editor.
- **mpdf supports a subset of HTML/CSS.** Whatever the editor emits has to
  render correctly in all 4 templates, not just in the browser.
- **RTL + inline formatting**: `dir` on block elements, mixed LTR/RTL runs
  inside a paragraph.
- **OCR returns plain text** — decide how it lands in a formatted chapter.
- **Existing chapters are plain text** — migration path (wrap in paragraphs).

### Tasks

- [ ] Decide the markup mechanism (open question above)
- [ ] Editor UI for bold / italic / colour
- [ ] Sanitize + validate on store/update
- [ ] Render correctly in all 4 mpdf templates
- [ ] RTL verification with Arabic content
- [ ] Migrate existing plain-text chapters
- [ ] Define how OCR output enters a formatted chapter

**Tests:**

- [ ] Sanitizer strips `<script>`, event handlers, `javascript:` URLs
- [ ] Allowed formatting survives a store → load → export round trip
- [ ] Export renders formatted content in all 4 templates
- [ ] Arabic/RTL chapter with inline formatting exports correctly
- [ ] Legacy plain-text chapters still render after migration

---

## 3. Grammar / spelling correction

Do after rich text. Depends on the markup decision above.

### Open question — provider

**LanguageTool** is the natural free analogue to OCR.space (genuinely free, no
LLM needed) — but Arabic support needs verifying before we commit. If Arabic
isn't covered, the feature would silently not work in exactly the language
this project is proudest of, and we'd need a free-tier LLM instead (Groq,
Gemini) with different rate limits.

- [ ] Verify Arabic coverage for the candidate providers, then choose

### Making it worth its weight

Architecturally this is a rerun of OCR (`Http` facade → external endpoint →
text back). Two things make it more than that:

- [ ] **Accept/reject diff UI** — inline suggestions the author takes one at a
      time. This is the part that isn't a repeat of OCR.
- [ ] **Run it queued**, with status polling. Establishes the job pattern that
      queued PDF export can then inherit.

### Tasks

- [ ] `GrammarService` behind an interface, provider-swappable
- [ ] Queued job + status endpoint
- [ ] Suggestion diff UI with per-suggestion accept/reject
- [ ] Degrade gracefully with no API key configured
- [ ] Applying a correction must preserve surrounding formatting

**Tests:**

- [ ] Service handles provider success, error, and rate-limit responses (faked HTTP)
- [ ] Feature disabled cleanly when no API key is set
- [ ] Accepting a suggestion edits only the intended span
- [ ] Applying a correction preserves surrounding formatting
- [ ] Only the chapter owner can run a check (policy coverage)

---

## Cross-cutting

- [ ] Run `/security-review` before making the repo public — all three
      features widen the user-supplied-content surface
- [ ] Every feature ships with tests, or the CI badge is decoration
- [ ] Keep the README roadmap in sync as these land (it went stale once)
- [ ] README screenshots — deferred until the UI work is done; cover images
      are the change that most improves them
- [ ] Personal data / portfolio link changes in `about.blade.php` and the
      three `resources/js/i18n/*.json` files

## Later

- Inline images in chapter content (deferred — needs a mechanism that fits)
- Additional text effects beyond bold / italic / colour
- Queued PDF export with completion notification
- Public read-only book sharing links
- Full-text search across books and chapters
- Chapter version history
- Paid subscription tier via Stripe
