/**
 * The meal planner on the "Planerat" tab. Meals are dragged by their grip between day zones
 * ([data-zone="Y-m-d"]) and the shortlist ([data-zone=""]) with @alpinejs/sort. A drop calls the
 * plan route (PUT, or DELETE back to the shortlist); on success a move between days stays in place,
 * a move to or from the shortlist reloads the page so the meal gets its row for the new place.
 * On failure the meal goes back where it came from and the error is shown.
 */
export function mealPlanner() {
    return {
        error: null,
        pending: false,

        init() {
            const highlight = this.$root.dataset.highlight;

            if (highlight) {
                this.$nextTick(() => document.getElementById(`day-${highlight}`)?.scrollIntoView({ block: 'center' }));
            }
        },

        zone(day) {
            return this.$root.querySelector(`[data-zone="${day}"]`);
        },

        place(card, day) {
            const zone = this.zone(day);

            if (zone && card.parentElement !== zone) {
                zone.insertBefore(card, zone.querySelector('[data-empty]'));
            }
        },

        refreshEmpty() {
            this.$root.querySelectorAll('[data-zone]').forEach((zone) => {
                zone.querySelector('[data-empty]')?.classList.toggle('hidden', zone.querySelector('[data-meal-id]') !== null);
            });
        },

        async drop(id, target) {
            const card = this.$root.querySelector(`[data-meal-id="${id}"]`);

            if (! card) {
                return;
            }

            const origin = card.dataset.day ?? '';

            if (origin === target || this.pending) {
                this.place(card, origin);
                this.refreshEmpty();

                return;
            }

            const toShortlist = target === '';
            this.pending = true;
            this.error = null;
            this.place(card, target);
            this.refreshEmpty();
            card.classList.add('opacity-60');

            try {
                const response = await fetch(toShortlist ? card.dataset.unplanUrl : card.dataset.planUrl, {
                    method: toShortlist ? 'DELETE' : 'PUT',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: toShortlist ? undefined : JSON.stringify({ date: target }),
                });

                if (response.status === 401 || response.status === 419) {
                    window.location.reload();

                    return;
                }

                if (! response.ok) {
                    const payload = await response.json().catch(() => ({}));
                    this.fail(card, origin, payload.message);

                    return;
                }

                if (origin === '' || toShortlist) {
                    window.location.reload();

                    return;
                }

                card.dataset.day = target;
            } catch {
                this.fail(card, origin);
            } finally {
                card.classList.remove('opacity-60');
                this.pending = false;
            }
        },

        fail(card, origin, message) {
            this.place(card, origin);
            this.refreshEmpty();
            this.error = message || this.$root.dataset.failed;
        },
    };
}
