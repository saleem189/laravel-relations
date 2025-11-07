#!/bin/bash

# Jenkins setup script
# This script helps configure Jenkins after first startup

echo "=========================================="
echo "Jenkins Setup Instructions"
echo "=========================================="
echo ""
echo "1. Get initial admin password:"
echo "   docker compose -f docker/compose/docker-compose.yml exec jenkins cat /var/jenkins_home/secrets/initialAdminPassword"
echo ""
echo "2. Access Jenkins UI:"
echo "   http://localhost:8080"
echo ""
echo "3. Install recommended plugins"
echo ""
echo "4. Create admin user"
echo ""
echo "5. Configure credentials:"
echo "   - docker-registry-credentials (Docker Hub username/password)"
echo "   - ssh-deploy-credentials (SSH private key)"
echo "   - deploy-host (deployment server hostname)"
echo "   - deploy-user (deployment user, usually 'deploy')"
echo ""
echo "6. Create pipeline job pointing to: docker/jenkins/Jenkinsfile"
echo ""
echo "=========================================="

