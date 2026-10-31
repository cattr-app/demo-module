import * as app from '@/App';
import * as login from '@/views/Auth/Login';
import * as account from '@/modules/Users/sections/account';
import * as users from '@/modules/Users/sections/users';
import Timer from './components/Timer';
import UserSelect from './components/UserSelect';

/**
 * ModuleConfig - used as module configuration section to manipulate module loading settings
 * @type {{routerPrefix: string, loadOrder: number, enabled: boolean}}
 */
export const ModuleConfig = {
    enabled: true,
    routerPrefix: 'demo',
    loadOrder: 20,
    moduleName: 'AmazingCat_DemoModule',
};

/**
 * Module init function.
 *
 * @param context
 * @param router
 * @returns {context}
 */
export function init(context, router) {
    app.config.beforeLayout = Timer;

    // Replace form on the login page with a demo account select signed in without a password
    login.config.authInput = UserSelect;
    login.config.loginEndpoint = '/demo/login';

    // Remove email and password fields in the account section
    const accountFieldsToDelete = ['email', 'password'];
    account.config.fieldsProvider = () =>
        account.fieldsProvider().filter(field => accountFieldsToDelete.indexOf(field.key) === -1);

    // Remove email and password fields on the user edit page
    const usersFieldsToDelete = ['email', 'change-password', 'password'];
    users.config.fieldsToFillProvider = () =>
        users.fieldsToFillProvider().filter(field => usersFieldsToDelete.indexOf(field.key) === -1);

    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    return context;
}
