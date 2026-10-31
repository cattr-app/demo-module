import axios from 'axios';

let infoRequest = null;

/**
 * Demo accounts and the next reset time. The response is shared by the components of a page;
 * pass refresh to request it again, e.g. after the reset time has passed.
 * @param {{refresh?: boolean}} options
 * @returns {Promise<{accounts: string[], next_reset_at: string}>}
 */
export function getDemoInfo({ refresh = false } = {}) {
    if (!infoRequest || refresh) {
        infoRequest = axios
            .get('/demo/info', { ignoreCancel: true })
            .then(({ data }) => data.data)
            .catch(error => {
                infoRequest = null;
                throw error;
            });
    }

    return infoRequest;
}
