# Project Directory Structure

This document describes the modular directory structure for Docker and deployment configurations.

## Directory Structure

```
laravel-relations/
├── Dockerfile                          # Main Dockerfile (root)
├── .dockerignore                       # Docker build exclusions
│
├── docker/                             # Docker-related files (by service)
│   ├── php/
│   │   ├── www.conf                    # PHP-FPM configuration
│   │   ├── php.ini                     # PHP settings
│   │   └── entrypoint.sh               # Container entrypoint script
│   ├── supervisor/
│   │   ├── supervisord.conf            # Supervisor main config
│   │   └── laravel-worker.conf         # Queue worker configuration
│   └── node/                           # Node.js specific configs (if needed)
│
├── nginx/                              # Nginx configurations
│   ├── conf.d/
│   │   ├── default.conf                # Local development
│   │   ├── staging.conf                # Staging environment
│   │   └── production.conf             # Production environment
│   └── ssl/                            # SSL certificates (production)
│
├── env/                                # Environment file templates
│   ├── .env.example                    # Local development template
│   ├── .env.staging.example            # Staging template
│   └── .env.production.example         # Production template
│
├── compose/                            # Docker Compose files
│   ├── docker-compose.yml              # Local development
│   ├── docker-compose.staging.yml      # Staging environment
│   └── docker-compose.production.yml  # Production environment
│
└── jenkins/                            # Jenkins CI/CD
    └── Jenkinsfile                     # Jenkins pipeline definition
```

## Docker Hub Registry

All images are pushed to: `devsaleem/laravel-realtions`

**Note:** The repository name uses "realtions" (as specified in your Docker Hub account).

## Usage

### Local Development

1. Copy environment file:
   ```bash
   cp env/.env.example .env
   ```

2. Start services:
   ```bash
   cd compose
   docker compose -f docker-compose.yml up -d --build
   ```

### Staging Deployment

```bash
cd compose
export IMAGE_TAG=develop-123-abc123
export DOCKER_REGISTRY=devsaleem
docker compose -f docker-compose.staging.yml up -d --no-build
```

### Production Deployment

```bash
cd compose
export IMAGE_TAG=master-456-def456
export DOCKER_REGISTRY=devsaleem
docker compose -f docker-compose.production.yml up -d --no-build
```

## File Locations Reference

| Configuration | Location |
|--------------|----------|
| PHP-FPM Config | `docker/php/www.conf` |
| PHP Settings | `docker/php/php.ini` |
| Entrypoint Script | `docker/php/entrypoint.sh` |
| Supervisor Config | `docker/supervisor/supervisord.conf` |
| Queue Workers | `docker/supervisor/laravel-worker.conf` |
| Nginx Configs | `nginx/conf.d/` |
| Environment Templates | `env/` |
| Docker Compose | `compose/` |
| Jenkins Pipeline | `jenkins/Jenkinsfile` |

## Important Notes

1. **Environment Files**: Copy templates from `env/` to project root when deploying:
   - `cp env/.env.example .env` (local)
   - `cp env/.env.staging.example .env.staging` (staging)
   - `cp env/.env.production.example .env.production` (production)

2. **Docker Compose**: All compose files assume they're run from the `compose/` directory with `..` as context.

3. **Jenkins**: The Jenkinsfile is located in `jenkins/` directory and should be referenced accordingly in Jenkins job configuration.

4. **SSL Certificates**: Place SSL certificates in `nginx/ssl/` for production deployments.

