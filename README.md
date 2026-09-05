# The English Channel BD — Backend

The Laravel backend for **The English Channel BD**, supporting a web platform centered on books, learning content, shopping, authentication, profiles, reading, courses, and related application workflows.

## Overview

This repository contains the server-side application and API used by the separate Next.js frontend. It is responsible for data persistence, business logic, authentication, authorization, validation, and backend integrations.

## Tech Stack

| Technology | Purpose |
| --- | --- |
| Laravel 12 | Backend/API framework |
| PHP 8.2+ | Server runtime |
| Laravel Sanctum 4.3 | API token authentication |
| Eloquent ORM | Database access |
| MySQL / relational database | Persistence |
| PHPUnit | Automated tests |
| Laravel Pint | PHP formatting |

The Composer configuration requires PHP `^8.2`, Laravel `^12.0`, and Sanctum `^4.3`. fileciteturn79file0

## Backend Responsibilities

- Authentication and user sessions/tokens
- User/profile data management
- Book and learning-content data
- Courses and reading-related backend services
- Shopping/cart/checkout backend support
- Staff/admin operations
- Validation, authorization, and persistence
- API endpoints consumed by the frontend

## Project Structure

```text
TheEnglishChannelBD_Backend/
├── app/
│   ├── Http/               # Controllers, middleware, requests
│   ├── Models/             # Eloquent models
│   └── Providers/          # Service providers
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
├── artisan
├── composer.json
├── .env.example
└── README.md
```

## Getting Started

### 1. Clone

```bash
git clone https://github.com/Sharar12/TheEnglishChannelBD_Backend.git
cd TheEnglishChannelBD_Backend
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Configure the database and other services in `.env`.

### 4. Run migrations

```bash
php artisan migrate
```

### 5. Start the API

```bash
php artisan serve --port=8000
```

## Useful Commands

```bash
php artisan serve
php artisan migrate
php artisan migrate:fresh
php artisan db:seed
php artisan route:list
php artisan optimize:clear
php artisan test
./vendor/bin/pint
```

The project's Composer scripts also include a combined local development workflow and automated test command. fileciteturn79file0

## Frontend

Frontend repository:

```text
https://github.com/Sharar12/TheEnglishChannelBD_Frontend
```

The frontend provides the public and application UI; this repository provides the server-side API and business/data layer.

## Security

- Never commit `.env` or real credentials.
- Authenticate protected API requests with Sanctum where required.
- Validate and authorize all sensitive operations server-side.
- Do not rely on frontend route guards as the final security boundary.
- Keep production database credentials and third-party API keys outside source control.

## License

This project is licensed under the license included in the repository.
