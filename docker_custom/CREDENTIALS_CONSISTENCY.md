# Credentials Consistency Checklist

This document ensures all credentials and environment variables are consistent across Docker Compose files, Jenkinsfile, and environment files.

## Docker Registry Configuration

### Jenkinsfile
```groovy
DOCKER_REGISTRY = 'devsaleem'
DOCKER_IMAGE = 'laravel-realtions'
```

### Docker Compose Files (Staging/Production)
```yaml
image: "${DOCKER_REGISTRY:-devsaleem}/laravel-realtions:${IMAGE_TAG:-latest}"
```

**✅ Consistent**: Both use `devsaleem` as the registry and `laravel-realtions` as the image name.

**Note**: The image name has a typo (`realtions` instead of `relations`). This is intentional and consistent across all files.

## Environment Variables Required

### For All Environments (`.env.development`, `.env.staging`, `.env.production`)

#### Core Application
- `APP_NAME` - Application name
- `APP_ENV` - Environment (`local`, `staging`, `production`)
- `APP_KEY` - Laravel encryption key (generate with `php artisan key:generate`)
- `APP_DEBUG` - Debug mode (`true` for local, `false` for staging/production)
- `APP_URL` - Application URL

#### Database (Must match docker-compose.yml MySQL service)
- `DB_CONNECTION=mysql`
- `DB_HOST=db` (Docker service name, not `localhost`)
- `DB_PORT=3306`
- `DB_DATABASE` - Must match `MYSQL_DATABASE` in compose file
- `DB_USERNAME` - Must match `MYSQL_USER` in compose file
- `DB_PASSWORD` - Must match `MYSQL_PASSWORD` in compose file
- `DB_ROOT_PASSWORD` - Used for MySQL root (only in compose file, not Laravel config)

#### Redis (Must match docker-compose.yml Redis service)
- `REDIS_HOST=redis` (Docker service name, not `localhost`)
- `REDIS_PASSWORD` - Must match Redis password in compose file (if set)
- `REDIS_PORT=6379`
- `REDIS_DB=0` (default database)
- `REDIS_CACHE_DB=1` (cache database)

#### Cache/Session/Queue (Production/Staging)
- `CACHE_DRIVER=redis` (should be `redis` for staging/production)
- `SESSION_DRIVER=redis` (should be `redis` for staging/production)
- `QUEUE_CONNECTION=redis` (should be `redis` for staging/production)

#### Mail Configuration
- `MAIL_MAILER=smtp`
- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_ENCRYPTION`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`

### For Testing (`.env.testing`)

Used by Jenkinsfile in the test stage:
- Same structure as development but with test-specific values
- `APP_ENV=testing`
- Test database credentials (can use separate test database)

### For Staging/Production Deployment (Environment Variables)

#### Required at Deployment Time
- `DOCKER_REGISTRY` - Defaults to `devsaleem` if not set
- `IMAGE_TAG` - Image tag (e.g., `develop-123-abc123`)

These are set by Jenkins during deployment:
```bash
export IMAGE_TAG=${env.IMAGE_TAG}
export DOCKER_REGISTRY=${DOCKER_REGISTRY}
```

## Docker Compose Service Credentials

### Development (`docker-compose.yml`)
```yaml
db:
  environment:
    MYSQL_DATABASE: ${DB_DATABASE:-laravel}
    MYSQL_USER: ${DB_USERNAME:-laravel}
    MYSQL_PASSWORD: ${DB_PASSWORD:-laravel}
    MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-rootpassword}
```

**Must match**: `.env.development` file values

### Staging (`docker-compose.staging.yml`)
```yaml
db:
  environment:
    MYSQL_DATABASE: ${DB_DATABASE}
    MYSQL_USER: ${DB_USERNAME}
    MYSQL_PASSWORD: ${DB_PASSWORD}
    MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}

redis:
  command: redis-server --requirepass ${REDIS_PASSWORD:-}
```

**Must match**: `.env.staging` file values

### Production (`docker-compose.production.yml`)
```yaml
db:
  environment:
    MYSQL_DATABASE: ${DB_DATABASE}
    MYSQL_USER: ${DB_USERNAME}
    MYSQL_PASSWORD: ${DB_PASSWORD}
    MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}

redis:
  command: redis-server --requirepass ${REDIS_PASSWORD:-}
```

**Must match**: `.env.production` file values

## Jenkins Credentials (Configured in Jenkins UI)

### Required Jenkins Credentials IDs

1. **`docker-registry-credentials`** (Username/Password)
   - Username: Docker Hub username (e.g., `devsaleem`)
   - Password: Docker Hub password or access token

2. **`ssh-deploy-credentials`** (SSH Username with private key)
   - Username: Deployment user (usually `deploy`)
   - Private Key: SSH private key for accessing deployment server

3. **`deploy-host`** (Secret text)
   - Value: Deployment server hostname or IP address

4. **`deploy-user`** (Secret text)
   - Value: Deployment user (usually `deploy`)

### Jenkinsfile Environment Variables
```groovy
DOCKER_REGISTRY = 'devsaleem'           // Hardcoded, matches compose default
DOCKER_IMAGE = 'laravel-realtions'      // Hardcoded, matches compose
DOCKER_CREDENTIALS_ID = 'docker-registry-credentials'
SSH_CREDENTIALS_ID = 'ssh-deploy-credentials'
DEPLOY_HOST = credentials('deploy-host') ?: 'your-deploy-host'
DEPLOY_USER = credentials('deploy-user') ?: 'deploy'
DEPLOY_PATH = '/opt/laravel-relations'  // Hardcoded deployment path
```

## Deployment Path Consistency

### Jenkinsfile
```groovy
DEPLOY_PATH = '/opt/laravel-relations'
```

**Action Required**: Ensure this path exists on your deployment servers (staging and production).

## Verification Checklist

Before deploying, verify:

- [ ] `.env.development` has `DB_HOST=db` (not `localhost`)
- [ ] `.env.development` has `REDIS_HOST=redis` (not `localhost`)
- [ ] `.env.staging` has all required variables matching compose file expectations
- [ ] `.env.production` has all required variables matching compose file expectations
- [ ] `.env.testing` exists and has test database credentials
- [ ] Jenkins credentials are configured with correct IDs:
  - [ ] `docker-registry-credentials`
  - [ ] `ssh-deploy-credentials`
  - [ ] `deploy-host`
  - [ ] `deploy-user`
- [ ] Docker Hub registry is `devsaleem`
- [ ] Docker image name is `laravel-realtions`
- [ ] Deployment path `/opt/laravel-relations` exists on servers
- [ ] Database credentials in env files match MySQL service in compose files
- [ ] Redis password in env files matches Redis service in compose files (if set)

## Common Issues

### Database Connection Failed
- **Cause**: `DB_HOST` is set to `localhost` instead of `db`
- **Fix**: Set `DB_HOST=db` in environment file

### Redis Connection Failed
- **Cause**: `REDIS_HOST` is set to `localhost` instead of `redis`
- **Fix**: Set `REDIS_HOST=redis` in environment file

### Redis Authentication Failed
- **Cause**: `REDIS_PASSWORD` in env file doesn't match Redis password in compose file
- **Fix**: Ensure `REDIS_PASSWORD` values match between `.env.*` and compose file

### Jenkins Deployment Failed
- **Cause**: Credentials not configured or wrong IDs
- **Fix**: Verify all 4 Jenkins credentials are configured with correct IDs

### Docker Image Push Failed
- **Cause**: Docker Hub credentials incorrect
- **Fix**: Verify `docker-registry-credentials` in Jenkins has correct username/password

