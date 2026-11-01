<template>
    <i18n class="demo" path="demo.warning" tag="div">
        <template v-slot:timer>
            <a v-html="timeBeforeDemoReset" />
        </template>
    </i18n>
</template>

<script>
    import { getDemoInfo } from '../services/demo.service';

    const RETRY_MS = 15000;

    export default {
        data() {
            return {
                timeBeforeDemoReset: '',
                nextResetAt: null,
                resetDue: false,
            };
        },

        created() {
            this.tick();
            this.interval = setInterval(this.tick, 1000);
            this.loadResetTime();
        },

        beforeDestroy() {
            // A request still in flight must not schedule another retry
            this.destroyed = true;
            clearInterval(this.interval);
            clearTimeout(this.retryTimeout);
        },

        methods: {
            async loadResetTime(refresh = false) {
                try {
                    const nextResetAt = new Date((await getDemoInfo({ refresh })).next_reset_at).getTime();

                    if (nextResetAt > Date.now()) {
                        this.nextResetAt = nextResetAt;
                        this.resetDue = false;
                        return;
                    }
                } catch (e) {
                    // The backend is down, most likely for the reset itself
                }

                if (!this.destroyed) {
                    this.retryTimeout = setTimeout(() => this.loadResetTime(true), RETRY_MS);
                }
            },

            tick() {
                const now = Date.now();

                if (this.nextResetAt !== null && this.nextResetAt <= now && !this.resetDue) {
                    // The server knows when the next reset is, the gap is not always three hours (DST)
                    this.resetDue = true;
                    this.loadResetTime(true);
                }

                let target = this.nextResetAt ?? this.browserBoundary(now);
                if (this.resetDue) {
                    target = now;
                }

                const left = Math.max(0, Math.floor((target - now) / 1000));

                const h = Math.floor(left / 3600);
                const m = Math.floor((left % 3600) / 60)
                    .toString()
                    .padStart(2, '0');
                const s = (left % 60).toString().padStart(2, '0');

                this.timeBeforeDemoReset = `${h}:${m}:${s}`;
            },

            // Fallback until the server answers: the next three-hour boundary in the browser's timezone
            browserBoundary(now) {
                const date = new Date(now);
                date.setHours(date.getHours() - (date.getHours() % 3) + 3, 0, 0, 0);

                return date.getTime();
            },
        },
    };
</script>

<style lang="scss" scoped>
    .demo {
        background: $red-200;
        font-weight: bold;
        padding: 5px;
        text-align: center;
    }
</style>
