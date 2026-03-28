pipeline {
    agent any

    options {
        timeout(time: 15, unit: 'MINUTES')
        timestamps() // Cette option est généralement pré-installée
    }

    environment {
        // Identifiants basés sur le numéro de build pour éviter les conflits
        DB_ID           = "db-${env.BUILD_NUMBER}"
        NET_ID          = "net-${env.BUILD_NUMBER}"
        
        // Configuration
        DB_PASSWORD     = credentials('jenkins-mysql-root-password')
        DB_DATABASE     = 'testing'
        DB_USERNAME     = 'root'
    }

    stages {
        stage('🚀 Setup Infra') {
            steps {
                echo "Creation du reseau : ${env.NET_ID}"
                sh "docker network create ${env.NET_ID} || true"
                
                echo "Lancement de MySQL..."
                sh """
                    docker run -d --name ${env.DB_ID} \
                        --network ${env.NET_ID} \
                        -e MYSQL_ROOT_PASSWORD=${env.DB_PASSWORD} \
                        -e MYSQL_DATABASE=${env.DB_DATABASE} \
                        mysql:8.0
                """
            }
        }

        stage('🧪 Build & Test') {
            steps {
                script {
                    // Utilisation de l'image Docker officielle PHP
                    docker.image('php:8.2-bullseye').inside("--network=${env.NET_ID}") {
                        echo "--- Preparation PHP & Composer ---"
                        sh '''
                            apt-get update -qq && apt-get install -y -qq libzip-dev mariadb-client unzip
                            docker-php-ext-install pdo_mysql zip > /dev/null
                            curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
                        '''

                        echo "--- Installation des dependances ---"
                        sh "composer install --no-interaction --prefer-dist"

                        echo "--- Configuration Laravel ---"
                        sh """
                            cp .env.example .env
                            sed -i 's/DB_HOST=127.0.0.1/DB_HOST=${env.DB_ID}/' .env
                            sed -i 's/DB_PASSWORD=/DB_PASSWORD=${env.DB_PASSWORD}/' .env
                            sed -i 's/DB_DATABASE=laravel/DB_DATABASE=${env.DB_DATABASE}/' .env
                            php artisan key:generate
                        """

                        echo "--- Attente MySQL ---"
                        sh """
                            until mysqladmin ping -h${env.DB_ID} -u${env.DB_USERNAME} -p${env.DB_PASSWORD} --silent; do
                                sleep 3
                            done
                        """

                        echo "--- Execution des Tests ---"
                        sh "php artisan migrate --force"
                        sh "php artisan test --without-tty"
                    }
                }
            }
        }
    }

    post {
        always {
            echo "🧹 Nettoyage..."
            sh "docker rm -f ${env.DB_ID} || true"
            sh "docker network rm ${env.NET_ID} || true"
        }
        
        success {
            echo "✅ Succès !"
        }

        failure {
            echo "❌ Échec !"
        }
    }
}