/**
 * Copies a text to the clipboard. Used by x-ui.copy-button (bookmarks) and the invite and
 * password-reset links on the users pages.
 *
 * copy(value) copies the given value; copy() without one copies the clicked element's data-copy
 * attribute. After copying, `copied` is true for two seconds so the button can show a check mark
 * or "Kopierad". Uses the Clipboard API, with a hidden textarea as a fallback for browsers or
 * plain-http pages where the API isn't available.
 */
export function copyText() {
    return {
        copied: false,
        timer: null,

        destroy() {
            window.clearTimeout(this.timer);
        },

        async copy(value) {
            const text = String(value ?? this.$el.dataset.copy ?? '');

            try {
                if (navigator.clipboard?.writeText && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                } else {
                    fallbackCopy(text);
                }
            } catch {
                fallbackCopy(text);
            }

            this.copied = true;
            window.clearTimeout(this.timer);
            this.timer = window.setTimeout(() => {
                this.copied = false;
            }, 2000);
        },
    };
}

function fallbackCopy(text) {
    const field = document.createElement('textarea');

    field.value = text;
    field.setAttribute('readonly', '');
    field.style.position = 'fixed';
    field.style.opacity = '0';
    document.body.append(field);
    field.select();
    document.execCommand('copy');
    field.remove();
}
