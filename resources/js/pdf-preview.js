/**
 * Canvas-based PDF rendering, on top of PDF.js.
 *
 * The pages are drawn by us rather than handed to the browser in an <iframe>,
 * because embedding only works when the browser has a built-in PDF viewer:
 * Chrome's "download PDF files instead of opening them" setting (and iOS
 * Safari) turns an embedded PDF into a download. A canvas always renders.
 */

import * as pdfjs from 'pdfjs-dist';
import PdfWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?worker';

// Vite bundles and instantiates the worker for us. Pointing `workerSrc` at the
// raw .mjs instead would break under `php artisan serve`, whose static MIME map
// has no entry for .mjs — the browser then rejects it as a module script.
pdfjs.GlobalWorkerOptions.workerPort = new PdfWorker();

// Widest a page is drawn, so a maximized window doesn't rasterize huge canvases.
const MAX_PAGE_WIDTH = 900;

const MIN_PAGE_WIDTH = 280;

/**
 * Draw every page of a PDF into `container`, replacing its current contents.
 * Pages appear one by one as they finish.
 *
 * @param {ArrayBuffer} data raw PDF bytes — PDF.js detaches these, so pass a fresh buffer
 * @param {HTMLElement} container
 * @param {AbortSignal} [signal] abort to stop rendering when the preview closes
 * @returns {Promise<number>} number of pages drawn
 */
export async function render(data, container, signal) {
    // Tearing down is the loading task's job, not the document proxy's.
    const task = pdfjs.getDocument({ data });
    signal?.addEventListener('abort', () => task.destroy(), { once: true });

    try {
        const doc = await task.promise;

        container.replaceChildren();
        const width = pageWidth(container);

        for (let number = 1; number <= doc.numPages; number++) {
            if (signal?.aborted) break;
            await drawPage(doc, number, container, width);
        }

        return doc.numPages;
    } catch (error) {
        // Destroying the task mid-load rejects whatever is pending; that is the
        // abort path, not a failure worth surfacing.
        if (!signal?.aborted) throw error;

        return 0;
    }
}

function pageWidth(container) {
    const padding = 32;

    return Math.max(MIN_PAGE_WIDTH, Math.min(container.clientWidth - padding, MAX_PAGE_WIDTH));
}

async function drawPage(doc, number, container, width) {
    const page = await doc.getPage(number);
    const scale = width / page.getViewport({ scale: 1 }).width;

    // Draw at device resolution, display at CSS size, so text stays sharp.
    const ratio = window.devicePixelRatio || 1;
    const viewport = page.getViewport({ scale: scale * ratio });

    const canvas = document.createElement('canvas');
    canvas.width = viewport.width;
    canvas.height = viewport.height;
    canvas.style.width = `${width}px`;
    canvas.style.height = `${viewport.height / ratio}px`;
    canvas.className = 'mx-auto mb-4 bg-white shadow-sm ring-1 ring-gray-300';
    container.append(canvas);

    await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
}
