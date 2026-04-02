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
            agent {
                docker {
                    image 'php:8.2-bullseye'
                    // Ajout de --network=host pour le DNS et --entrypoint pour la stabilité
                    args '-u root --network=host --entrypoint='''
                }
            }
            steps {
                sh '''
                    # On vérifie la connexion avant de lancer apt
                    ping -c 2 google.com || echo "Attention: Pas d'accès internet"
                    
                    apt-get update -yqq
                    apt-get install -yqq libzip-dev zip unzip git default-mysql-client
                    docker-php-ext-install pdo_mysql zip > /dev/null 2>&1
                    
                    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer > /dev/null 2>&1
                    composer install --prefer-dist --no-ansi --no-interaction --no-progress
                '''
                stash includes: 'vendor/**', name: 'vendor-deps'
            }
        }

        stage('Test') {
            agent {
                docker {
                    image 'php:8.2-bullseye'
                    args '-u root --network=host --entrypoint='''
                }
            }
            steps {
                unstash 'vendor-deps'
                
                // Lancement de MySQL directement sur l'hôte (puisqu'on est en --network=host)
                sh 'docker rm -f mysql_test || true'
                sh 'docker run -d --name mysql_test -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=testing -p 3306:3306 mysql:8.0'
                
                script {
                    try {
                        sh '''
                            cp .env.example .env.testing
                            php artisan key:generate --env=testing

                            echo "Attente de MySQL..."
                            until mysqladmin ping -h"127.0.0.1" -u"root" -p"root" --silent; do 
                                sleep 2
                            done
                            echo "MySQL est prêt !"

                            php artisan migrate --env=testing --force
                            php artisan test --env=testing
                        '''
                    } finally {
                        sh 'docker rm -f mysql_test || true'
                    }
                }
            }
        }
    }
}