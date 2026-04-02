pipeline {
    agent any

    environment {
        // Variables pour Laravel et MySQL
        DB_CONNECTION = 'mysql'
        DB_HOST = '127.0.0.1' // On utilise localhost car on va mapper les ports
        DB_PORT = '3306'
        DB_DATABASE = 'testing'
        DB_USERNAME = 'root'
        DB_PASSWORD = 'root'
        MYSQL_ROOT_PASSWORD = 'root'
        MYSQL_DATABASE = 'testing'
    }

    stages {
        stage('Build & Environment') {
            agent {
                docker {
                    image 'php:8.2-bullseye'
                    args '-u root' // Nécessaire pour apt-get
                }
            }
            steps {
                script {
                    // Installation des extensions et de Composer
                    sh '''
                        apt-get update -yqq
                        apt-get install -yqq libzip-dev zip unzip git default-mysql-client
                        docker-php-ext-install pdo_mysql zip > /dev/null 2>&1
                        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer > /dev/null 2>&1
                        composer install --prefer-dist --no-ansi --no-interaction --no-progress
                    '''
                }
                // Conservation du dossier vendor pour le stage suivant
                stash includes: 'vendor/**', name: 'vendor-deps'
            }
        }

        stage('Test') {
            agent {
                docker {
                    image 'php:8.2-bullseye'
                    args '--network=host' // Utilise le réseau de l'hôte pour voir MySQL
                }
            }
            steps {
                unstash 'vendor-deps'
                
                // On lance MySQL en arrière-plan via Docker sur l'hôte
                sh 'docker run -d --name mysql_test -e MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD} -e MYSQL_DATABASE=${MYSQL_DATABASE} -p 3306:3306 mysql:8.0'
                
                script {
                    try {
                        sh '''
                            # Préparation Laravel
                            cp .env.example .env.testing
                            php artisan key:generate --env=testing

                            # Attente de MySQL
                            echo "Attente du démarrage de MySQL..."
                            until mysqladmin ping -h"127.0.0.1" -u"root" -p"root" --silent; do 
                                sleep 2
                            done
                            echo "MySQL est prêt !"

                            # Migrations et Tests
                            php artisan migrate --env=testing --force
                            php artisan test --env=testing
                        '''
                    } finally {
                        // Nettoyage impératif du conteneur MySQL après les tests
                        sh 'docker rm -f mysql_test || true'
                    }
                }
            }
        }
    }
}