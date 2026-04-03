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
        stage('Nettoyer et lancer MySQL') {
            steps {
                script {
                    sh 'docker rm -f mysql_test || true'
                    sh '''
                        docker run -d --name mysql_test \
                        -e MYSQL_ROOT_PASSWORD=root \
                        -e MYSQL_DATABASE=testing \
                        -p 3306:3306 mysql:8.0
                    '''
                }
            }
        }

        stage('Installer PHP et dépendances') {
            steps {
                script {
                    docker.image('php:8.2-bullseye').inside('--network host -u root') {
                        sh '''
                            set -e
                            apt-get update -yqq || (sleep 5 && apt-get update -yqq)
                            apt-get install -yqq libzip-dev zip unzip git default-mysql-client
                            docker-php-ext-install pdo_mysql zip > /dev/null 2>&1
                            curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
                        '''
                    }
                }
            }
        }

        stage('Configuration Laravel') {
            steps {
                script {
                    docker.image('php:8.2-bullseye').inside('--network host -u root') {
                        sh '''
                            set -e
                            composer install --prefer-dist --no-interaction
                            cp .env.example .env.testing
                            php artisan key:generate --env=testing
                        '''
                    }
                }
            }
        }

        stage('Attente MySQL et Migrations') {
            steps {
                script {
                    docker.image('php:8.2-bullseye').inside('--network host -u root') {
                        sh '''
                            set -e
                            echo "Attente de la base de données..."
                            until mysqladmin ping -h"127.0.0.1" -u"root" -p"root" --silent; do
                                sleep 2
                            done
                            php artisan migrate --env=testing --force
                        '''
                    }
                }
            }
        }

        stage('Tests Laravel') {
            steps {
                script {
                    docker.image('php:8.2-bullseye').inside('--network host -u root') {
                        sh '''
                            set -e
                            php artisan test --env=testing
                        '''
                    }
                }
            }
        }
    }

    post {
        always {
            sh 'docker rm -f mysql_test || true'
        }
    }
}