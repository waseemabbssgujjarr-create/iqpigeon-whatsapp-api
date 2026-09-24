export function copyText(text) {
    if (navigator.clipboard?.writeText) {
        return navigator.clipboard.writeText(text);
    }

    return Promise.reject(new Error('Clipboard unavailable'));
}
