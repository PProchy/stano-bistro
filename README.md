# Stano Bistro - Nette Application

Restaurant management application built with Nette Framework 3.3 and PHP 8.5.

## Requirements

- Docker & Docker Compose

## Quick Start

### 1. Create Local Config

```bash
cp config/local.neon.example config/local.neon
```

### 2. Start Containers

```bash
docker-compose up -d --build
docker-compose exec web composer install
```

### 3. Access the Application

- **Application**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081
- **MySQL**: localhost:3307 (user: `root`, password: `root`)

## Docker Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs -f web

# Rebuild after Dockerfile changes
docker-compose up -d --build

# Run composer commands
docker-compose exec web composer install
docker-compose exec web composer update

# Clear Nette cache
docker-compose exec web rm -rf temp/cache/*

# Access container shell
docker-compose exec web bash
```

## Database

Database is automatically initialized from `docker/init.sql` on first start.

Local admin login: `admin@example.com` / `zAFlnLISjGZBoO` (local development only).

**Connection settings** live in `config/local.neon` (not in git, see `config/local.neon.example`).
Every environment has its own `local.neon`; on production it holds only the database credentials.
Debug mode is on only when `local.neon` contains `parameters: debugMode: true`.

### Reset Database

To reset the database and re-import init.sql:

```bash
docker-compose down -v
docker-compose up -d --build
```

## Troubleshooting

### Permission Issues
```bash
docker-compose exec web chown -R www-data:www-data temp log
docker-compose exec web chmod -R 775 temp log
```

### Clear All Caches
```bash
docker-compose exec web rm -rf temp/cache/* temp/proxies/*
```

### Database Connection Issues
```bash
docker-compose ps
docker-compose logs db
```
