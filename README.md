# AJE Challenge Solution

This solution keeps the challenge as a Symfony application with a React SPA frontend, exactly in the spirit of the original README. It implements:

- registration
- account confirmation through a persisted email file in `var/emails`
- login
- note creation
- note search by title/content text
- filtering by status
- filtering by category

## Run

```bash
cp .env.dist .env
composer install
yarn install
docker-compose up -d
php bin/console doctrine:migrations:migrate --no-interaction
yarn watch
```

Then open `http://localhost:81/`.

## Flow

1. Register a user.
2. Open the generated file in `var/emails/`.
3. Visit the verification URL inside that file.
4. Log in.
5. Create notes and filter them from the SPA.

## Architecture notes

- Backend exposes JSON endpoints under `/api`.
- Frontend is a single React SPA mounted at `/`.
- Authentication uses Symfony Security with an entity-backed user provider.
- Verification emails are intentionally stored as files to match the challenge requirement.