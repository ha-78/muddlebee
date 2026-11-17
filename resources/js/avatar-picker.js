// The avatar circle on the account page is a text field. Whatever the keyboard sends,
// keep only the last emoji, so tapping a new one replaces the old one.
const EMOJI = /[\p{Extended_Pictographic}\u{1F1E6}-\u{1F1FF}\u{20E3}]/u;

function graphemes(value) {
    if (typeof Intl.Segmenter === 'function') {
        return [...new Intl.Segmenter(undefined, { granularity: 'grapheme' }).segment(value)].map((part) => part.segment);
    }

    return [...value];
}

export function avatarPicker() {
    return {
        emoji: '',
        invalid: false,

        // Copied here because methods called from nested elements see a different $el.
        init() {
            this.emoji = this.$el.dataset.emoji ?? '';
        },

        pick(event) {
            const field = event.target;
            const last = graphemes(field.value).reverse().find((part) => EMOJI.test(part));

            this.invalid = field.value !== '' && last === undefined;

            if (last !== undefined) {
                this.emoji = last;
            }

            field.value = this.emoji;
        },
    };
}
