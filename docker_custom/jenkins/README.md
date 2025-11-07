# Jenkins Configuration

This directory contains Jenkins CI/CD pipeline configuration.

## Files

- `Jenkinsfile` - Jenkins pipeline definition for building and deploying Laravel application

## Jenkins Container Setup

Jenkins is included in the main `docker-compose.yml` for local development:

```bash
cd docker/compose
docker compose -f docker-compose.yml up -d jenkins
```

**Access Jenkins:**
- Web UI: http://localhost:8080
- Get initial admin password:
  ```bash
  docker compose -f docker-compose.yml exec jenkins cat /var/jenkins_home/secrets/initialAdminPassword
  ```

**Note:** If you need Jenkins without the Laravel stack, you can stop other services:
```bash
docker compose -f docker-compose.yml stop app nginx db redis
docker compose -f docker-compose.yml up -d jenkins
```

## Jenkins Configuration Steps

1. **Access Jenkins UI:**
   - Open http://localhost:8080
   - Unlock with initial admin password (see above)

2. **Install Recommended Plugins:**
   - Docker Pipeline
   - SSH Agent
   - Git

3. **Configure Credentials:**
   - Go to: Manage Jenkins → Credentials → Add Credentials
   
   **Required Credentials:**
   - `docker-registry-credentials` (Username/Password)
     - Username: Your Docker Hub username (devsaleem)
     - Password: Your Docker Hub password/token
   
   - `ssh-deploy-credentials` (SSH Username with private key)
     - Username: deploy
     - Private Key: Your SSH private key for deployment server
   
   - `deploy-host` (Secret text)
     - Secret: Your deployment server hostname/IP
   
   - `deploy-user` (Secret text)
     - Secret: deploy (or your deployment user)

4. **Create Pipeline Job:**
   - New Item → Pipeline
   - Name: laravel-relations-pipeline
   - Pipeline definition: Pipeline script from SCM
   - SCM: Git
   - Repository URL: Your repository URL
   - Script Path: `docker/jenkins/Jenkinsfile`

## Jenkinsfile Overview

The pipeline includes:

1. **Checkout** - Gets source code from Git
2. **Build Docker Image** - Builds Laravel application image
3. **Run Tests** - Executes PHPUnit tests
4. **Security Scan** - Scans image with Trivy (if available)
5. **Push to Registry** - Pushes to Docker Hub (`devsaleem/laravel-realtions`)
6. **Deploy to Staging** - Auto-deploys on `develop` branch
7. **Deploy to Production** - Deploys on `master` branch (with approval)

## Docker Socket Access

Jenkins container mounts Docker socket (`/var/run/docker.sock`) to allow building Docker images from within the container.

**Security Note:** This allows Jenkins to control Docker on the host. Only use this in trusted environments.

## Jenkins Data Persistence

Jenkins data is stored in `jenkins_home` volume:
- Configuration
- Plugins
- Job history
- Credentials (encrypted)

To backup:
```bash
docker compose -f docker-compose.yml exec jenkins tar -czf /tmp/jenkins-backup.tar.gz /var/jenkins_home
```

## Troubleshooting

### Jenkins Can't Access Docker

If Jenkins can't build Docker images:
1. Check Docker socket is mounted: `/var/run/docker.sock:/var/run/docker.sock`
2. Ensure Jenkins container runs as root or has Docker access
3. Check Docker is running on host

### Jenkins Can't Access Git Repository

1. Add SSH keys to Jenkins credentials
2. Configure Git credentials in Jenkins
3. Check network connectivity from Jenkins container

### Pipeline Fails to Deploy

1. Verify SSH credentials are correct
2. Check deployment server is accessible
3. Ensure Docker is installed on deployment server
4. Verify deployment path exists on server

