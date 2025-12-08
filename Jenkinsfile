pipeline {
    agent any

    environment {
        CI = 'true'
        DOCKER_USER = 'alfredo1011'
        DOCKER_CREDENTIALS = credentials('dockerhub-credentials')
        PROJECT_NAME = 'assistpro-kardex-fc'
    }

    stages {
        stage('Load Instance Configurations') {
            steps {
                script {
                    // ========================================
                    // CONFIGURACIÓN DE INSTANCIAS
                    // Agrega instancias aquí - se desplegarán automáticamente
                    // ========================================
                    env.INSTANCE_CONFIGS = groovy.json.JsonOutput.toJson([
                        'cliente1': [
                            server: '212.56.46.7',
                            port: '7070',
                            dbHost: 'mysql1.example.com',
                            dbPort: '3306',
                            dbName: 'assistpro_cliente1',
                            dbUser: 'user_cliente1',
                            dbPass: 'pass_cliente1'
                        ],
                        'cliente2': [
                            server: '212.56.46.8',
                            port: '7070',
                            dbHost: 'mysql2.example.com',
                            dbPort: '3306',
                            dbName: 'assistpro_cliente2',
                            dbUser: 'user_cliente2',
                            dbPass: 'pass_cliente2'
                        ],
                        'cliente3': [
                            server: '212.56.46.7',
                            port: '7071',
                            dbHost: 'mysql3.example.com',
                            dbPort: '3306',
                            dbName: 'assistpro_cliente3',
                            dbUser: 'user_cliente3',
                            dbPass: 'pass_cliente3'
                        ]
                        // ⬇️ AGREGA MÁS INSTANCIAS AQUÍ
                        // Se desplegarán automáticamente en el próximo commit
                    ])
                    
                    def configs = new groovy.json.JsonSlurper().parseText(env.INSTANCE_CONFIGS)
                    echo "=== 📋 Instancias Configuradas ==="
                    configs.each { name, config ->
                        echo "  • ${name} → ${config.server}:${config.port}"
                    }
                    echo "Total: ${configs.size()} instancias"
                }
            }
        }

        stage('Debug Info') {
            steps {
                script {
                    echo "=== 🔍 DEBUG INFO ==="
                    echo "Branch: ${env.GIT_BRANCH}"
                    echo "Commit: ${env.GIT_COMMIT}"
                    sh 'pwd'
                    sh 'ls -la'
                }
            }
        }

        stage('Build Docker Image') {
            when {
                branch 'main'
            }
            steps {
                echo "🔨 Compilando Imagen Docker"
                
                script {
                    sh "touch .env"

                    echo "📦 Building Docker image: ${DOCKER_USER}/${PROJECT_NAME}:latest"
                    sh "docker build -f Dockerfile -t ${DOCKER_USER}/${PROJECT_NAME}:latest ."

                    echo "🔐 Login to DockerHub"
                    sh "echo \$DOCKER_CREDENTIALS_PSW | docker login -u \$DOCKER_CREDENTIALS_USR --password-stdin"

                    echo "📤 Pushing image to DockerHub"
                    sh "docker push ${DOCKER_USER}/${PROJECT_NAME}:latest"
                    
                    echo "✅ Imagen construida y publicada"
                }
            }
        }

        stage('Deploy All Instances') {
            when {
                branch 'main'
            }
            steps {
                script {
                    def configs = new groovy.json.JsonSlurper().parseText(env.INSTANCE_CONFIGS)
                    
                    echo "\n🚀 Iniciando deployment de ${configs.size()} instancias..."
                    
                    // Desplegar cada instancia
                    configs.each { instanceName, config ->
                        stage("Deploy ${instanceName}") {
                            echo "\n=== 🎯 Desplegando: ${instanceName} ==="
                            echo "Servidor: ${config.server}"
                            echo "Puerto: ${config.port}"
                            
                            sshagent(['server-dev']) {
                                def user = 'root'
                                def containerName = "assistpro-kardex-${instanceName}"
                                
                                try {
                                    // Login to DockerHub on target server
                                    sh """ssh -o StrictHostKeyChecking=no -l ${user} ${config.server} \
                                        'echo ${DOCKER_CREDENTIALS_PSW} | docker login -u ${DOCKER_CREDENTIALS_USR} --password-stdin'"""

                                    // Stop & Remove existing container
                                    sh """ssh -o StrictHostKeyChecking=no -l ${user} ${config.server} \
                                        'docker stop ${containerName} || true && docker rm ${containerName} || true'"""

                                    // Run new container
                                    sh """ssh -o StrictHostKeyChecking=no -l ${user} ${config.server} \
                                        'docker run -d --pull=always \
                                        --name ${containerName} \
                                        -p ${config.port}:7071 \
                                        -e DB_HOST=${config.dbHost} \
                                        -e DB_PORT=${config.dbPort} \
                                        -e DB_NAME=${config.dbName} \
                                        -e DB_USER=${config.dbUser} \
                                        -e DB_PASS=${config.dbPass} \
                                        -e DB_CHARSET=utf8mb4 \
                                        -e DB_TIMEZONE=-06:00 \
                                        -e INSTANCE_NAME=${instanceName} \
                                        --restart unless-stopped \
                                        ${DOCKER_USER}/${PROJECT_NAME}:latest'"""
                                    
                                    // Verificar
                                    sh "sleep 5"
                                    sh """ssh -o StrictHostKeyChecking=no -l ${user} ${config.server} \
                                        'docker ps --filter name=${containerName} --format "{{.Names}}: {{.Status}}"'"""
                                    
                                    echo "✅ ${instanceName} desplegado exitosamente"
                                    echo "🌐 URL: http://${config.server}:${config.port}/assistpro_kardex_fc/"
                                    
                                } catch (Exception e) {
                                    echo "❌ Error desplegando ${instanceName}: ${e.message}"
                                    // Continuar con las demás instancias
                                }
                            }
                        }
                    }
                }
            }
        }

        stage('Deployment Summary') {
            when {
                branch 'main'
            }
            steps {
                script {
                    def configs = new groovy.json.JsonSlurper().parseText(env.INSTANCE_CONFIGS)
                    
                    echo "\n=========================================="
                    echo "✅ DEPLOYMENT COMPLETADO"
                    echo "=========================================="
                    echo "📦 Imagen: ${DOCKER_USER}/${PROJECT_NAME}:latest"
                    echo "🎯 Instancias desplegadas: ${configs.size()}"
                    echo "\n📋 URLs de acceso:"
                    
                    configs.each { name, config ->
                        echo "  • ${name}: http://${config.server}:${config.port}/assistpro_kardex_fc/"
                    }
                    
                    echo "=========================================="
                }
            }
        }
    }

    post {
        always {
            echo "🧹 Limpiando..."
            sh 'docker image prune -f || true'
        }
        success {
            script {
                def configs = new groovy.json.JsonSlurper().parseText(env.INSTANCE_CONFIGS)
                echo "\n✅ Pipeline ejecutado exitosamente"
                echo "Total de instancias: ${configs.size()}"
            }
        }
        failure {
            echo "\n❌ Pipeline falló - revisa los logs arriba"
        }
    }
}
