# Quick Start Guide - Docker Directory

## Local Development Setup

1. **Copy environment file:**
   ```bash
   cp docker/env/.env.development .env
   ```
   
   Or edit `docker/env/.env.development` directly (docker-compose will use it)

2. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

3. **Start Docker services:**
   ```bash
   cd docker/compose
   docker compose -f docker-compose.yml up -d --build
   ```

   Or from project root:
   ```bash
   docker compose -f docker/compose/docker-compose.yml up -d --build
   ```

4. **Run migrations:**
   ```bash
   docker compose -f docker/compose/docker-compose.yml exec app php artisan migrate
   ```

5. **Create storage link:**
   ```bash
   docker compose -f docker/compose/docker-compose.yml exec app php artisan storage:link
   ```

6. **Access application:**
   - Web: http://localhost:8000 (Laravel app)
   - Jenkins: http://localhost:8080 (CI/CD)
   - MySQL: localhost:3306
   - Redis: localhost:6379

## Building and Pushing Docker Image

### Build Image
```bash
docker build -t devsaleem/laravel-realtions:latest -f docker/Dockerfile .
```

### Tag and Push
```bash
# Login to Docker Hub
docker login

# Tag image
docker tag devsaleem/laravel-realtions:latest devsaleem/laravel-realtions:v1.0.0

# Push to Docker Hub
docker push devsaleem/laravel-realtions:latest
docker push devsaleem/laravel-realtions:v1.0.0
```

## Staging Deployment

1. **Edit environment file:**
   ```bash
   # Edit docker/env/.env.staging with staging values
   ```

2. **Deploy:**
   ```bash
   export IMAGE_TAG=develop-123-abc123
   export DOCKER_REGISTRY=devsaleem
   cd docker/compose
   docker compose -f docker-compose.staging.yml up -d --no-build
   ```

## Production Deployment

1. **Edit environment file:**
   ```bash
   # Edit docker/env/.env.production with production values
   ```

2. **Deploy:**
   ```bash
   export IMAGE_TAG=master-456-def456
   export DOCKER_REGISTRY=devsaleem
   cd docker/compose
   docker compose -f docker-compose.production.yml up -d --no-build
   ```

## Common Commands

### Artisan Commands
```bash
# From project root
docker compose -f docker/compose/docker-compose.yml exec app php artisan migrate
docker compose -f docker/compose/docker-compose.yml exec app php artisan cache:clear
docker compose -f docker/compose/docker-compose.yml exec app php artisan tinker
```

### View Logs
```bash
docker compose -f docker/compose/docker-compose.yml logs -f app
docker compose -f docker/compose/docker-compose.yml logs -f nginx
```

### Stop Services
```bash
docker compose -f docker/compose/docker-compose.yml down
```

### Rebuild Services
```bash
docker compose -f docker/compose/docker-compose.yml up -d --build
```

## Jenkins Setup

### Start Jenkins Container

Jenkins is included in the main docker-compose file:

```bash
cd docker/compose
docker compose -f docker-compose.yml up -d jenkins
```

### Initial Setup

1. **Get initial admin password:**
   ```bash
   docker compose -f docker-compose.yml exec jenkins cat /var/jenkins_home/secrets/initialAdminPassword
   ```

2. **Access Jenkins UI:** http://localhost:8080

3. **Install recommended plugins** (Docker Pipeline, SSH Agent, Git)

4. **Configure credentials:**
   - `docker-registry-credentials` - Docker Hub username/password
   - `ssh-deploy-credentials` - SSH private key
   - `deploy-host` - Deployment server hostname
   - `deploy-user` - Deployment user

5. **Create pipeline job:**
   - Point to `docker/jenkins/Jenkinsfile`
   - Pipeline will automatically:
     - Build image
     - Run tests
     - Push to Docker Hub (`devsaleem/laravel-realtions`)
     - Deploy to staging/production

See `docker/jenkins/README.md` for detailed instructions.

## Troubleshooting

### Permission Issues
```bash
docker compose -f docker/compose/docker-compose.yml exec app chmod -R 775 storage bootstrap/cache
```

### Database Connection
- Ensure `DB_HOST=db` (Docker service name, not `localhost`)
- Check MySQL container: `docker compose -f docker/compose/docker-compose.yml ps`

### Queue Workers
```bash
docker compose -f docker/compose/docker-compose.yml exec app supervisorctl status
```

