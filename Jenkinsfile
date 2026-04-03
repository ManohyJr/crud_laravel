pipeline {
    agent any

    environment {
        DB_CONNECTION = 'mysql'
        DB_HOST = '127.0.0.1'
        DB_PORT = '3306'
        DB_DATABASE = 'testing'
        DB_USERNAME = 'root'
        DB_PASSWORD = 'root'
    }

    stages {
        stage('Build & Environment') {
            steps {
                script {
                    // On utilise l'image PHP pour installer les dépendances
                    docker.image('php:8.2-bullseye').inside('-u root') {
                        sh '''
                            apt-get update -yqq && apt-get install -yqq libzip-dev zip unzip git
                            docker-php-ext-install pdo_mysql zip > /dev/null 2>&1
                            curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer > /dev/null 2>&1
                            composer install --prefer-dist --no-ansi --no-interaction --no-progress
                        '''
                    }
                }
                stash includes: 'vendor/**', name: 'vendor-deps'
            }
        }

        stage('Test') {
            steps {
                script {
                    // 1. Lancer MySQL sur l'hôte (Debian)
                    sh 'docker rm -f mysql_test || true'
                    sh 'docker run -d --name mysql_test -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=testing -p 3306:3306 mysql:8.0'
                    
                    // 2. Exécuter les tests dans PHP
                    docker.image('php:8.2-bullseye').inside('-u root --network=host') {
                        unstash 'vendor-deps'
                        sh '''
                            apt-get update -yqq && apt-get install -yqq default-mysql-client
                            cp .env.example .env.testing
                            php artisan key:generate --env=testing
                            
                            echo "Attente de MySQL..."
                            until mysqladmin ping -h"127.0.0.1" -u"root" -p"root" --silent; do 
                                sleep 3
                            done
                            
                            php artisan migrate --env=testing --force
                            php artisan test --env=testing
                        '''
                    }
                }
            }
        }
    }
    
    post {
        always {
            // Nettoyage final du conteneur de base de données
            sh 'docker rm -f mysql_test || true'
        }
    }
}