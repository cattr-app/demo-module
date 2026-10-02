# Cattr Demo Module — Frontend

A Vue 2 interface extension for [Cattr Server](https://github.com/cattr-app/server-application). It provides a demo user selector and a reset countdown banner.

Use this extension on a dedicated demo instance together with the [Cattr Demo backend module](https://github.com/cattr-app/demo-module#readme), which generates activity and resets application data every three hours.

## Features

- A demo user selector on the login page instead of email and password fields.
- A banner with a reset warning and countdown.
- Hidden email and password fields in the profile and user editing forms.
- English and Russian interface translations.

## Requirements

The extension runs within Cattr's frontend and uses its frontend aliases. It is not a standalone application. Choose Node.js and pnpm versions according to the [Cattr development guide](https://github.com/cattr-app/server-application/blob/main/CONTRIBUTING.md) for your server version.

## Installation Options

For a production deployment of a dedicated demo instance, use the published npm package `@amazingcat/cattr-demo-module`. Choose a version compatible with your Cattr server and register it through Cattr's frontend module configuration.

The instructions below describe installation from source.

## Installation from Source

Run all commands below from the root of the **server-application** repository. These instructions assume the module repository is already checked out at `modules/CattrDemo`.

For a local setup, place the module's frontend in the `vendor_modules` tree using a symbolic link:

```bash
mkdir -p resources/frontend/vendor_modules/AmazingCat
ln -s ../../../../modules/CattrDemo/frontend resources/frontend/vendor_modules/AmazingCat/DemoModule
```

Add an entry to `resources/frontend/etc/modules.local.json`, preserving the existing settings:

```json
{
  "AmazingCat_DemoModule": {
    "type": "local",
    "ref": "AmazingCat_DemoModule",
    "enabled": true
  }
}
```

The `modules.local.json` file is intended for local configuration and is ignored by Git in Cattr. To keep the settings in the demo instance's repository, use `resources/frontend/etc/modules.config.json` or the configuration for the relevant environment.

Check the `DEMO_CREDENTIALS` list in the server's `resources/frontend/etc/demo.credentials.js`. Each entry contains `user` (the name shown in the list), `email`, and `password`; these credentials must match the users created by the Cattr seeders. This list is included in the client build, so use demo accounts only.

Install dependencies and rebuild the interface:

```bash
pnpm install
pnpm prod
```

This source installation does not require publishing the package to a registry.

## Verifying the Installation

After rebuilding the interface, open the login page: the demo user selector and reset banner should appear. Select a user and verify sign-in. Once the backend emulator has run during the working hours specified in its plan, check that activity and screenshots appear.

## Implementation Notes

- The timer in `components/Timer.vue` counts down to the next three-hour period boundary in the browser's time zone. It does not receive the next reset time from the server. For the countdown to match the backend schedule, the browser and scheduler time zones must have matching three-hour period boundaries.
- Hidden fields do not replace Cattr's access controls. The demo instance should contain only data intended for demonstration.
- Demo credentials are included in the client build. Use demo accounts only.

## Structure

```text
components/                  # Timer and user selector
locales/                     # English and Russian translations
module.init.js               # Interface extension registration
package.json                 # Frontend package metadata
```

## License

The module is released under `SSPL-1.0` — the [Server Side Public License 1.0](https://www.mongodb.com/legal/licensing/server-side-public-license).
