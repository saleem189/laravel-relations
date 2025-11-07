# Docker Configuration Directory

This directory contains all Docker-related configuration files organized by service and purpose.

## Directory Structure

```
docker/
├── Dockerfile                    # Main Dockerfile for building the application image
├── compose/                      # Docker Compose files
│   ├── docker-compose.yml        # Local development
│   ├── docker-compose.staging.yml # Staging environment
│   └── docker-compose.production.yml # Production environment
├── env/                          # 🔒 Environment files
│   ├── .env.development         # Local development
│   ├── .env.staging             # Staging environment
│   └── .env.production          # Production environment
├── jenkins/                      # Jenkins CI/CD pipeline
│   ├── Jenkinsfile               # Jenkins pipeline definition
│   └── README.md                 # Jenkins setup guide
├── nginx/                        # Nginx configurations
│   └── conf.d/
│       ├── default.conf          # Local development
│       ├── staging.conf          # Staging environment
│       └── production.conf       # Production environment
├── php/                          # PHP-FPM configurations
│   ├── www.conf                  # PHP-FPM pool configuration
│   ├── php.ini                   # PHP settings
│   └── entrypoint.sh             # Container entrypoint script
└── supervisor/                   # Supervisor configurations
    ├── supervisord.conf          # Supervisor main config
    └── laravel-worker.conf       # Queue worker configuration
```

## Usage

### Local Development

1. **Copy environment file:**
   ```bash
   cp docker/env/.env.development .env
   ```
   
   Or use directly (docker-compose references `../env/.env.development`):
   ```bash
   # Edit docker/env/.env.development directly
   ```

2. **Start services:**
   ```bash
   cd docker/compose
   docker compose -f docker-compose.yml up -d --build
   ```

3. **Run migrations:**
   ```bash
   docker compose -f docker-compose.yml exec app php artisan migrate
   ```

### Building Docker Image

From project root:
```bash
docker build -t devsaleem/laravel-realtions:latest -f docker/Dockerfile .
```

### Docker Hub Registry

All images are pushed to: `devsaleem/laravel-realtions`

**Note:** The repository name uses "realtions" (as specified in your Docker Hub account).

## File Paths Reference

When running docker-compose from `docker/compose/` directory:
- Build context: `../..` (project root)
- Dockerfile: `../Dockerfile` or `docker/Dockerfile` from root
- Nginx configs: `../nginx/conf.d/`
- Environment files: `../env/`

## Important Notes

1. **Build Context**: The Dockerfile is in `docker/` but build context should be project root
2. **Environment Files**: Copy templates from `docker/env/` to project root or `docker/env/` when deploying
3. **Nginx Configs**: All nginx configurations are in `docker/nginx/conf.d/`
4. **Compose Files**: Run from `docker/compose/` directory with relative paths

## Quick Commands

```bash
# Local development
cd docker/compose
docker compose -f docker-compose.yml up -d

# Start only Jenkins (with full stack)
docker compose -f docker-compose.yml up -d jenkins

# Staging deployment
export IMAGE_TAG=develop-123-abc123
docker compose -f docker-compose.staging.yml up -d --no-build

# Production deployment
export IMAGE_TAG=master-456-def456
docker compose -f docker-compose.production.yml up -d --no-build
```

## Jenkins Container

Jenkins is included in `docker-compose.yml` for local CI/CD development:

- **Web UI**: http://localhost:8080
- **Get initial password**: 
  ```bash
  docker compose -f docker-compose.yml exec jenkins cat /var/jenkins_home/secrets/initialAdminPassword
  ```
- **Docker Access**: Jenkins can build Docker images (Docker socket mounted)
- **Data Persistence**: Jenkins data stored in `jenkins_home` volume

See `docker/jenkins/README.md` for detailed setup instructions.

## Credentials Consistency

**Important**: Ensure all credentials are consistent across Docker Compose files, environment files, and Jenkins configuration.

See `docker/CREDENTIALS_CONSISTENCY.md` for a complete checklist of all credentials and environment variables that must match.

