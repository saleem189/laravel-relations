pipeline {
    agent any
    
    environment {
        DOCKER_REGISTRY = 'devsaleem'
        DOCKER_IMAGE = 'laravel-relations'
        DOCKER_CREDENTIALS_ID = 'docker-registry-credentials'
        SSH_CREDENTIALS_ID = 'ssh-deploy-credentials'
        DEPLOY_HOST = credentials('deploy-host')
        DEPLOY_USER = credentials('deploy-user')
        DEPLOY_PATH = '/opt/laravel-relations'
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
        
        stage('Build Docker Image') {
            steps {
                script {
                    if (env.BRANCH_NAME == 'develop') {
                        env.IMAGE_TAG = "staging-${env.BUILD_NUMBER}-${GIT_COMMIT_SHORT}"
                        env.FULL_IMAGE_NAME = "${DOCKER_REGISTRY}/${DOCKER_IMAGE}:${env.IMAGE_TAG}"
                        echo "Building Staging Docker image: ${env.FULL_IMAGE_NAME}"
                        sh """
docker build -t ${env.FULL_IMAGE_NAME} -f docker/Dockerfile.prod .
docker tag ${env.FULL_IMAGE_NAME} ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:staging-latest
"""
                    } else if (env.BRANCH_NAME == 'master') {
                        env.IMAGE_TAG = "prod-${env.BUILD_NUMBER}-${GIT_COMMIT_SHORT}"
                        env.FULL_IMAGE_NAME = "${DOCKER_REGISTRY}/${DOCKER_IMAGE}:${env.IMAGE_TAG}"
                        echo "Building Production Docker image: ${env.FULL_IMAGE_NAME}"
                        sh """
docker build -t ${env.FULL_IMAGE_NAME} -f docker/Dockerfile.prod .
docker tag ${env.FULL_IMAGE_NAME} ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:prod-latest
"""
                    } else {
                        // Optional: build for other branches
                        env.IMAGE_TAG = "${env.BRANCH_NAME}-${env.BUILD_NUMBER}-${GIT_COMMIT_SHORT}"
                        env.FULL_IMAGE_NAME = "${DOCKER_REGISTRY}/${DOCKER_IMAGE}:${env.IMAGE_TAG}"
                        echo "Building Docker image for branch ${env.BRANCH_NAME}: ${env.FULL_IMAGE_NAME}"
                        sh "docker build -t ${env.FULL_IMAGE_NAME} -f docker/Dockerfile ."
                    }
                }
            }
        }

        // stage('Run Tests') { ... }  // Keep commented as-is
        
        stage('Security Scan') {
            steps {
                script {
                    echo "Scanning Docker image for vulnerabilities: ${env.FULL_IMAGE_NAME}"
                    sh """
if ! command -v trivy &> /dev/null; then
    echo "Trivy not found, skipping security scan"
else
    trivy image --exit-code 0 --severity HIGH,CRITICAL ${env.FULL_IMAGE_NAME} || true
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
        
        stage('Deploy to Staging') {
            when { branch 'develop' }
            steps {
                script {
                    unstash 'deployment-info'
                    sh """
echo "Deploying to Staging..."
echo "IMAGE_TAG: ${env.IMAGE_TAG}"
echo "FULL_IMAGE_NAME: ${env.FULL_IMAGE_NAME}"
"""
                    // sshagent([SSH_CREDENTIALS_ID]) { ... }  // keep commented
                }
            }
        }
        
        stage('Deploy to Production') {
            when { branch 'master' }
            steps {
                script {
                    input message: 'Deploy to Production?', ok: 'Deploy'
                    unstash 'deployment-info'
                    sh """
echo "Deploying to Production..."
echo "IMAGE_TAG: ${env.IMAGE_TAG}"
echo "FULL_IMAGE_NAME: ${env.FULL_IMAGE_NAME}"
"""
                    // sshagent([SSH_CREDENTIALS_ID]) { ... }  // keep commented
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
                sh "docker system prune -f || true"
            }
        }
    }
}
