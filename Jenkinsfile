pipeline {
    agent any
    
    environment {
        CI = 'true'
        DOCKER_USER = 'alfredo1011'
        DOCKER_CREDENTIALS = credentials('dockerhub-credentials')
        IMAGE_NAME = 'assistpro-kardex-fc'
    }

    stages {
        stage('Debug Info') {
            steps {
                script {
                    echo "=== DEBUG INFO ==="
                    sh 'pwd && ls -la'
                    echo "================="
                }
            }
        }

        // ========== BUILD STAGES ==========
        stage('Build - Development') {
            when {
                anyOf {
                    branch 'dev'
                    expression { env.GIT_BRANCH == 'origin/dev' }
                }
            }
            steps {
                echo "🔨 Build DEV - ${IMAGE_NAME}"
                sh "docker build --target development -t ${DOCKER_USER}/${IMAGE_NAME}:DEV ."
                sh "echo \$DOCKER_CREDENTIALS_PSW | docker login -u \$DOCKER_CREDENTIALS_USR --password-stdin"
                sh "docker push ${DOCKER_USER}/${IMAGE_NAME}:DEV"
            }
        }

        stage('Build - QA') {
            when {
                anyOf {
                    branch 'QA'
                    expression { env.GIT_BRANCH == 'origin/QA' }
                }
            }
            steps {
                echo "🔨 Build QA - ${IMAGE_NAME}"
                sh "docker build --target production -t ${DOCKER_USER}/${IMAGE_NAME}:QA ."
                sh "echo \$DOCKER_CREDENTIALS_PSW | docker login -u \$DOCKER_CREDENTIALS_USR --password-stdin"
                sh "docker push ${DOCKER_USER}/${IMAGE_NAME}:QA"
            }
        }

        stage('Build - Production') {
            when {
                anyOf {
                    branch 'main'
                    expression { env.GIT_BRANCH == 'origin/main' }
                }
            }
            steps {
                echo "🔨 Build PROD - ${IMAGE_NAME}"
                sh "docker build --target production -t ${DOCKER_USER}/${IMAGE_NAME}:PROD ."
                sh "echo \$DOCKER_CREDENTIALS_PSW | docker login -u \$DOCKER_CREDENTIALS_USR --password-stdin"
                sh "docker push ${DOCKER_USER}/${IMAGE_NAME}:PROD"
            }
        }

        // ========== DEPLOY STAGES ==========
        stage('Deploy - Development') {
            when {
                anyOf {
                    branch 'dev'
                    expression { env.GIT_BRANCH == 'origin/dev' }
                }
            }
            steps {
                echo "🚀 Deploy DEV"
                sshagent(['server-dev']) {
                    script {
                        def serverIP = '212.56.46.7'
                        def user = 'root'
                        def containerName = 'kardex-fc-dev'
                        def storagePath = '/home/kardex-fc-storage-dev'
                        def port = '8893'
                        
                        // Variables de BD para DEV
                        def dbHost = '89.117.146.27'
                        def dbName = 'assistpro_etl_fc_dev'
                        def dbUser = 'root2'
                        def dbPass = 'AdvLogMysql21#'
                        
                        deployContainer(serverIP, user, containerName, storagePath, port, 'DEV', dbHost, dbName, dbUser, dbPass)
                    }
                }
            }
        }

        stage('Deploy - QA') {
            when {
                anyOf {
                    branch 'QA'
                    expression { env.GIT_BRANCH == 'origin/QA' }
                }
            }
            steps {
                echo "🚀 Deploy QA"
                sshagent(['server-dev']) {
                    script {
                        def serverIP = '212.56.46.7'
                        def user = 'root'
                        def containerName = 'kardex-fc-qa'
                        def storagePath = '/home/kardex-fc-storage-qa'
                        def port = '8894'
                        
                        // Variables de BD para QA
                        def dbHost = '89.117.146.27'
                        def dbName = 'assistpro_etl_fc_qa'
                        def dbUser = 'root2'
                        def dbPass = 'AdvLogMysql21#'
                        
                        deployContainer(serverIP, user, containerName, storagePath, port, 'QA', dbHost, dbName, dbUser, dbPass)
                    }
                }
            }
        }

        stage('Deploy - La Canada (Production)') {
            when {
                anyOf {
                    branch 'main'
                    expression { env.GIT_BRANCH == 'origin/main' }
                }
            }
            steps {
                echo "🚀 Deploy LA CANADA (PRODUCTION)"
                sshagent(['server-dev']) {
                    script {
                        def serverIP = '212.56.46.7'
                        def user = 'root'
                        def containerName = 'kardex-fc-lacanada'
                        def storagePath = '/home/kardex-fc-storage-lacanada'
                        def port = '8895'
                        
                        // Variables de BD para LA CANADA
                        def dbHost = '89.117.146.27'
                        def dbName = 'assistpro_etl_fc_canada'
                        def dbUser = 'root2'
                        def dbPass = 'AdvLogMysql21#'
                        
                        deployContainer(serverIP, user, containerName, storagePath, port, 'PROD', dbHost, dbName, dbUser, dbPass)
                    }
                }
            }
        }

        stage('Deploy - Foam(Production)') {
            when {
                anyOf {
                    branch 'main'
                    expression { env.GIT_BRANCH == 'origin/main' }
                }
            }
            steps {
                echo "🚀 Deploy FOAM (PRODUCTION)"
                sshagent(['server-prod']) {
                    script {
                        def serverIP = '147.93.138.200'
                        def user = 'root'
                        def containerName = 'kardex-fc-prod'
                        def storagePath = '/home/kardex-fc-storage-prod'
                        def port = '81'
                        
                        // Variables de BD para PRODUCTION
                        def dbHost = '147.93.138.200'
                        def dbPort = '3007'
                        def dbName = 'assistpro_etl_fc_prod'
                        def dbUser = 'advlsystem'
                        def dbPass = 'AdvLogMysql21#'
                        
                        deployContainerCustomPort(serverIP, user, containerName, storagePath, port, 'PROD', dbHost, dbPort, dbName, dbUser, dbPass)
                    }
                }
            }
        }
    }

    post {
        success {
            echo "✅ Pipeline ejecutado exitosamente - Branch: ${env.BRANCH_NAME}"
        }
        failure {
            echo "❌ Pipeline falló en la rama ${env.BRANCH_NAME}"
        }
        always {
            sh 'docker image prune -f || true'
        }
    }
}

// ========== HELPER FUNCTION ==========
def deployContainer(serverIP, user, containerName, storagePath, port, tag, dbHost, dbName, dbUser, dbPass) {
    sh """ssh -o StrictHostKeyChecking=no -l ${user} ${serverIP} \
        'echo ${DOCKER_CREDENTIALS_PSW} | docker login -u ${DOCKER_CREDENTIALS_USR} --password-stdin'"""
    
    sh "ssh -o StrictHostKeyChecking=no -l ${user} ${serverIP} 'docker stop ${containerName} || true'"
    sh "ssh -o StrictHostKeyChecking=no -l ${user} ${serverIP} 'docker rm ${containerName} || true'"
    
    sh """ssh -o StrictHostKeyChecking=no -l ${user} ${serverIP} \
        'mkdir -p ${storagePath}/logs ${storagePath}/uploads'"""
    
    sh """ssh -o StrictHostKeyChecking=no -l ${user} ${serverIP} \
        'chown -R 33:33 ${storagePath} && chmod -R 775 ${storagePath}'"""
    
    sh """ssh -o StrictHostKeyChecking=no -l ${user} ${serverIP} \
        'docker run -d --pull=always \
        --name ${containerName} \
        -p ${port}:80 \
        -e DB_HOST=${dbHost} \
        -e DB_PORT=3306 \
        -e DB_NAME=${dbName} \
        -e DB_USER=${dbUser} \
        -e DB_PASS="${dbPass}" \
        -e DB_CHARSET=utf8mb4 \
        -v ${storagePath}/logs:/var/www/html/assistpro_kardex_fc/storage/logs \
        -v ${storagePath}/uploads:/var/www/html/assistpro_kardex_fc/uploads \
        --restart unless-stopped \
        ${DOCKER_USER}/${IMAGE_NAME}:${tag}'"""
    
    sh "sleep 10"
    sh "ssh -o StrictHostKeyChecking=no -l ${user} ${serverIP} 'docker ps | grep ${containerName}'"
    
    echo "✅ Deploy ${tag} completado - http://${serverIP}:${port}"
    echo "📁 Storage: ${storagePath}"
    echo "🗄️  Database: ${dbHost}/${dbName}"
}

// ========== HELPER FUNCTION WITH CUSTOM DB PORT ==========
def deployContainerCustomPort(serverIP, user, containerName, storagePath, port, tag, dbHost, dbPort, dbName, dbUser, dbPass) {
    sh """ssh -o StrictHostKeyChecking=no -l ${user} ${serverIP} \
        'echo ${DOCKER_CREDENTIALS_PSW} | docker login -u ${DOCKER_CREDENTIALS_USR} --password-stdin'"""
    
    sh "ssh -o StrictHostKeyChecking=no -l ${user} ${serverIP} 'docker stop ${containerName} || true'"
    sh "ssh -o StrictHostKeyChecking=no -l ${user} ${serverIP} 'docker rm ${containerName} || true'"
    
    sh """ssh -o StrictHostKeyChecking=no -l ${user} ${serverIP} \
        'mkdir -p ${storagePath}/logs ${storagePath}/uploads'"""
    
    sh """ssh -o StrictHostKeyChecking=no -l ${user} ${serverIP} \
        'chown -R 33:33 ${storagePath} && chmod -R 775 ${storagePath}'"""
    
    sh """ssh -o StrictHostKeyChecking=no -l ${user} ${serverIP} \
        'docker run -d --pull=always \
        --name ${containerName} \
        -p ${port}:80 \
        -e DB_HOST=${dbHost} \
        -e DB_PORT=${dbPort} \
        -e DB_NAME=${dbName} \
        -e DB_USER=${dbUser} \
        -e DB_PASS="${dbPass}" \
        -e DB_CHARSET=utf8mb4 \
        -v ${storagePath}/logs:/var/www/html/assistpro_kardex_fc/storage/logs \
        -v ${storagePath}/uploads:/var/www/html/assistpro_kardex_fc/uploads \
        --restart unless-stopped \
        ${DOCKER_USER}/${IMAGE_NAME}:${tag}'"""
    
    sh "sleep 10"
    sh "ssh -o StrictHostKeyChecking=no -l ${user} ${serverIP} 'docker ps | grep ${containerName}'"
    
    echo "✅ Deploy ${tag} completado - http://${serverIP}:${port}"
    echo "📁 Storage: ${storagePath}"
    echo "🗄️  Database: ${dbHost}:${dbPort}/${dbName}"
}
