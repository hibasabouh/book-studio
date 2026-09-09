/**
 * Fixed-ratio image cropper.
 *
 * The crop frame never moves — the image pans and zooms behind it, and is kept
 * large enough to always cover the frame, so the preview is exactly what gets
 * cut. `rect()` reports the framed area as fractions of the source image, which
 * the server re-applies to the original file at full resolution.
 */

const MAX_ZOOM = 4;

/**
 * Read a File into the plain descriptor `createCropper` needs. Kept free of DOM
 * nodes so it can live inside an Alpine (proxied) component.
 *
 * @param {File} file
 * @returns {Promise<{src: string, naturalWidth: number, naturalHeight: number}>}
 */
export function loadImage(file) {
    return new Promise((resolve, reject) => {
        const src = URL.createObjectURL(file);
        const image = new Image();

        image.onload = () => resolve({ src, naturalWidth: image.naturalWidth, naturalHeight: image.naturalHeight });
        image.onerror = () => {
            URL.revokeObjectURL(src);
            reject(new Error('The image could not be read.'));
        };

        image.src = src;
    });
}

/**
 * @param {{src: string, naturalWidth: number, naturalHeight: number}} source
 * @param {number} frameWidth  crop frame width in CSS pixels
 * @param {number} frameHeight crop frame height in CSS pixels
 */
export function createCropper(source, frameWidth, frameHeight) {
    const cropper = {
        source,
        frameWidth,
        frameHeight,
        scale: 1,
        minScale: 1,
        x: 0,
        y: 0,
        dragging: false,
        lastX: 0,
        lastY: 0,

        get maxScale() {
            return this.minScale * MAX_ZOOM;
        },

        get width() {
            return this.source.naturalWidth * this.scale;
        },

        get height() {
            return this.source.naturalHeight * this.scale;
        },

        get style() {
            return `width:${this.width}px;height:${this.height}px;transform:translate(${this.x}px,${this.y}px)`;
        },

        /** Fit the image to cover the frame, centred. */
        reset() {
            this.minScale = Math.max(
                this.frameWidth / this.source.naturalWidth,
                this.frameHeight / this.source.naturalHeight,
            );
            this.scale = this.minScale;
            this.x = (this.frameWidth - this.width) / 2;
            this.y = (this.frameHeight - this.height) / 2;
        },

        /** Keep the image covering the frame — no empty gutters. */
        clamp() {
            this.x = Math.min(0, Math.max(this.frameWidth - this.width, this.x));
            this.y = Math.min(0, Math.max(this.frameHeight - this.height, this.y));
        },

        /** Zoom about the frame centre, so whatever is framed stays framed. */
        zoomTo(scale) {
            const next = Math.min(this.maxScale, Math.max(this.minScale, scale));
            const factor = next / this.scale;

            this.x = this.frameWidth / 2 - (this.frameWidth / 2 - this.x) * factor;
            this.y = this.frameHeight / 2 - (this.frameHeight / 2 - this.y) * factor;
            this.scale = next;
            this.clamp();
        },

        start(event) {
            this.dragging = true;
            this.lastX = event.clientX;
            this.lastY = event.clientY;
            event.currentTarget.setPointerCapture?.(event.pointerId);
        },

        move(event) {
            if (!this.dragging) return;

            this.x += event.clientX - this.lastX;
            this.y += event.clientY - this.lastY;
            this.lastX = event.clientX;
            this.lastY = event.clientY;
            this.clamp();
        },

        end() {
            this.dragging = false;
        },

        wheel(event) {
            this.zoomTo(this.scale * (event.deltaY < 0 ? 1.1 : 1 / 1.1));
        },

        /** The framed area, as fractions of the source image. */
        rect() {
            return {
                x: -this.x / this.width,
                y: -this.y / this.height,
                width: this.frameWidth / this.width,
                height: this.frameHeight / this.height,
            };
        },

        release() {
            URL.revokeObjectURL(this.source.src);
        },
    };

    cropper.reset();

    return cropper;
}
