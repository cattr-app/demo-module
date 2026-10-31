import Vue from 'vue';
import axios from 'axios';
import { getDemoInfo } from './demo.service';

const POLL_MS = 3000;

/** Whether the backend answered 503: the demo is being reset */
export const resetState = Vue.observable({ inProgress: false });

let installed = false;

/**
 * Watch API responses for 503 (the reset runs in maintenance mode), show the reset overlay and
 * reload the page once the backend is back: the data and sessions were replaced.
 */
export function watchForReset() {
    if (installed) {
        return;
    }
    installed = true;

    axios.interceptors.response.use(undefined, error => {
        if (error && error.response && error.response.status === 503) {
            notifyReset();
        }

        return Promise.reject(error);
    });
}

/** Show the overlay and wait for the backend to come back */
export function notifyReset() {
    if (resetState.inProgress) {
        return;
    }

    resetState.inProgress = true;
    setTimeout(poll, POLL_MS);
}

async function poll() {
    try {
        await getDemoInfo({ refresh: true });
        window.location.reload();
    } catch (e) {
        setTimeout(poll, POLL_MS);
    }
}
