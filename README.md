# Muddlebee 🐝

A shared place for one household: calendar and notices, threads, lists, bookmarks and meal planning. Each part of the app has its own color, so a glance is enough to see where you are. It works in the browser and can be installed on a phone's home screen as an app.

The interface is in Swedish (English is available too).

## Tech stack

- PHP 8.5 (runs on 8.3+), Laravel 13, Fortify for sign-in
- Blade templates, Tailwind CSS 4, Alpine.js, Vite 8
- MySQL 8.4
- Laravel Sail (Docker) for local development
- Pest for tests

## Requirements

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or another Docker engine with Compose)
- Node.js **20.19+ or 22.12+** and npm (Vite 8 doesn't run on older versions). Check with `node -v`.
- Git

PHP and Composer don't need to be installed on your machine; they run inside Docker.

## Local setup

### 1. Get the code and the settings file

```bash
git clone <repository-url> fnof
cd fnof
cp .env.example .env
```

Open `.env` and check the ports under "Local Docker ports". Change `APP_PORT`, `FORWARD_DB_PORT` or `VITE_PORT` if another project already uses them, and keep `APP_URL` in step with `APP_PORT` (for example `APP_PORT=8009` → `APP_URL=http://localhost:8009`).

### 2. Install the PHP packages

The `./sail` command lives in `vendor/`, which doesn't exist yet in a fresh clone. Install the packages once with a temporary Composer container:

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

### 3. Start the containers

```bash
./sail up -d
```

The first start builds the app image and creates the MySQL database and user from `.env` (plus a separate `testing` database for the tests). It takes a few minutes the first time.

### 4. Prepare the app

```bash
./sail artisan key:generate
./sail artisan migrate
```

### 5. Build the frontend

npm runs directly on your machine, not through Sail:

```bash
npm install
npm run build
```

While working on views, CSS or JS, run the dev server instead; it reloads the page when files change:

```bash
npm run dev
```

### 6. Create the first user (God user)

There is no open sign-up. The first user is created from the command line:

```bash
./sail artisan users:create-god
```

It asks for a name, an email and a password (twice). You can also pass them as options:

```bash
./sail artisan users:create-god --name="Hanna" --email="hanna@example.com"
```

Leave out `--password` so the password isn't saved in your shell history; the command asks for it instead.

The God user is added to the first household, which is created (named after the user) if there is none yet. From there, sign in at `APP_URL` and invite the rest of the household under Account → Users.

### 7. Open the app

Go to `APP_URL` from your `.env`, for example http://localhost:8000.

## Everyday commands

| What | Command |
| --- | --- |
| Start / stop the containers | `./sail up -d` / `./sail stop` |
| Run migrations | `./sail artisan migrate` |
| Run all tests | `./sail artisan test` |
| Run one test file | `./sail artisan test tests/Feature/MealsTest.php` |
| Format PHP code | `./sail pint` |
| Frontend dev server | `npm run dev` |
| Build the frontend | `npm run build` |
| Open a shell in the app container | `./sail shell` |
| Open MySQL | `./sail mysql` |

Tests use their own `testing` database, so they never touch your local data.

## Roles and permissions

Each person has a role in a household:

- **God:** full access, above admins. Created with `users:create-god`.
- **Admin:** looks after a household: invites people, chooses roles and what each person may do, and can create a link for a new password.
- **Member:** uses the household.
- **Reader:** can read, but not write.

On top of the role, admins can turn on single permissions per person (for example planning meals or creating notices).

## Project structure

- `app/Services` — business logic; controllers stay thin
- `app/Http/Requests` — validation (Form Requests)
- `app/Policies` — who may do what
- `app/Enums` — sections, roles, permissions, meal statuses
- `resources/views/components` — Blade components, `components/ui` for the shared building blocks
- `resources/lang/sv`, `resources/lang/en` — all texts
- `resources/js` — small Alpine.js components

## Troubleshooting

- **A port is already in use:** change `APP_PORT`, `FORWARD_DB_PORT` or `VITE_PORT` in `.env`, update `APP_URL`, then `./sail down && ./sail up -d`.
- `npm run build` **fails with** `styleText` **or another** `node:` **error:** Node.js is too old. Install Node 22 (for example `nvm install 22`), then `rm -rf node_modules && npm install`.
- **Database errors after changing** `DB_`* **in** `.env`**:** MySQL only creates the database and user on its very first start. Remove its volume and start again (this deletes the local data): `./sail down -v && ./sail up -d && ./sail artisan migrate`.
- **The page has no styling:** run `npm run build`, or keep `npm run dev` running.
