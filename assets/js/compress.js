/**
 * Client-side Image Compression Utility
 * Resizes and compresses images using HTML5 Canvas
 */

/**
 * Compresses an image file.
 * @param {File} file - The image file to compress
 * @param {number} maxWidth - Maximum width of the output image
 * @param {number} quality - JPEG quality (0.0 to 1.0)
 * @returns {Promise<Blob>} - Compressed image blob
 */
function compressImage(file, maxWidth = 1280, quality = 0.7) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = function (event) {
            const img = new Image();
            img.src = event.target.result;
            img.onload = function () {
                let width = img.width;
                let height = img.height;

                // Resize logic
                if (width > maxWidth) {
                    height = Math.round(height * (maxWidth / width));
                    width = maxWidth;
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                // Compress
                canvas.toBlob((blob) => {
                    resolve(blob);
                }, 'image/jpeg', quality);
            };
            img.onerror = function (error) {
                reject(error);
            };
        };
        reader.onerror = function (error) {
            reject(error);
        };
    });
}
