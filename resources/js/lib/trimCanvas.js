/**
 * Trims transparent whitespace from a canvas and returns a clean data URL.
 * This ensures hand-drawn signatures sit directly on their baseline without floating.
 */
export function trimCanvasToDataUrl(canvas) {
    if (!canvas) return '';

    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;
    const imgData = ctx.getImageData(0, 0, width, height);
    const data = imgData.data;

    let minX = width;
    let minY = height;
    let maxX = 0;
    let maxY = 0;
    let found = false;

    for (let y = 0; y < height; y++) {
        for (let x = 0; x < width; x++) {
            const alpha = data[(y * width + x) * 4 + 3];
            if (alpha > 15) {
                if (x < minX) minX = x;
                if (x > maxX) maxX = x;
                if (y < minY) minY = y;
                if (y > maxY) maxY = y;
                found = true;
            }
        }
    }

    if (!found) {
        return canvas.toDataURL('image/png');
    }

    // Add 2px breathing margin
    minX = Math.max(0, minX - 2);
    minY = Math.max(0, minY - 2);
    maxX = Math.min(width, maxX + 2);
    maxY = Math.min(height, maxY + 2);

    const cropW = maxX - minX;
    const cropH = maxY - minY;

    const trimmed = document.createElement('canvas');
    trimmed.width = cropW;
    trimmed.height = cropH;
    const tCtx = trimmed.getContext('2d');
    tCtx.drawImage(canvas, minX, minY, cropW, cropH, 0, 0, cropW, cropH);

    return trimmed.toDataURL('image/png');
}
