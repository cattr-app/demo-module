import axios from 'axios';
import { notifyReset } from './reset.service';

// Without the app's global interceptors: retries of these background requests must not show
// error notifications or the loading bar, nor log the visitor out
const http = axios.create();

let infoRequest = null;

/**
 * Demo accounts and the next reset time. The response is shared by the components of a page;
 * pass refresh to request it again, e.g. after the reset time has passed.
 * @param {{refresh?: boolean}} options
 * @returns {Promise<{accounts: string[], next_reset_at: string}>}
 */
export function getDemoInfo({ refresh = false } = {}) {
    if (!infoRequest || refresh) {
        infoRequest = http
            .get('/demo/info', { baseURL: axios.defaults.baseURL })
            .then(({ data }) => data.data)
            .catch(error => {
                infoRequest = null;

                if (error && error.response && error.response.status === 503) {
                    notifyReset();
                }

                throw error;
            });
    }

    return infoRequest;
}
