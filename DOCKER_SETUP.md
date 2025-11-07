# Docker Setup Guide for Laravel Relations

This guide explains how to set up and run the Laravel application using Docker.

## Prerequisites

- Docker Desktop (Windows/Mac) or Docker Engine (Linux)
- Docker Compose v2
- Git

## Quick Start (Local Development)

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd laravel-relations
   ```

2. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

3. **Generate application key**
   ```bash
   docker compose run --rm app php artisan key:generate
   ```

4. **Start services**
   ```bash
   docker compose up -d --build
   ```

5. **Run migrations**
   ```bash
   docker compose exec app php artisan migrate
   ```

6. **Create storage link**
   ```bash
   docker compose exec app php artisan storage:link
   ```

7. **Access the application**
   - Web: http://localhost:8080
   - MySQL: localhost:3306
   - Redis: localhost:6379

## Docker Compose Files

- `docker-compose.yml` - Local development
- `docker-compose.staging.yml` - Staging environment
- `docker-compose.production.yml` - Production environment

## Services

### App (PHP-FPM)
- Image: Custom built from Dockerfile
- Port: 9000 (internal)
- Includes Supervisor for queue workers
- Non-root user: `appuser` (UID 1000)

### Nginx
- Image: `nginx:alpine`
- Port: 8080 (local), 80 (staging/production)
- Configuration: `docker/nginx/default.conf`

### MySQL
- Image: `mysql:8.0`
- Port: 3306
- Data: Persistent volume `db_data`

### Redis
- Image: `redis:7-alpine`
- Port: 6379
- Data: Persistent volume `redis_data`

## Common Commands

### Artisan Commands
```bash
# Run migrations
docker compose exec app php artisan migrate

# Run seeders
docker compose exec app php artisan db:seed

# Clear cache
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear

# Generate key
docker compose exec app php artisan key:generate
```

### Container Management
```bash
# Start services
docker compose up -d

# Stop services
docker compose stop

# View logs
docker compose logs -f app
docker compose logs -f nginx

# Execute commands
docker compose exec app bash
docker compose exec app php artisan tinker
```

### Queue Workers
Supervisor manages queue workers automatically. Check status:
```bash
docker compose exec app supervisorctl status
```

## Environment Variables

### Required for Local
- `APP_KEY` - Generated with `php artisan key:generate`
- `DB_*` - Database credentials
- `REDIS_HOST` - Set to `redis`

### Production Specific
- `APP_ENV=production`
- `APP_DEBUG=false`
- `CACHE_DRIVER=redis`
- `SESSION_DRIVER=redis`
- `QUEUE_CONNECTION=redis`

## Production Deployment

1. **Build and push image**
   ```bash
   docker build -t your-registry/laravel-relations:latest .
   docker push your-registry/laravel-relations:latest
   ```

2. **On server, create `.env.production`**
   ```bash
   cp .env.production.example .env.production
   # Edit with production values
   ```

3. **Deploy**
   ```bash
   export IMAGE_TAG=latest
   docker compose -f docker-compose.production.yml up -d
   ```

4. **Run migrations**
   ```bash
   docker compose -f docker-compose.production.yml exec app php artisan migrate --force
   ```

## Troubleshooting

### Permission Issues
```bash
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R appuser:appuser storage bootstrap/cache
```

### Database Connection Issues
- Ensure `DB_HOST=db` (Docker service name, not `localhost`)
- Check MySQL container is healthy: `docker compose ps`

### Queue Workers Not Running
```bash
# Check Supervisor status
docker compose exec app supervisorctl status

# Restart workers
docker compose exec app supervisorctl restart laravel-worker:*
```

### View Logs
```bash
# Application logs
docker compose exec app tail -f storage/logs/laravel.log

# Worker logs
docker compose exec app tail -f storage/logs/worker.log

# Nginx logs
docker compose logs nginx

# PHP-FPM logs
docker compose exec app tail -f /var/log/php-fpm.log
```

## Health Checks

The application includes a `/health` endpoint:
```bash
curl http://localhost:8080/health
```

## File Structure

```
docker/
├── entrypoint.sh          # Container entrypoint
├── nginx/
│   ├── default.conf       # Local development
│   ├── staging.conf       # Staging environment
│   ├── production.conf    # Production environment
│   └── logs/              # Nginx logs
├── php/
│   └── conf/
│       ├── www.conf       # PHP-FPM configuration
│       └── php.ini        # PHP settings
└── supervisor/
    ├── supervisord.conf   # Supervisor main config
    └── laravel-worker.conf # Queue worker config
```

## Security Notes

- Containers run as non-root user (`appuser`)
- Sensitive files are blocked by Nginx
- Use environment variables for secrets
- Never commit `.env` files with real credentials

## Next Steps

1. Set up Jenkins pipeline (see `Jenkinsfile`)
2. Configure Docker registry
3. Set up SSL certificates for production
4. Configure backups for database volumes
5. Set up monitoring and alerting

