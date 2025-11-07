# Environment Files 🔒

This directory contains environment files for different deployment environments.

## Files

- `.env.development` - Local development environment
- `.env.staging` - Staging environment
- `.env.production` - Production environment
- `.env.testing` - Testing environment (used by Jenkins for running tests)

## Creating Environment Files

If the environment files don't exist yet, you can create them using one of these methods:

### Option 1: Run the script (Linux/Mac)
```bash
chmod +x docker/env/create-env-files.sh
./docker/env/create-env-files.sh
```

### Option 2: Run the PowerShell script (Windows)
```powershell
.\docker\env\create-env-files.ps1
```

### Option 3: Create manually
Copy the content from the script files and create the files manually in `docker/env/` directory.

## Usage

### Local Development

1. Copy the development file to project root:
   ```bash
   cp docker/env/.env.development .env
   ```

2. Edit `.env` with your local configuration:
   - Set `APP_KEY` (generate with `php artisan key:generate`)
   - Configure database credentials
   - Set other required variables

   Or use directly:
   ```bash
   # Edit docker/env/.env.development
   # docker-compose.yml references: ../env/.env.development
   ```

### Staging

1. Edit `docker/env/.env.staging` with staging-specific values:
   - Set `APP_ENV=staging`
   - Set `APP_DEBUG=false`
   - Configure database credentials
   - Configure Redis for cache/sessions/queues
   - Set mail settings

2. The docker-compose file references this as `../env/.env.staging`

### Production

1. Edit `docker/env/.env.production` with production values:
   - Set `APP_ENV=production`
   - Set `APP_DEBUG=false`
   - Configure Redis for cache/sessions/queues
   - Set secure database passwords
   - Configure mail settings
   - Set `DOCKER_REGISTRY=devsaleem`

2. The docker-compose file references this as `../env/.env.production`

### Testing

The `.env.testing` file is used by Jenkins during the test stage. It should have:
- `APP_ENV=testing`
- Test database credentials
- `DB_HOST=db` (Docker service name)
- `REDIS_HOST=redis` (Docker service name)

## Important Notes

⚠️ **Security Warning:**
- **Never commit actual `.env` files** with real credentials to Git
- These files should be in `.gitignore`
- Use Jenkins credentials or Docker secrets for production secrets
- The `DOCKER_REGISTRY` in production/staging is set to `devsaleem`

## File Structure

```
docker/env/
├── .env.development    # Local development
├── .env.staging        # Staging environment
└── .env.production     # Production environment
```

