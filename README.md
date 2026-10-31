# Cattr Demo Module — Backend

A Laravel demo mode module for [Cattr Server](https://github.com/cattr-app/server-application). It owns the demo company and its accounts, signs visitors in without a password, imitates a team at work around the clock, serves colored placeholders instead of screenshots, and periodically resets application data.

For the demo account selector, reset banner, and interface setup, see the [frontend README](frontend/README.md).

> Use this module only on a dedicated demo instance. The reset runs automatically every three hours and clears application data and screenshots without confirmation.

## Features

- **Demo company.** The module seeder creates one login account per role (administrator, manager, company auditor, project manager, project auditor, user), a team of 14 people in timezones from Los Angeles to Auckland, and five projects with tasks, estimates, due dates and comments. A few tasks per project carry code names such as `Release "Swift Raven": final QA`, new on every reset. Passwords are random. The core seeders are not used, apart from the core's initial data. The content is in English.
- **Passwordless login.** `GET /api/demo/info` lists the keys of the accounts that can sign in and the next reset time; `POST /api/demo/login` with `{"role": "<key>"}` returns the same token response as `/auth/login`. No passwords are stored in the module, in the core, or in the client build.
- **Protected accounts.** Requests to `users/edit` cannot change the email, password, role, or active flag of a demo account, and `users/remove` refuses to delete one. Passwords are removed from every user edit request.
- **Work emulation.** Everyone except the administrator and the project auditor has a working day of about eight hours in their own timezone, with lunch, short breaks, task switches, an occasional manual meeting interval, days off and an activity profile. Every five minutes the past slot gets an interval for each worker. When fewer than three people would be at work, team members whose local time allows take an extra hour, so somebody is always online. A reset fills in the past 14 days and today at once.
- **Screenshot placeholders.** Every interval is served one of 10 colored placeholders, chosen by the interval ID. The disk (local or S3) holds 10 originals and 10 thumbnails however many intervals there are. Uploaded screenshots are discarded, and deleting an interval keeps the shared files.

## Requirements

The module runs within Cattr and uses its models, factories, services, and filters. This repository does not provide a standalone application or a separate build.

It requires a Cattr version where:

- backend modules are installed through Composer and enabled with `MODULE_BACKEND_*_ENABLED` flags;
- `cattr:reset --images` clears the `screenshots/` folder of the default disk;
- the login page supports `loginEndpoint` (used by the frontend part).

Installation requires a configured Cattr instance with a database, screenshot storage (local or S3), and PHP GD. Choose the PHP version according to the [Cattr development guide](https://github.com/cattr-app/server-application/blob/main/CONTRIBUTING.md) for your server version.

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

Optional settings:

| Variable | Default | Meaning |
| --- | --- | --- |
| `DEMO_HISTORY_DAYS` | `14` | Days of tracked time a reset fills in before today |
| `DEMO_MIN_ONLINE` | `3` | Target number of people at work. When fewer would be, team members whose local time is between 07:00 and 22:00 take extra hours; a target above what they can cover is met as far as possible |

To build a Docker image with the module, follow [Building an image with modules](https://github.com/cattr-app/server-application/blob/main/CONTRIBUTING.md#building-an-image-with-modules) in the Cattr development guide.

## Preparing Demo Data

The following command **deletes existing data**. Run it only after setting up a dedicated demo instance:

```bash
php artisan cattr:demo:reset
```

The command enables maintenance mode, calls `cattr:reset --force --images` (the core's initial data only), runs the module seeder, creates the screenshot placeholders, fills in the intervals of the past days and today, rebuilds the tracked time totals, and takes the application out of maintenance mode, even if one of the steps fails. It takes about 10 seconds.

Use the existing Cattr scheduler or configure Laravel's scheduler to run once per minute:

```cron
* * * * * cd /path/to/server-application && php artisan schedule:run >> /dev/null 2>&1
```

Do not add a second scheduler if one is already running in the application environment.

## Commands and Schedule

| Command | Action | Automatic Execution |
| --- | --- | --- |
| `php artisan cattr:demo:reset` | Clears and reseeds the demo instance and fills in the tracked time of the past days | Every three hours: 00:00, 03:00, 06:00, and so on |
| `php artisan cattr:demo:emulate` | Creates intervals for the past five-minute slot for everyone at work in it | Every five minutes, in the background, with overlapping runs prevented |
| `php artisan cattr:demo:plan [date]` | Shows every worker's periods at work for a day (local time), planned and extra hours, and the fewest people at work each hour | Manual only |

The schedule is registered in `backend/Providers/ModuleServiceProvider.php`. The reset uses the cron expression `0 */3 * * *` (`DemoReset::SCHEDULE`), rather than counting three hours from application startup. Execution times follow the application timezone (the company timezone setting); the scheduler uses `app.schedule_timezone` when it is set. The login accounts work in the application timezone, team members in their own.

The work schedule is not stored: it is derived from each worker's email and the date, so the history filled in by a reset and the intervals added later follow the same days. Resets and emulator runs share a cache lock (`demo:reset`): the emulator skips its run while a reset holds it, and a reset waits up to a minute for a running emulator and refuses to start while another reset runs. The lock needs a cache store shared by the scheduler and manual runs (the default file cache of one instance is). The emulator also skips workers who already have time in the slot. A reset started while the application is already in maintenance mode leaves it in maintenance mode.

## Verifying the Installation

From the server root, check module discovery, command and route registration, and the schedule:

```bash
php artisan module:list
php artisan list cattr:demo
php artisan route:list --path=demo
php artisan schedule:list
```

After a reset, `GET /api/demo/info` should list six accounts, the `screenshots/demo/` folder of the default disk should contain 10 placeholders and 10 thumbnails, and `php artisan cattr:demo:plan` should show at least three people at work in every hour. Interface verification is covered in the [frontend README](frontend/README.md#verifying-the-installation).

## Implementation Notes

- The screenshot service is replaced with `$app->extend()`: the core binds `ScreenshotService` in a deferred provider that is loaded after module providers and would override a plain binding.
- Login accounts are identified by their `@demo.cattr.app` emails, which cannot be changed through the API; team members use `@team.demo.cattr.app`.
- The account filters do not replace Cattr's access controls. The demo instance should contain only data intended for demonstration.

## Structure

```text
backend/
  Commands/                  # Reset, planning, and activity emulation
  Config/                    # Module settings (history days, minimum online)
  Database/Seeders/          # Demo company: accounts, team, projects, tasks
  Http/Controllers/          # Demo info and passwordless login
  Providers/                 # Command, route, schedule, filter, and service registration
  Routes/                    # /api/demo routes
  Services/                  # Screenshot placeholders, work schedule, and emulator
  Support/                   # Demo accounts and the company catalog
composer.json                # Backend metadata and autoloading
module.json                  # Cattr module manifest
```

## License

The module is released under `SSPL-1.0` — the [Server Side Public License 1.0](https://www.mongodb.com/legal/licensing/server-side-public-license).
