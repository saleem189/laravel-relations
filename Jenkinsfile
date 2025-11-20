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
                    env.BRANCH_NAME = (env.BRANCH_NAME ?: env.GIT_BRANCH ?: 'master')
                                        .replaceAll(/^origin\//, '')
                                        .replaceAll(/^remotes\/origin\//, '')
                                        .replaceAll(/^remotes\//, '')
                    env.GIT_COMMIT_SHORT = sh(script: 'git rev-parse --short HEAD', returnStdout: true).trim()
                    echo "Branch: ${env.BRANCH_NAME}, Commit: ${env.GIT_COMMIT_SHORT}"
                }
            }
        }

        stage('Build & Tag Docker Image') {
            steps {
                script {
                    def tagPrefix = (env.BRANCH_NAME == 'develop') ? 'staging' :
                                    (env.BRANCH_NAME == 'master') ? 'prod' : env.BRANCH_NAME
                    env.IMAGE_TAG = "${tagPrefix}-${env.BUILD_NUMBER}-${env.GIT_COMMIT_SHORT}"
                    env.FULL_IMAGE_NAME = "${DOCKER_REGISTRY}/${DOCKER_IMAGE}:${env.IMAGE_TAG}"
                    env.LATEST_TAG = (tagPrefix == 'staging') ? 'staging-latest' :
                                     (tagPrefix == 'prod') ? 'prod-latest' : 'latest'
                    
                    echo "Building Docker image ${env.FULL_IMAGE_NAME} with cache ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:${env.LATEST_TAG}"
                    sh """
                        docker pull ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:${env.LATEST_TAG} || true
                        docker build --cache-from ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:${env.LATEST_TAG} -t ${env.FULL_IMAGE_NAME} -f docker/Dockerfile.prod .
                        docker tag ${env.FULL_IMAGE_NAME} ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:${env.LATEST_TAG}
                    """
                }
            }
        }

        stage('Push Docker Image') {
            steps {
                script {
                    withCredentials([usernamePassword(credentialsId: DOCKER_CREDENTIALS_ID, usernameVariable: 'DOCKER_USER', passwordVariable: 'DOCKER_PASS')]) {
                        sh """
                            echo \$DOCKER_PASS | docker login -u \$DOCKER_USER --password-stdin
                            docker push ${env.FULL_IMAGE_NAME}
                            docker push ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:${env.LATEST_TAG}
                        """
                    }
                }
            }
        }

        stage('Stash Deployment Info') {
            steps {
                script {
                    writeFile file: 'deployment-info.txt', text: """
                        IMAGE_TAG=${env.IMAGE_TAG}
                        FULL_IMAGE_NAME=${env.FULL_IMAGE_NAME}
                        BRANCH_NAME=${env.BRANCH_NAME}
                        BUILD_NUMBER=${env.BUILD_NUMBER}
                        GIT_COMMIT=${env.GIT_COMMIT_SHORT}
                        DEPLOY_TIMESTAMP=${new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))}
                    """
                    stash includes: 'deployment-info.txt', name: 'deployment-info'
                }
            }
        }

        stage('Deploy to Staging') {
            when { branch 'develop' }
            steps {
                script {
                    unstash 'deployment-info'
                    def envFile = '.env.staging'
                    def composeFile = 'docker-compose.staging.yml'
                    deployDocker(envFile, composeFile)
                }
            }
        }

        stage('Deploy to Production') {
            when { branch 'master' }
            steps {
                script {
                    input message: 'Deploy to Production?', ok: 'Deploy'
                    unstash 'deployment-info'
                    def envFile = '.env.production'
                    def composeFile = 'docker-compose.production.yml'
                    deployDocker(envFile, composeFile)
                }
            }
        }
    }

    post {
        success { echo "Pipeline succeeded! Image: ${env.FULL_IMAGE_NAME}" }
        failure { echo "Pipeline failed! Check logs for details." }
        always {
            script {
                sh "docker rmi -f ${env.FULL_IMAGE_NAME} || true"
            }
        }
    }
}

// Helper function to deploy docker on remote server
def deployDocker(String envFile, String composeFile) {
    def sshTarget = "${env.DEPLOY_USER}@${env.DEPLOY_HOST}"
    // Determine latest tag based on compose file
    def latestTag = composeFile.contains('staging') ? 'staging-latest' : 
                    composeFile.contains('production') ? 'prod-latest' : 'latest'

    sshagent([env.SSH_CREDENTIALS_ID]) {
        withCredentials([usernamePassword(credentialsId: env.DOCKER_CREDENTIALS_ID, usernameVariable: 'DOCKER_USER', passwordVariable: 'DOCKER_PASS')]) {
            sh """
                echo "Preparing remote directories..."
                ssh -o StrictHostKeyChecking=no ${sshTarget} '
                    mkdir -p ${DEPLOY_PATH}/docker_custom/{env,compose,nginx,php,supervisor,jenkins}
                    sudo chown -R ${DEPLOY_USER}:${DEPLOY_USER} ${DEPLOY_PATH}/docker_custom || true
                    rm -f ${DEPLOY_PATH}/docker_custom/env/.env.* || true
                '

                echo "Copying docker_custom to remote..."
                scp -o StrictHostKeyChecking=no -r docker_custom/* ${sshTarget}:${DEPLOY_PATH}/docker_custom/

                echo "Setting .env symlink..."
                ssh -o StrictHostKeyChecking=no ${sshTarget} '
                    cd ${DEPLOY_PATH}/docker_custom/compose
                    ln -sf ../env/${envFile} .env
                '

                echo "Logging into Docker and deploying containers..."
                ssh -o StrictHostKeyChecking=no ${sshTarget} "
                    cd ${DEPLOY_PATH}
                    echo \\\$DOCKER_PASS | docker login -u \\\$DOCKER_USER --password-stdin
                    docker pull ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:${latestTag}
                    IMAGE_TAG=${latestTag} docker compose -f docker_custom/compose/${composeFile} up -d --no-build --force-recreate
                    # docker compose -f docker_custom/compose/${composeFile} exec -T app php artisan migrate --force || true
                    docker compose -f docker_custom/compose/${composeFile} ps
                "
            """
        }
    }
}
