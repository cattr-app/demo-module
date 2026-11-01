<template>
    <div>
        <Timer />
        <ResetOverlay />
    </div>
</template>

<script>
    import Timer from './Timer';
    import ResetOverlay from './ResetOverlay';

    export default {
        name: 'DemoBar',

        components: { Timer, ResetOverlay },
    };
</script>
