# Cattr Demo Module — Backend

A Laravel demo mode module for [Cattr Server](https://github.com/cattr-app/server-application). It owns the demo accounts, signs visitors in without a password, imitates employees at work, serves colored placeholders instead of screenshots, and periodically resets application data.

For the demo account selector, reset banner, and interface setup, see the [frontend README](frontend/README.md).

> Use this module only on a dedicated demo instance. The reset runs automatically every three hours and clears application data and screenshots without confirmation.

## Features

- **Demo accounts.** The module seeder creates one account per role (administrator, manager, company auditor, project manager, project auditor, user) with random passwords, plus a project with tasks for every account except the administrator. The core seeders are not used, apart from the core's initial data.
- **Passwordless login.** `GET /api/demo/info` lists the account keys and the next reset time; `POST /api/demo/login` with `{"role": "<key>"}` returns the same token response as `/auth/login`. No passwords are stored in the module, in the core, or in the client build.
- **Protected accounts.** Requests to `users/edit` cannot change the email, password, role, or active flag of a demo account, and `users/remove` refuses to delete one. Passwords are removed from every user edit request.
- **Work emulation.** Every five minutes each scheduled account gets an interval for the past five-minute slot according to a random daily plan. After a reset, the day so far is filled in at once.
- **Screenshot placeholders.** Every interval is served one of 10 colored placeholders, chosen by the interval ID. The disk (local or S3) holds 10 originals and 10 thumbnails however many intervals there are. Uploaded screenshots are discarded, and deleting an interval keeps the shared files.

## Requirements

The module runs within Cattr and uses its models, factories, services, filters, and cache. This repository does not provide a standalone application or a separate build.

It requires a Cattr version where:

- backend modules are installed through Composer and enabled with `MODULE_BACKEND_*_ENABLED` flags;
- `cattr:reset --images` clears the `screenshots/` folder of the default disk;
- the login page supports `loginEndpoint` (used by the frontend part).

Installation requires a configured Cattr instance with a database, screenshot storage (local or S3), a working cache shared by the scheduler processes, and PHP GD. Choose the PHP version according to the [Cattr development guide](https://github.com/cattr-app/server-application/blob/main/CONTRIBUTING.md) for your server version.

## Installation

Run all commands below from the root of the **server-application** repository on a dedicated demo instance.

Install the published package:

```bash
composer require cattr/demo-module
```

For development, install a local checkout through a path repository instead (a local setting; do not commit it):

```bash
composer config repositories.demo path ../demo-module
composer require cattr/demo-module:@dev
```

Enable the module in `.env` (`CattrDemo` is the backend module name from `module.json`):

```dotenv
MODULE_BACKEND_CATTR_DEMO_ENABLED=true
```

Only the exact lowercase string `true` enables a module. `modules.json` and `php artisan module:enable` are not used by Cattr. After changing the configuration, restart the Cattr processes, including Octane and the scheduler.

To build a Docker image with the module, follow [Building an image with modules](https://github.com/cattr-app/server-application/blob/main/CONTRIBUTING.md#building-an-image-with-modules) in the Cattr development guide.

## Preparing Demo Data

The following command **deletes existing data**. Run it only after setting up a dedicated demo instance:

```bash
php artisan cattr:demo:reset
```

The command enables maintenance mode, calls `cattr:reset --force --images` (the core's initial data only), runs the module seeder, creates the screenshot placeholders, creates a work plan, fills in the intervals since midnight, and takes the application out of maintenance mode, even if one of the steps fails.

Use the existing Cattr scheduler or configure Laravel's scheduler to run once per minute:

```cron
* * * * * cd /path/to/server-application && php artisan schedule:run >> /dev/null 2>&1
```

Do not add a second scheduler if one is already running in the application environment.

## Commands and Schedule

| Command | Action | Automatic Execution |
| --- | --- | --- |
| `php artisan cattr:demo:reset` | Clears and reseeds the demo instance, updates the plan, and fills in the day so far | Every three hours: 00:00, 03:00, 06:00, and so on |
| `php artisan cattr:demo:plan` | Creates a random plan for the demo accounts and stores it in the cache under the `usersPlan` key | During a reset; also when the emulator has no plan |
| `php artisan cattr:demo:emulate` | Creates intervals for the past five-minute slot for accounts scheduled to work in it | Every five minutes, in the background, with overlapping runs prevented |

The schedule is registered in `backend/Providers/ModuleServiceProvider.php`. The reset uses the cron expression `0 */3 * * *` (`DemoReset::SCHEDULE`), rather than counting three hours from application startup. Execution times and plan hours follow the application timezone (the company timezone setting); the scheduler uses `app.schedule_timezone` when it is set.

The emulator skips slots that already have an interval and does nothing while the application is in maintenance mode, so a run that coincides with a reset neither fails nor duplicates intervals.

## Verifying the Installation

From the server root, check module discovery, command and route registration, and the schedule:

```bash
php artisan module:list
php artisan list cattr:demo
php artisan route:list --path=demo
php artisan schedule:list
```

After a reset, `GET /api/demo/info` should list six accounts, the `screenshots/demo/` folder of the default disk should contain 10 placeholders and 10 thumbnails, and the accounts scheduled for today should have intervals up to the current time. Interface verification is covered in the [frontend README](frontend/README.md#verifying-the-installation).

## Implementation Notes

- The screenshot service is replaced with `$app->extend()`: the core binds `ScreenshotService` in a deferred provider that is loaded after module providers and would override a plain binding.
- Demo accounts are identified by their `@demo.cattr.app` emails, which cannot be changed through the API.
- The account filters do not replace Cattr's access controls. The demo instance should contain only data intended for demonstration.

## Structure

```text
backend/
  Commands/                  # Reset, planning, and activity emulation
  Database/Seeders/          # Demo accounts, projects, and tasks
  Http/Controllers/          # Demo info and passwordless login
  Providers/                 # Command, route, schedule, filter, and service registration
  Routes/                    # /api/demo routes
  Services/                  # Screenshot placeholders and the work emulator
  Support/                   # Demo account catalog
composer.json                # Backend metadata and autoloading
module.json                  # Cattr module manifest
```

## License

The module is released under `SSPL-1.0` — the [Server Side Public License 1.0](https://www.mongodb.com/legal/licensing/server-side-public-license).
