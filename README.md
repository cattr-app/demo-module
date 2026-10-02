# Cattr Demo Module — Backend

A Laravel demo mode module for [Cattr Server](https://github.com/cattr-app/server-application). It generates demo activity and periodically resets application data.

For the demo user selector, reset banner, and interface setup, see the [frontend README](frontend/README.md).

> Use this module only on a dedicated demo instance. The reset runs automatically every three hours and clears application data and screenshots without confirmation, then reruns the Cattr seeders.

## Features

- A backend filter that removes `password` from user update requests.
- Random work plan generation for non-admin users with assigned tasks.
- Demo activity intervals with screenshots generated according to the plan every five minutes.
- A replacement screenshot service that determines the image path from the last digit of the interval ID.

## Requirements

The module runs within Cattr and uses its models, factories, seeders, services, and cache. This repository does not provide a standalone application or a separate build.

`module.json` specifies a minimum core version of `4.0`. Installation requires a configured Cattr instance with a database, accessible screenshot storage, and a working cache. Choose the PHP version according to the [Cattr development guide](https://github.com/cattr-app/server-application/blob/main/CONTRIBUTING.md) for your server version. Screenshot generation requires PHP GD.

## Installation Options

For a production deployment of a dedicated demo instance, use the Composer package `cattr/demo-module`. Choose a version compatible with your Cattr server and configure it through Cattr's backend module configuration.

The instructions below describe installation from source.

## Installation from Source

Run all commands below from the root of the **server-application** repository on a dedicated demo instance.

Clone the module into the Cattr modules directory:

```bash
git clone https://github.com/cattr-app/demo-module.git modules/CattrDemo
composer update --lock
```

Cattr's Composer configuration includes autoload definitions from `modules/*/composer.json`. This module uses the `Modules\Demo\` namespace, with its source code in `backend/`.

Add an entry to the root `modules.json`, preserving the settings for other modules:

```json
{
  "CattrDemo": true
}
```

`CattrDemo` is the backend module name from `module.json`; `demo` is its alias. In Cattr, environment configuration, local configuration, and database records can override `modules.json`. If the module was previously disabled through the database, enable it explicitly:

```bash
php artisan module:enable CattrDemo
```

After changing the configuration, restart the Cattr processes, including Octane and the scheduler, so they load the module and refresh its cached state.

## Preparing Demo Data

The following command **deletes existing data**. Run it only after setting up a dedicated demo instance:

```bash
php artisan cattr:demo:reset
```

The command enables maintenance mode, calls `cattr:reset --force --seed --images`, creates a work plan, runs the emulator, and takes the application out of maintenance mode. The Cattr core handles database cleanup and reseeding; the module has no seeders of its own.

Use the existing Cattr scheduler or configure Laravel's scheduler to run once per minute:

```cron
* * * * * cd /path/to/server-application && php artisan schedule:run >> /dev/null 2>&1
```

Do not add a second scheduler if one is already running in the application environment.

## Commands and Schedule

| Command | Action | Automatic Execution |
| --- | --- | --- |
| `php artisan cattr:demo:reset` | Clears and reseeds the demo instance, updates the plan, and runs the emulator | Every three hours: 00:00, 03:00, 06:00, and so on |
| `php artisan cattr:demo:plan` | Creates a random plan and stores it in the cache under the `usersPlan` key | During a reset; also when the emulator has no plan |
| `php artisan cattr:demo:emulate` | Creates intervals with screenshots for users currently scheduled to work | Every five minutes, in the background, with overlapping runs prevented |

The schedule is registered in `backend/Providers/ModuleServiceProvider.php`. The reset uses the cron expression `0 */3 * * *`, rather than counting three hours from application startup. Execution times follow the Laravel scheduler's time zone.

If `usersPlan` is missing or empty, `cattr:demo:emulate` generates a plan and exits the current run with code `1`. Activity generation starts on the next run with a non-empty plan. Users without assigned tasks are skipped.

## Verifying the Installation

From the server root, check module discovery, command registration, and the schedule:

```bash
php artisan module:list
php artisan list cattr:demo
php artisan schedule:list
```

Check that demo activity intervals and screenshots are created after running the emulator during the working hours specified in the plan. Interface verification is covered in the [frontend README](frontend/README.md#verifying-the-installation).

## Implementation Notes

- The user update filter does not replace Cattr's access controls. The demo instance should contain only data intended for demonstration.
- If the reset fails with an exception, the application may remain in maintenance mode: the command does not restore availability in a `finally` block. After resolving the cause, run `php artisan up`.

## Structure

```text
backend/
  Commands/                  # Reset, planning, and activity emulation
  Providers/                 # Command, schedule, filter, and service registration
  Services/                  # DemoScreenshotService
composer.json                # Backend metadata and autoloading
module.json                  # Cattr module manifest
```

## License

The module is released under `SSPL-1.0` — the [Server Side Public License 1.0](https://www.mongodb.com/legal/licensing/server-side-public-license).
