import axios from 'axios';

let infoRequest = null;

/**
 * Demo accounts and the next reset time, requested once per page load.
 * @returns {Promise<{accounts: string[], next_reset_at: string}>}
 */
export function getDemoInfo() {
    if (!infoRequest) {
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
