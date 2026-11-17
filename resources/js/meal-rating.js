/**
 * A wishlist meal's ratings: the average stars and count on the row, and the user's own rating in the
 * expanded row. Starts from the data-* attributes on its element (see x-meals.rating); picking a star
 * saves with PUT, "Ta bort mitt betyg" with DELETE, and the JSON answer (MealRatingService::summary)
 * updates everything in place. On failure the own rating goes back and an error shows.
 * The settings are copied from the scope element's data-* attributes in init(), where this.$el is that
 * element. Later, inside handlers and x-text, this.$el is the bound element and this.$root the nearest
 * x-data root (the meal row's own { open } state), so neither can be used to read them.
 */
export function mealRating() {
    return {
        rating: null,
        average: null,
        stars: 3,
        count: 0,
        pending: false,
        error: null,
        settings: {},

        init() {
            const data = this.$el.dataset;

            this.settings = { ...data };
            this.rating = data.rating ? Number(data.rating) : null;
            this.average = data.average ? Number(data.average) : null;
            this.stars = Number(data.stars);
            this.count = Number(data.count);
        },

        get unrated() {
            return this.count === 0;
        },

        get countLabel() {
            const data = this.settings;

            if (this.unrated) {
                return data.labelNone;
            }

            return (this.count === 1 ? data.labelOne : data.labelMany).replace(':count', this.count);
        },

        get srLabel() {
            const data = this.settings;

            if (this.unrated) {
                return data.labelNone;
            }

            const average = new Intl.NumberFormat(document.documentElement.lang || undefined, { maximumFractionDigits: 1 }).format(this.average);

            return `${data.labelAverage.replace(':average', average)}, ${this.countLabel}`;
        },

        set(value) {
            if (value === this.rating) {
                return;
            }

            this.send('PUT', { rating: value }, value);
        },

        clear() {
            this.send('DELETE', null, null);
        },

        async send(method, body, optimistic) {
            if (this.pending) {
                return;
            }

            const previous = this.rating;
            this.rating = optimistic;
            this.pending = true;
            this.error = null;

            try {
                const response = await fetch(this.settings.url, {
                    method,
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: body === null ? undefined : JSON.stringify(body),
                });

                if (response.status === 401 || response.status === 419) {
                    window.location.reload();

                    return;
                }

                if (! response.ok) {
                    throw new Error(String(response.status));
                }

                const summary = await response.json();

                this.rating = summary.rating;
                this.average = summary.average;
                this.stars = summary.stars;
                this.count = summary.count;
            } catch {
                this.rating = previous;
                this.error = this.settings.failed;
            } finally {
                this.pending = false;
            }
        },
    };
}
