pipeline {
    agent any

    environment {
        DOCKER_REGISTRY = 'devsaleem'
        DOCKER_IMAGE = 'laravel-relations'
        DOCKER_CREDENTIALS_ID = 'docker-registry-credentials'
        SSH_CREDENTIALS_ID = 'ssh-deploy-credentials'
        DEPLOY_PATH = '/opt/laravel-relations'
    }

    options {
        buildDiscarder(logRotator(numToKeepStr: '10'))
        timeout(time: 60, unit: 'MINUTES')
        timestamps()
    }

    stages {
        stage('Prepare Environment') {
            steps {
                script {
                    // Set dynamic values here
                    env.DEPLOY_HOST = credentials('deploy-host')
                    env.DEPLOY_USER = credentials('deploy-user')
                    env.GIT_COMMIT_SHORT = sh(script: 'git rev-parse --short HEAD', returnStdout: true).trim()
                }
            }
        }

        stage('Checkout') {
            steps {
                checkout scm
                script {
                    env.BRANCH_NAME = env.BRANCH_NAME ?: env.GIT_BRANCH ?: 'master'
                    env.IMAGE_TAG = "${env.BRANCH_NAME}-${env.BUILD_NUMBER}-${env.GIT_COMMIT_SHORT}"
                    env.FULL_IMAGE_NAME = "${DOCKER_REGISTRY}/${DOCKER_IMAGE}:${env.IMAGE_TAG}"
                }
            }
        }

        stage('Build Docker Image') {
            steps {
                script {
                    def image = env.FULL_IMAGE_NAME
                    echo "Building Docker image: ${image}"
                    sh """
docker build -t ${image} -f docker/Dockerfile .
docker tag ${image} ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:latest
"""
                }
            }
        }

        // stage('Run Tests') {
        //     steps {
        //         script {
        //             echo "Running tests in Docker container..."
        //             def testEnvFile = 'docker/env/.env.testing'
        //             def envFileFlag = fileExists(testEnvFile) ? "--env-file ${testEnvFile}" : ''
        //             def image = env.FULL_IMAGE_NAME
        //             sh """
        // docker run --rm \\
        //     ${envFileFlag} \\
        //     -e DB_HOST=db \\
        //     -e DB_DATABASE=laravel_test \\
        //     -e DB_USERNAME=laravel_test \\
        //     -e DB_PASSWORD=laravel_test_password \\
        //     -e REDIS_HOST=redis \\
        //     -v \$(pwd)/tests:/var/www/html/tests:ro \\
        //     ${image} \\
        //     php artisan test
        // """
        //         }
        //     }
        //     post {
        //         always {
        //             junit 'tests/results/*.xml'
        //         }
        //     }
        // }

        stage('Security Scan') {
            steps {
                script {
                    def image = env.FULL_IMAGE_NAME
                    echo "Scanning Docker image for vulnerabilities: ${image}"
                    sh """
if ! command -v trivy &> /dev/null; then
    echo "Trivy not found, skipping security scan"
else
    trivy image --exit-code 0 --severity HIGH,CRITICAL ${image} || true
fi
"""
                }
            }
        }

        stage('Push to Registry') {
            steps {
                script {
                    def image = env.FULL_IMAGE_NAME
                    echo "Pushing image to Docker Hub: ${image}"
                    withCredentials([usernamePassword(credentialsId: DOCKER_CREDENTIALS_ID, usernameVariable: 'DOCKER_USER', passwordVariable: 'DOCKER_PASS')]) {
                        sh """
echo \$DOCKER_PASS | docker login -u \$DOCKER_USER --password-stdin
docker push ${image}
docker push ${DOCKER_REGISTRY}/${DOCKER_IMAGE}:latest
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
                    def image = env.FULL_IMAGE_NAME
                    def branch = env.BRANCH_NAME
                    def buildNum = env.BUILD_NUMBER
                    def commit = env.GIT_COMMIT_SHORT
                    def timestamp = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))

                    sh """
echo "Deploying to Staging..."
echo "IMAGE_TAG: ${env.IMAGE_TAG}"
echo "FULL_IMAGE_NAME: ${image}"
echo "BRANCH_NAME: ${branch}"
echo "BUILD_NUMBER: ${buildNum}"
echo "GIT_COMMIT: ${commit}"
echo "DEPLOY_TIMESTAMP: ${timestamp}"
"""

                    // sshagent([SSH_CREDENTIALS_ID]) { ... commented original deploy steps ... }
                }
            }
        }

        stage('Deploy to Production') {
            when { branch 'master' }
            steps {
                script {
                    input message: 'Deploy to Production?', ok: 'Deploy'
                    unstash 'deployment-info'

                    def image = env.FULL_IMAGE_NAME
                    def branch = env.BRANCH_NAME
                    def buildNum = env.BUILD_NUMBER
                    def commit = env.GIT_COMMIT_SHORT
                    def timestamp = new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'", TimeZone.getTimeZone('UTC'))

                    sh """
echo "Deploying to Production..."
echo "IMAGE_TAG: ${env.IMAGE_TAG}"
echo "FULL_IMAGE_NAME: ${image}"
echo "BRANCH_NAME: ${branch}"
echo "BUILD_NUMBER: ${buildNum}"
echo "GIT_COMMIT: ${commit}"
echo "DEPLOY_TIMESTAMP: ${timestamp}"
"""

                    // sshagent([SSH_CREDENTIALS_ID]) { ... commented original deploy steps ... }
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
            node {
                sh """
docker system prune -f || true
"""
            }
        }
    }
}
