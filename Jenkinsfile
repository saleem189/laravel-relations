pipeline {
    agent any

    environment {
        DOCKER_REGISTRY = 'devsaleem'
        DOCKER_IMAGE = 'laravel-relations'
        DOCKER_CREDENTIALS_ID = 'docker-registry-credentials'
        SSH_CREDENTIALS_ID = 'ssh-deploy-credentials'
        DEPLOY_HOST = '172.22.146.117'
        DEPLOY_USER = 'ubuntu'
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
                    env.BRANCH_NAME = env.BRANCH_NAME ?: env.GIT_BRANCH ?: 'master'
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

                    sh """
                        # Pull cache image if exists
                        docker pull ${env.CACHE_IMAGE} || true

                        # Build Docker image using cache
                        docker build \
                            --cache-from ${env.CACHE_IMAGE} \
                            -t ${env.FULL_IMAGE_NAME} \
                            -f docker/Dockerfile.prod .

                        # Tag latest for branch
                        if [ "${env.BRANCH_NAME}" == "develop" ]; then
                            docker tag ${env.FULL_IMAGE_NAME} ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:staging-latest
                        elif [ "${env.BRANCH_NAME}" == "master" ]; then
                            docker tag ${env.FULL_IMAGE_NAME} ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:prod-latest
                        else
                            docker tag ${env.FULL_IMAGE_NAME} ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:latest
                        fi
                    """
                }
            }
        }

        stage('Push to Registry') {
            steps {
                script {
                    echo "Pushing Docker image: ${env.FULL_IMAGE_NAME}"
                    withCredentials([usernamePassword(credentialsId: DOCKER_CREDENTIALS_ID, usernameVariable: 'DOCKER_USER', passwordVariable: 'DOCKER_PASS')]) {
                        sh """
                            echo \$DOCKER_PASS | docker login -u \$DOCKER_USER --password-stdin
                            docker push ${env.FULL_IMAGE_NAME}
                            if [ "${env.BRANCH_NAME}" == "develop" ]; then
                                docker push ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:staging-latest
                            elif [ "${env.BRANCH_NAME}" == "master" ]; then
                                docker push ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:prod-latest
                            else
                                docker push ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:latest
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

       stage('Prepare Remote Server') {
    when { anyOf { branch 'develop'; branch 'master' } }
    steps {
        sshagent([SSH_CREDENTIALS_ID]) {
            script {
                sh """
# 1️⃣ Ensure main deploy path exists
ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null ${DEPLOY_USER}@${DEPLOY_HOST} "mkdir -p ${DEPLOY_PATH}"

# 2️⃣ Ensure subdirectories exist (docker_custom, env)
ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null ${DEPLOY_USER}@${DEPLOY_HOST} "mkdir -p ${DEPLOY_PATH}/docker_custom/compose"
ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null ${DEPLOY_USER}@${DEPLOY_HOST} "mkdir -p ${DEPLOY_PATH}/env"

# 3️⃣ Copy docker_custom directory only if it doesn't exist
ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null ${DEPLOY_USER}@${DEPLOY_HOST} "[ -d ${DEPLOY_PATH}/docker_custom ]" || \
scp -r -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null docker_custom ${DEPLOY_USER}@${DEPLOY_HOST}:${DEPLOY_PATH}/

# 4️⃣ Copy branch-specific .env file if missing
if [ "${BRANCH_NAME}" = "develop" ]; then
    REMOTE_ENV_FILE="${DEPLOY_PATH}/env/.env.staging"
    LOCAL_ENV_FILE="env/.env.staging"
elif [ "${BRANCH_NAME}" = "master" ]; then
    REMOTE_ENV_FILE="${DEPLOY_PATH}/env/.env.production"
    LOCAL_ENV_FILE="env/.env.production"
fi

ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null ${DEPLOY_USER}@${DEPLOY_HOST} "[ -f \$REMOTE_ENV_FILE ]" || \
scp -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \$LOCAL_ENV_FILE ${DEPLOY_USER}@${DEPLOY_HOST}:\$REMOTE_ENV_FILE

echo "✅ Remote server prepared successfully"
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
                            sh """
                                ssh -o StrictHostKeyChecking=no ${DEPLOY_USER}@${DEPLOY_HOST} << EOF
                                set -e
                                cd ${STAGGING_DEPLOY_PATH}

                                # Login to Docker
                                echo \$DOCKER_PASS | docker login -u \$DOCKER_USER --password-stdin

                                # Pull latest image
                                docker pull ${env.FULL_IMAGE_NAME}

                                # Update environment
                                export IMAGE_TAG=${env.IMAGE_TAG}
                                export DOCKER_REGISTRY=${DOCKER_REGISTRY}

                                # Deploy with Docker Compose
                                docker compose -f docker_custom/compose/docker-compose.staging.yml up -d --no-build

                                # Run migrations
                                docker compose -f docker_custom/compose/docker-compose.staging.yml exec -T app php artisan migrate --force || true

                                # Clear caches
                                docker compose -f docker_custom/compose/docker-compose.staging.yml exec -T app php artisan config:cache
                                docker compose -f docker_custom/compose/docker-compose.staging.yml exec -T app php artisan route:cache
                                docker compose -f docker_custom/compose/docker-compose.staging.yml exec -T app php artisan view:cache

                                # Health check
                                sleep 10
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
