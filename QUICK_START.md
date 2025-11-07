# Quick Start Guide

## Local Development Setup

1. **Copy environment file:**
   ```bash
   cp env/.env.example .env
   ```

2. **Generate application key:**
   ```bash
   php artisan key:generate
   ```
   Or if using Docker:
   ```bash
   cd compose
   docker compose -f docker-compose.yml run --rm app php artisan key:generate
   ```

3. **Start Docker services:**
   ```bash
   cd compose
   docker compose -f docker-compose.yml up -d --build
   ```

4. **Run migrations:**
   ```bash
   docker compose -f docker-compose.yml exec app php artisan migrate
   ```

5. **Create storage link:**
   ```bash
   docker compose -f docker-compose.yml exec app php artisan storage:link
   ```

6. **Access application:**
   - Web: http://localhost:8080
   - MySQL: localhost:3306
   - Redis: localhost:6379

## Docker Hub Push

To manually push to your Docker Hub repository:

```bash
# Build image
docker build -t devsaleem/laravel-realtions:latest .

# Login to Docker Hub
docker login

# Push image
docker push devsaleem/laravel-realtions:latest

# Tag with specific version
docker tag devsaleem/laravel-realtions:latest devsaleem/laravel-realtions:v1.0.0
docker push devsaleem/laravel-realtions:v1.0.0
```

## Jenkins Pipeline

The Jenkins pipeline is located in `jenkins/Jenkinsfile`. 

**Configure Jenkins:**
1. Create a new Pipeline job
2. Point to `jenkins/Jenkinsfile` in your repository
3. Set up credentials:
   - `docker-registry-credentials` - Docker Hub username/password
   - `ssh-deploy-credentials` - SSH key for deployment server
   - `deploy-host` - Deployment server hostname
   - `deploy-user` - Deployment user (usually `deploy`)

**Pipeline Stages:**
- Checkout code
- Build Docker image
- Run tests
- Security scan (Trivy)
- Push to Docker Hub (`devsaleem/laravel-realtions`)
- Deploy to staging (on `develop` branch)
- Deploy to production (on `master` branch, with approval)

## Directory Structure Reference

- `docker/php/` - PHP-FPM and entrypoint configurations
- `docker/supervisor/` - Queue worker configurations
- `nginx/conf.d/` - Nginx configurations
- `env/` - Environment file templates
- `compose/` - Docker Compose files
- `jenkins/` - Jenkins pipeline

For detailed information, see `STRUCTURE.md`.

