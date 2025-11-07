
# CONFIGURATION_ANALYSIS_UPDATED.md

This is an **updated, actionable** configuration analysis for containerizing your Laravel application and deploying it via Jenkins to Docker hosts (VirtualBox VMs or real VPS).  
This file is a revision of the original `CONFIGURATION_ANALYSIS.md` you provided — it standardizes versions, tightens security recommendations, and adds concrete Docker and CI/CD implementation notes.

> **Version:** 2025-11-05  
> **Notes:** Targets PHP 8.2+ and modern Docker best practices; recommends registry-based image workflow and secure secrets management.

---

## Summary of key changes from original analysis
- Bumped recommended PHP baseline to **PHP 8.2** for performance and security.
- Stronger recommendations for **secrets management** (Docker secrets / env files + Jenkins credentials + HashiCorp Vault as an option).
- Added **multi-stage Dockerfile** patterns for smaller images and faster builds.
- Recommended **non-root runtime user** for containers and file permission steps.
- Clarified **registry-based deploy workflow** (push image from Jenkins → pull on servers).
- Added **healthcheck examples**, **entrypoint script** pattern, and **supervisor vs. process manager** guidance.
- Added concrete **docker-compose** snippets and environment file handling.
- Added **rollback / tagging** recommendation for CI/CD.

---

## 1. Recommended platform versions
- PHP: **8.2** (use `php:8.2-fpm` base)
- Composer: latest stable (use official composer image in multi-stage)
- Node: `18` or `20` (for frontend build stage)
- MySQL: `8.0` or MariaDB `10.6`
- Docker: latest stable, Docker Compose v2 syntax (`docker compose`)

---

## 2. Multi-stage Dockerfile (recommended)
Use multi-stage to build frontend assets and PHP dependencies, then produce a minimal runtime image.

```dockerfile
# Stage 1: node builder (assets)
FROM node:18-alpine AS node_builder
WORKDIR /build
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run production

# Stage 2: composer / build
FROM composer:2 AS composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize

# Stage 3: final runtime
FROM php:8.2-fpm-alpine
# system deps
RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev bash shadow \
    && docker-php-ext-install pdo_mysql mbstring intl zip bcmath

# create non-root user
RUN useradd -u 1000 -m appuser

WORKDIR /var/www/html
COPY --from=composer /app /var/www/html
COPY --from=node_builder /build/public/js /var/www/html/public/js
COPY docker/php/conf/www.conf /usr/local/etc/php-fpm.d/www.conf

# permissions
RUN chown -R appuser:appuser /var/www/html/storage /var/www/html/bootstrap/cache

USER appuser
EXPOSE 9000
CMD ["php-fpm"]
```

**Notes:**
- Use `alpine` images for smaller size where appropriate.
- Add `healthcheck` in compose for the app service.
- Avoid running containers as root — use a dedicated non-root user.

---

## 3. Docker Compose (production / staging pattern)
Example `docker-compose.production.yml`:

```yaml
version: "3.8"
services:
  app:
    image: "your-registry/your-app:${IMAGE_TAG}"
    env_file: [".env.production"]
    restart: always
    depends_on: [db]
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/health || exit 1"]
      interval: 30s
      timeout: 10s
      retries: 3
  nginx:
    image: nginx:alpine
    ports: ["80:80"]
    volumes:
      - ./nginx/conf.d:/etc/nginx/conf.d:ro
  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: laravel_prod
      MYSQL_USER: laravel_user
      MYSQL_PASSWORD: "${DB_PASSWORD}"
      MYSQL_ROOT_PASSWORD: "${DB_ROOT_PASSWORD}"
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

**Deployment note:** Compose references `IMAGE_TAG` env var. Jenkins injects the image tag (e.g., `your-registry/your-app:master-123`).

---

## 4. Environment & Secrets
- **Never commit `.env.*`** files with secrets to Git.
- Use **Jenkins Credentials** to inject secrets during deploy:
  - SSH keys for server access (`sshUserPrivateKey`)
  - Docker registry username/password (`usernamePassword`)
  - Option: use HashiCorp Vault / AWS Secrets Manager for production.
- Prefer **Docker secrets** or `env_file` with proper filesystem permissions for the server-side `.env`.

---

## 5. Supervisor / Process Management
- Supervisor is fine and commonly used. Example supervisord config is in the original file.
- Alternatives:
  - Use **systemd** on host with Docker (less common).
  - Use **Laravel Octane** for concurrent workers (if applicable).
- If using Supervisor inside the container, start supervisord AND php-fpm via an entrypoint script.

Entrypoint pattern:
```bash
#!/bin/sh
set -e
# run migrations only when explicitly requested
if [ -f artisan ] && [ "$RUN_MIGRATIONS" = "true" ]; then
  php artisan migrate --force
fi

# start supervisord in background
/usr/bin/supervisord -c /etc/supervisor/supervisord.conf &
# start php-fpm
php-fpm
```

---

## 6. CI/CD / Registry Workflow (recommended)
1. Jenkins builder:
   - checkout
   - build Docker image
   - run tests inside container
   - tag image: `your-registry/your-app:${BRANCH}-${BUILD_NUMBER}`
   - login and push to registry
   - stash metadata (image tag)
2. Jenkins deployer (on deploy node):
   - ssh to target VM
   - pull image using tag from Jenkins
   - `docker compose -f docker-compose.<env>.yml up -d --no-build`
   - run post-deploy tasks (artisan migrate, config cache) securely via `ssh` with `ssh-agent` or credentials
3. Rollback strategy:
   - Keep last N image tags and have a Jenkins job to redeploy a previous tag.

---

## 7. Healthchecks & Monitoring
- Provide `/health` route in Laravel that returns `200` when app is healthy.
- Use `docker-compose` healthchecks for orchestrating startup.
- Add basic container metrics (Prometheus exporter) when needed.
- Consider log forwarding to a centralized system (ELK, Loki, etc.) in production.

---

## 8. Permissions & Storage
- Ensure correct ownership of storage and cache folders at build time or at container start:
  ```
  chown -R 1000:1000 storage bootstrap/cache
  chmod -R 775 storage bootstrap/cache
  ```
- Use named volumes for persistent DB storage.

---

## 9. Additional Security Hardening
- Use non-root users for containers.
- Use minimal base images (alpine variants) where compatible.
- Limit environment variable exposure: only inject what is needed.
- Run security scans against images (Trivy).
- Restrict access to Docker registry using tokens and least privilege.

---

## 10. Testing Locally (Docker Desktop on Windows)
- Use `docker compose up -d --build` from repo root.
- Map ports (Nginx: `8080`, app port) in `docker-compose.yml`.
- Use `docker exec -it <app-container> bash` to run artisan commands.
- To emulate staging/prod, maintain separate compose files and `.env.*` files.

---

## 11. Jenkinsfile Integration Notes
- Use `stash/unstash` to move deploy artifacts between builder and deployer stages.
- Use `sshagent` with Jenkins credential IDs to perform `scp`/`ssh` securely.
- Provide manual `input` step for production deploys if you want approvals.
- Use `git rev-parse --short HEAD` to tag images by commit for traceability.

---

## 12. Quick Checklist Before Production
- [ ] Generate and secure `APP_KEY` for production
- [ ] Migrate to Redis for sessions in multi-container setups
- [ ] Ensure Supervisor or queue workers are configured and tested
- [ ] Configure backups for DB volumes
- [ ] Configure SSL (Let's Encrypt) on Nginx (or use a reverse proxy)
- [ ] Configure monitoring & alerts

---

## 13. Where to start (practical next steps)
1. Add the multi-stage Dockerfile to your repo.
2. Add `docker-compose.*.yml` files and `.env.*` templates (do not store secrets).
3. Configure Jenkins builder + deployer nodes, registry credentials, and SSH keys.
4. Test full pipeline by building an image and deploying to a local VirtualBox VM or Docker Desktop VM.
5. Iterate: enable healthchecks, logging, backups, and monitoring.

---

### End of `CONFIGURATION_ANALYSIS_UPDATED.md`
