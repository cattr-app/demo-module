<template>
    <div>
        <div class="input-group">
            <p v-t="'demo.auth_message'"></p>

            <at-select v-model="role">
                <at-option v-for="account in accounts" :key="account" :value="account">
                    {{ $t(`demo.accounts.${account}`) }}
                </at-option>
            </at-select>
        </div>

        <div class="input-group">
            <LanguageSelector :value="language" @setLanguage="setLanguage" />
        </div>
    </div>
</template>

<script>
    import i18n from '@/i18n';
    import { getUserLang, getLangCookie, setLangCookie } from '@/i18n';
    import LanguageSelector from '@/components/LanguageSelector';
    import { getDemoInfo } from '../services/demo.service';

    export default {
        name: 'UserSelect',

        components: {
            LanguageSelector,
        },

        data() {
            return {
                accounts: [],
                role: null,
            };
        },

        computed: {
            language() {
                return getLangCookie() || getUserLang();
            },
        },

        watch: {
            role(value) {
                // Posted to the demo login endpoint (see module.init.js), no password is needed
                this.$emit('change', { role: value });
            },
        },

        async mounted() {
            try {
                ({ accounts: this.accounts } = await getDemoInfo());
                this.role = this.accounts[0] ?? null;
            } catch (e) {
                this.accounts = [];
            }
        },

        methods: {
            setLanguage(lang) {
                i18n.locale = lang;
                setLangCookie(lang);
            },
        },
    };
</script>

<style lang="scss" scoped>
    .input-group p {
        text-align: center;
    }
</style>
