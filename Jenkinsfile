pipeline {
    agent any

    environment {
        DOCKER_REGISTRY = 'devsaleem'
        DOCKER_IMAGE = 'laravel-relations'
        DOCKER_CREDENTIALS_ID = 'docker-registry-credentials'
        SSH_CREDENTIALS_ID = 'ssh-deploy-credentials'
        DEPLOY_HOST = '192.168.244.128'
        DEPLOY_USER = 'saleem'
        DEPLOY_PATH = '/opt/laravel-relations'
        STAGGING_DEPLOY_PATH = '/opt/laravel-relations'
        PRODUCTION_DEPLOY_PATH = '/opt/laravel-relations'
        GIT_COMMIT_SHORT = sh(script: 'git rev-parse --short HEAD', returnStdout: true).trim()
    }

    options {
        buildDiscarder(logRotator(numToKeepStr: '10'))
        timeout(time: 60, unit: 'MINUTES')
        timestamps()
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
                script {
                    // Get branch name and clean it (remove origin/, remotes/origin/, etc.)
                    def rawBranch = env.BRANCH_NAME ?: env.GIT_BRANCH ?: 'master'
                    env.BRANCH_NAME = rawBranch.replaceAll(/^origin\//, '').replaceAll(/^remotes\/origin\//, '').replaceAll(/^remotes\//, '')
                    echo "Detected branch: ${env.BRANCH_NAME} (from: ${rawBranch})"
                }
            }
        }

        stage('Build Docker Image with Cache') {
            steps {
                script {
                    if (env.BRANCH_NAME == 'develop') {
                        env.IMAGE_TAG = "staging-${env.BUILD_NUMBER}-${GIT_COMMIT_SHORT}"
                        env.FULL_IMAGE_NAME = "${DOCKER_REGISTRY}/${DOCKER_IMAGE}:${env.IMAGE_TAG}"
                        env.CACHE_IMAGE = "${DOCKER_REGISTRY}/${DOCKER_IMAGE}:staging-latest"
                    } else if (env.BRANCH_NAME == 'master') {
                        env.IMAGE_TAG = "prod-${env.BUILD_NUMBER}-${GIT_COMMIT_SHORT}"
                        env.FULL_IMAGE_NAME = "${DOCKER_REGISTRY}/${DOCKER_IMAGE}:${env.IMAGE_TAG}"
                        env.CACHE_IMAGE = "${DOCKER_REGISTRY}/${DOCKER_IMAGE}:prod-latest"
                    } else {
                        env.IMAGE_TAG = "${env.BRANCH_NAME}-${env.BUILD_NUMBER}-${GIT_COMMIT_SHORT}"
                        env.FULL_IMAGE_NAME = "${DOCKER_REGISTRY}/${DOCKER_IMAGE}:${env.IMAGE_TAG}"
                        env.CACHE_IMAGE = "${DOCKER_REGISTRY}/${DOCKER_IMAGE}:latest"
                    }

                    echo "Building Docker image: ${env.FULL_IMAGE_NAME} with cache from ${env.CACHE_IMAGE}"
                    echo "Branch name for tagging: ${env.BRANCH_NAME}"

                    sh """
                        # Pull cache image if exists
                        docker pull ${env.CACHE_IMAGE} || true

                        # Build Docker image using cache
                        docker build \
                            --cache-from ${env.CACHE_IMAGE} \
                            -t ${env.FULL_IMAGE_NAME} \
                            -f docker/Dockerfile.prod .

                        # Tag latest for branch
                        if [ "${env.BRANCH_NAME}" = "develop" ]; then
                            echo "Tagging as staging-latest..."
                            docker tag ${env.FULL_IMAGE_NAME} ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:staging-latest
                            echo "✅ Tagged: ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:staging-latest"
                        elif [ "${env.BRANCH_NAME}" = "master" ]; then
                            echo "Tagging as prod-latest..."
                            docker tag ${env.FULL_IMAGE_NAME} ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:prod-latest
                            echo "✅ Tagged: ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:prod-latest"
                        else
                            echo "Tagging as latest for branch: ${env.BRANCH_NAME}"
                            docker tag ${env.FULL_IMAGE_NAME} ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:latest
                            echo "✅ Tagged: ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:latest"
                        fi
                    """
                }
            }
        }

        stage('Push to Registry') {
            steps {
                script {
                    echo "Pushing Docker image: ${env.FULL_IMAGE_NAME}"
                    echo "Branch name: ${env.BRANCH_NAME}"
                    withCredentials([usernamePassword(credentialsId: DOCKER_CREDENTIALS_ID, usernameVariable: 'DOCKER_USER', passwordVariable: 'DOCKER_PASS')]) {
                        sh """
                            echo \$DOCKER_PASS | docker login -u \$DOCKER_USER --password-stdin
                            docker push ${env.FULL_IMAGE_NAME}
                            
                            # Push latest tag based on branch
                            if [ "${env.BRANCH_NAME}" = "develop" ]; then
                                echo "Pushing staging-latest tag..."
                                docker push ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:staging-latest
                                echo "✅ Successfully pushed ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:staging-latest"
                            elif [ "${env.BRANCH_NAME}" = "master" ]; then
                                echo "Pushing prod-latest tag..."
                                docker push ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:prod-latest
                                echo "✅ Successfully pushed ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:prod-latest"
                            else
                                echo "Pushing latest tag for branch: ${env.BRANCH_NAME}"
                                docker push ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:latest
                                echo "✅ Successfully pushed ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:latest"
                            fi
                        """
                    }
                }
            }
        }

        stage('Stash Deployment Info') {
            steps {
                script {
                    def commit = env.GIT_COMMIT_SHORT
                    def timestamp = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))
                    writeFile file: 'deployment-info.txt', text: """
                                        IMAGE_TAG=${env.IMAGE_TAG}
                                        FULL_IMAGE_NAME=${env.FULL_IMAGE_NAME}
                                        BRANCH_NAME=${env.BRANCH_NAME}
                                        BUILD_NUMBER=${env.BUILD_NUMBER}
                                        GIT_COMMIT=${commit}
                                        DEPLOY_TIMESTAMP=${timestamp}
                                    """
                    stash includes: 'deployment-info.txt', name: 'deployment-info'
                }
            }
        }


        stage('Test Server Connectivity') {
            steps {
                script {
                    sh """
                        echo "Testing connectivity to ${DEPLOY_HOST}..."
                        echo "Jenkins container network info:"
                        ip addr show || true
                        ip route show || true
                        echo ""
                        echo "Testing ping to ${DEPLOY_HOST}..."
                        ping -c 2 ${DEPLOY_HOST} || echo "⚠️  Ping failed"
                        echo ""
                        echo "Testing SSH port 22..."
                        timeout 5 bash -c "</dev/tcp/${DEPLOY_HOST}/22" && echo "✅ Port 22 is open" || echo "❌ Port 22 is closed/unreachable"
                    """
                }
            }
        }

        stage('Prepare & Copy docker_custom to Remote') {
            steps {
                sshagent([SSH_CREDENTIALS_ID]) {
                    script {
                        def targetEnvName = '.env.staging'
                        if (env.BRANCH_NAME == 'master') {
                            targetEnvName = '.env.production'
                        }

                        def sshTarget = "${DEPLOY_USER}@${DEPLOY_HOST}"

                        sh """
                            echo "Ensuring remote folders exist and are writable..."
                            ssh -o StrictHostKeyChecking=no ${sshTarget} "
                                mkdir -p ${STAGGING_DEPLOY_PATH}/docker_custom
                                chown -R ${DEPLOY_USER}:${DEPLOY_USER} ${STAGGING_DEPLOY_PATH}/docker_custom
                            "

                            echo "Copying docker_custom contents to remote..."
                            scp -o StrictHostKeyChecking=no -r docker_custom/* ${sshTarget}:${STAGGING_DEPLOY_PATH}/docker_custom/

                            echo "Creating .env symlink on remote..."
                            ssh -o StrictHostKeyChecking=no ${sshTarget} << 'REMOTE_SCRIPT'
                                cd /opt/laravel-relations/docker_custom/compose

                                # Determine env file
                                if [ -f ../env/.env.staging ]; then
                                    ENV_FILE="../env/.env.staging"
                                elif [ -f ../env/.env.production ]; then
                                    ENV_FILE="../env/.env.production"
                                else
                                    echo "⚠️  No .env file found!"
                                    exit 1
                                fi

                                # Remove existing .env if it's not a symlink
                                if [ -f .env ] && [ ! -L .env ]; then
                                    rm -f .env
                                fi

                                # Create or update symlink
                                if [ ! -L .env ] || [ "\$(readlink .env)" != "\$ENV_FILE" ]; then
                                    ln -sf "\$ENV_FILE" .env
                                    echo "✅ Symlink created: .env -> \$ENV_FILE"
                                else
                                    echo "✅ Symlink already exists"
                                fi
                            REMOTE_SCRIPT
                        """
                    }
                }
            }
        }





        stage('Deploy to Staging') {
            when { branch 'develop' }
            steps {
                script {
                    unstash 'deployment-info'
                    echo "Deploying to Staging: ${env.FULL_IMAGE_NAME}"

                    sshagent([SSH_CREDENTIALS_ID]) {
                        withCredentials([usernamePassword(credentialsId: DOCKER_CREDENTIALS_ID, usernameVariable: 'DOCKER_USER', passwordVariable: 'DOCKER_PASS')]) {
                            def sshTarget = "${DEPLOY_USER}@${DEPLOY_HOST}"
                            sh """
                                ssh -o StrictHostKeyChecking=no ${sshTarget} << EOF
                                set -e
                                cd ${STAGGING_DEPLOY_PATH}

                                echo "Logging in to Docker..."
                                echo \$DOCKER_PASS | docker login -u \$DOCKER_USER --password-stdin

                                echo "Pulling image: ${env.FULL_IMAGE_NAME}..."
                                docker pull ${env.FULL_IMAGE_NAME}

                                echo "Deploying containers..."
                                docker compose -f docker_custom/compose/docker-compose.staging.yml up -d --no-build

                                echo "Running migrations..."
                                docker compose -f docker_custom/compose/docker-compose.staging.yml exec -T app php artisan migrate --force || true

                                echo "Ensuring supervisor log directory exists..."
                                mkdir -p /var/log/supervisor

                                echo "Health check - list containers:"
                                docker compose -f docker_custom/compose/docker-compose.staging.yml ps
                                EOF
                            """
                        }
                    }
                }
            }
        }

        stage('Deploy to Production') {
            when { branch 'master' }
            steps {
                script {
                    input message: 'Deploy to Production?', ok: 'Deploy'
                    unstash 'deployment-info'
                    echo "Deploying to Production: ${env.FULL_IMAGE_NAME}"
                    sshagent([SSH_CREDENTIALS_ID]) {
                        withCredentials([usernamePassword(credentialsId: DOCKER_CREDENTIALS_ID, usernameVariable: 'DOCKER_USER', passwordVariable: 'DOCKER_PASS')]) {
                            // sh """
                            //     ssh -o StrictHostKeyChecking=no ${DEPLOY_USER}@${DEPLOY_HOST} << EOF
                            //     set -e
                            //     cd ${PRODUCTION_DEPLOY_PATH}

                            //     # Login to Docker
                            //     echo \$DOCKER_PASS | docker login -u \$DOCKER_USER --password-stdin

                            //     # Pull latest image
                            //     docker pull ${env.FULL_IMAGE_NAME}

                            //     # Update environment
                            //     export IMAGE_TAG=${env.IMAGE_TAG}
                            //     export DOCKER_REGISTRY=${DOCKER_REGISTRY}

                            //     # Deploy with Docker Compose
                            //     docker compose -f docker_custom/compose/docker-compose.production.yml up -d --no-build

                            //     # Run migrations
                            //     docker compose -f docker_custom/compose/docker-compose.production.yml exec -T app php artisan migrate --force || true

                            //     # Clear caches
                            //     docker compose -f docker_custom/compose/docker-compose.production.yml exec -T app php artisan config:cache
                            //     docker compose -f docker_custom/compose/docker-compose.production.yml exec -T app php artisan route:cache
                            //     docker compose -f docker_custom/compose/docker-compose.production.yml exec -T app php artisan view:cache

                            //     # Health check
                            //     sleep 10
                            //     docker compose -f docker_custom/compose/docker-compose.production.yml ps
                            //     EOF
                            // """
                            sh """
                               echo "Deploying to Production: ${env.FULL_IMAGE_NAME}"
                            """
                        }
                    }
                }
            }
        }
    }

    post {
        success {
            echo "Pipeline succeeded! Image: ${env.FULL_IMAGE_NAME}"
        }
        failure {
            echo "Pipeline failed! Check logs for details."
        }
        always {
            script {
                // Optional: remove only this build's images
                sh """
docker rmi -f ${env.FULL_IMAGE_NAME} || true
"""
            }
        }
    }
}
