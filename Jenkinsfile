pipeline {
    agent any

    environment {
        DB_CONNECTION = 'mysql'
        DB_HOST = '127.0.0.1'
        DB_PORT = '3306'
        DB_DATABASE = 'testing'
        DB_USERNAME = 'root'
        DB_PASSWORD = 'root'
        DOCKER_ARGS = '--network host -u root' // On centralise les arguments Docker
    }

    stages {
        stage('Install Dependencies') {
            steps {
                script {
                    docker.image('php:8.2-bullseye').inside("${DOCKER_ARGS}") {
                        sh '''
                            apt-get update -yqq && apt-get install -yqq libzip-dev zip unzip git
                            docker-php-ext-install pdo_mysql zip > /dev/null 2>&1
                            curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
                            composer install --prefer-dist --no-interaction
                        '''
                    }
                }
                // On sauvegarde les dossiers vendor et .env pour les stages suivants
                stash includes: '**', name: 'app-source'
            }
        }

        stage('Setup Database') {
            steps {
                // On lance le conteneur MySQL sur l'hôte Debian
                sh 'docker rm -f mysql_test || true'
                sh 'docker run -d --name mysql_test -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=testing -p 3306:3306 mysql:8.0'
                
                script {
                    docker.image('php:8.2-bullseye').inside("${DOCKER_ARGS}") {
                        unstash 'app-source'
                        sh '''
                            apt-get update -yqq && apt-get install -yqq default-mysql-client
                            cp .env.example .env.testing
                            php artisan key:generate --env=testing
                            
                            echo "Attente de la base de données..."
                            until mysqladmin ping -h"127.0.0.1" -u"root" -p"root" --silent; do 
                                sleep 2
                            done
                            
                            php artisan migrate --env=testing --force
                        '''
                    }
                }
                // On re-stash pour inclure les changements (key:generate, migrations)
                stash includes: '**', name: 'app-ready'
            }
        }

        stage('Run Tests') {
            steps {
                script {
                    docker.image('php:8.2-bullseye').inside("${DOCKER_ARGS}") {
                        unstash 'app-ready'
                        sh 'php artisan test --env=testing'
                    }
                }
            }
        }
    }

    post {
        always {
            // Nettoyage systématique du conteneur MySQL
            sh 'docker rm -f mysql_test || true'
        }
        success {
            echo "Félicitations Manohy ! Le pipeline Laravel est un succès."
        }
    }
}