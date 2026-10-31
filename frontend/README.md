# Cattr Demo Module — Frontend

A Vue 2 interface extension for [Cattr Server](https://github.com/cattr-app/server-application). It provides a demo account selector with passwordless sign-in and a reset countdown banner.

Use this extension on a dedicated demo instance together with the [Cattr Demo backend module](https://github.com/cattr-app/demo-module#readme), which generates activity and resets application data every three hours.

## Features

- A demo account selector on the login page instead of email and password fields; sign-in needs no password.
- A banner with a reset warning and countdown.
- Hidden email and password fields in the profile and user editing forms.
- English and Russian interface translations.

## Requirements

The extension runs within Cattr's frontend and uses its frontend aliases. It is not a standalone application. Choose Node.js and pnpm versions according to the [Cattr development guide](https://github.com/cattr-app/server-application/blob/main/CONTRIBUTING.md) for your server version.

## Installation

Run all commands below from the root of the **server-application** repository. The interface needs the backend part of the module: the selector loads the accounts from `GET /api/demo/info` and signs in through `POST /api/demo/login`. It also needs a Cattr version whose login page supports `loginEndpoint`.

Add the published package, or link a local checkout:

```bash
pnpm add @amazingcat/cattr-demo-module
```

```bash
pnpm link ../demo-module/frontend
```

Register it in `resources/frontend/etc/modules.config.json` (or a local overlay); the key must equal `ModuleConfig.moduleName`:

```json
{
  "AmazingCat_DemoModule": {
    "type": "package",
    "ref": "@amazingcat/cattr-demo-module"
  }
}
```

Enable it in `.env` and rebuild the interface:

```dotenv
MODULE_FRONTEND_AMAZING_CAT_DEMO_MODULE_ENABLED=true
```

```bash
pnpm prod
```

`modules.config.json` only determines what gets bundled; the flag decides whether the module is loaded. See [Installing frontend modules](https://github.com/cattr-app/server-application/blob/main/CONTRIBUTING.md#installing-frontend-modules) in the Cattr development guide.

## Verifying the Installation

After rebuilding the interface, open the login page: the demo account selector and reset banner should appear. Select an account and sign in; no password is asked for. Once the backend emulator has run during the working hours specified in its plan, check that activity and screenshots appear.

## Implementation Notes

- The timer in `components/Timer.vue` counts down to `next_reset_at` from `GET /api/demo/info` and requests it again once that time has passed, since the gap between resets changes with daylight saving time. Until the server answers, it counts to the next three-hour boundary in the browser's time zone.
- If `GET /api/demo/info` fails, for example during a reset, the account selector retries every five seconds.
- The client build contains no credentials: the selector sends only the account key.
- Hidden fields do not replace Cattr's access controls; the backend part protects the demo accounts.

## Structure

```text
components/                  # Timer and account selector
services/                    # Demo info request
locales/                     # English and Russian translations
module.init.js               # Interface extension registration
package.json                 # Frontend package metadata
```

## License

The module is released under `SSPL-1.0` — the [Server Side Public License 1.0](https://www.mongodb.com/legal/licensing/server-side-public-license).
