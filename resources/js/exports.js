/**
 * Tracks queued PDF exports across page loads.
 *
 * Tracked exports live in localStorage, so polling resumes on whichever page the
 * user opens next, and a finished export stays as a notice with a download link
 * (see partials/export-notices.blade.php) until it is downloaded or dismissed.
 */
import { fetchJson } from './auth';
import { translate } from './i18n';

const STORAGE_KEY = 'exports';

// Most exports finish within the first check, so they never flash a status strip.
const FIRST_POLL_MS = 500;
const POLL_MS = 1500;
// Still `pending` after this long usually means no queue worker is running.
const STALL_MS = 20000;
// Comfortably past the job's own timeout, so a live export is never abandoned.
const GIVE_UP_MS = 5 * 60 * 1000;
// Matches BookExport::prunable(); past this the file may already be gone.
const KEEP_MS = 24 * 60 * 60 * 1000;

const ACTIVE = ['pending', 'processing'];

function load() {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY)) ?? [];
    } catch {
        return [];
    }
}

function toast(message, type) {
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { message, type } }));
}

export default {
    // { id, bookId, title, status, downloadUrl, startedAt, checks, stalled }
    items: load(),
    timer: null,

    init() {
        // Keep tabs in sync, e.g. a notice dismissed in one disappears from the others.
        window.addEventListener('storage', (e) => {
            if (e.key === STORAGE_KEY) this.items = load();
        });
    },

    get pending() {
        return this.items.filter(e => ACTIVE.includes(e.status));
    },

    get ready() {
        return this.items.filter(e => e.status === 'completed');
    },

    forBook(bookId) {
        return this.pending.find(e => e.bookId === bookId) ?? null;
    },

    /** Start following an export returned by POST /api/books/{book}/exports. */
    track(job) {
        if (!this.items.some(e => e.id === job.id)) {
            this.update(list => [...list, {
                id: job.id,
                bookId: job.book_id,
                title: job.book_title,
                status: job.status,
                downloadUrl: job.download_url,
                startedAt: Date.now(),
                checks: 0,
                stalled: false,
            }]);
        }

        this.schedule();
    },

    /** Pick up exports tracked on an earlier page; call once the user is signed in. */
    resume() {
        this.update(list => list.filter(e => Date.now() - e.startedAt < KEEP_MS));
        this.schedule();
    },

    dismiss(id) {
        this.update(list => list.filter(e => e.id !== id));
    },

    clear() {
        clearTimeout(this.timer);
        this.timer = null;
        this.update(() => []);
    },

    schedule() {
        if (this.timer || !this.pending.length) return;

        const delay = this.pending.some(e => !e.checks) ? FIRST_POLL_MS : POLL_MS;
        this.timer = setTimeout(() => this.poll(), delay);
    },

    async poll() {
        for (const entry of this.pending) await this.check(entry);

        this.timer = null;
        this.schedule();
    },

    async check(entry) {
        let job;
        try {
            ({ data: job } = await fetchJson(`/api/exports/${entry.id}`, { cache: 'no-store', toastErrors: false }));
        } catch (e) {
            // The book was deleted, or the export belongs to another account; anything else is retried.
            if ([403, 404].includes(e.status)) this.dismiss(entry.id);
            return;
        }

        const elapsed = Date.now() - entry.startedAt;

        if (job.status === 'failed') {
            this.dismiss(entry.id);
            toast(job.error || translate('export.error'), 'error');
        } else if (ACTIVE.includes(job.status) && elapsed > GIVE_UP_MS) {
            this.dismiss(entry.id);
            toast(translate('export.gave_up'), 'error');
        } else {
            this.patch(entry.id, {
                status: job.status,
                title: job.book_title,
                downloadUrl: job.download_url,
                checks: entry.checks + 1,
                stalled: job.status === 'pending' && elapsed > STALL_MS,
            });
        }
    },

    patch(id, changes) {
        this.update(list => list.map(e => (e.id === id ? { ...e, ...changes } : e)));
    },

    // Always applied to the stored list, so a change made by another tab is never overwritten.
    update(fn) {
        this.items = fn(load());
        localStorage.setItem(STORAGE_KEY, JSON.stringify(this.items));
    },
};
