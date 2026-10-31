<template>
    <i18n class="demo" path="demo.warning" tag="div">
        <template v-slot:timer>
            <a v-html="timeBeforeDemoReset" />
        </template>
    </i18n>
</template>

<script>
    import { getDemoInfo } from '../services/demo.service';

    const PERIOD_MS = 3 * 60 * 60 * 1000;

    export default {
        data() {
            return {
                timeBeforeDemoReset: '',
                nextResetAt: null,
            };
        },

        async created() {
            this.tick();
            this.interval = setInterval(this.tick, 1000);

            try {
                this.nextResetAt = new Date((await getDemoInfo()).next_reset_at).getTime();
            } catch (e) {
                // Keep counting to the browser's three-hour boundary
            }
        },

        beforeDestroy() {
            clearInterval(this.interval);
        },

        methods: {
            tick() {
                const now = Date.now();

                if (this.nextResetAt !== null && this.nextResetAt <= now) {
                    // The reset is due: the next one is a period later
                    this.nextResetAt += PERIOD_MS;
                }

                const target = this.nextResetAt ?? this.browserBoundary(now);
                const left = Math.max(0, Math.floor((target - now) / 1000));

                const h = Math.floor(left / 3600);
                const m = Math.floor((left % 3600) / 60)
                    .toString()
                    .padStart(2, '0');
                const s = (left % 60).toString().padStart(2, '0');

                this.timeBeforeDemoReset = `${h}:${m}:${s}`;
            },

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
