<template>
    <div v-if="state.inProgress" class="demo-reset" role="status" aria-live="polite">
        <div class="demo-reset__card">
            <div class="demo-reset__spinner" aria-hidden="true"></div>
            <h2 v-t="'demo.reset.title'" class="demo-reset__title"></h2>
            <p v-t="'demo.reset.message'" class="demo-reset__message"></p>
        </div>
    </div>
</template>

<script>
    import { resetState } from '../services/reset.service';

    export default {
        name: 'ResetOverlay',

        data() {
            return { state: resetState };
        },
    };
</script>

<style lang="scss" scoped>
    .demo-reset {
        align-items: center;
        background: rgba(0, 0, 0, 0.45);
        display: flex;
        inset: 0;
        justify-content: center;
        padding: 16px;
        position: fixed;
        z-index: 10000;

        &__card {
            background: $color-white;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            max-width: 420px;
            padding: 40px 32px;
            text-align: center;
            width: 100%;
        }

        &__spinner {
            animation: demo-reset-spin 1s linear infinite;
            border: 4px solid rgba($blue-1, 0.2);
            border-radius: 50%;
            border-top-color: $blue-1;
            height: 48px;
            margin: 0 auto 24px;
            width: 48px;
        }

        &__title {
            color: $gray-1;
            font-size: 20px;
            margin: 0 0 8px;
        }

        &__message {
            color: $gray-2;
            margin: 0;
        }
    }

    @keyframes demo-reset-spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
