pipeline {
    agent any

    environment {
        DB_CONNECTION = 'mysql'
        DB_HOST = '127.0.0.1'
        DB_PORT = '3306'
        DB_DATABASE = 'testing'
        DB_USERNAME = 'root'
        DB_PASSWORD = 'root'
        EMAIL_RECEIVER = 'manohydiary@gmail.com'
    }

    stages {

        stage('Préparation DB') {
            steps {
                script {
                    echo "Nettoyage et lancement du conteneur MySQL"
                    sh 'docker rm -f mysql_test || true'
                    sh 'docker run -d --name mysql_test -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=testing -p 3306:3306 mysql:8.0'
                }
            }
        }

        stage('Installation PHP et Composer') {
            steps {
                script {
                    echo "Installation des extensions PHP et Composer"
                    docker.image('php:8.2-bullseye').inside('--network host -u root') {
                        sh '''
                            apt-get update -yqq || (sleep 5 && apt-get update -yqq)
                            apt-get install -yqq libzip-dev zip unzip git default-mysql-client
                            docker-php-ext-install pdo_mysql zip
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
                            echo "Installation des dépendances Laravel"
                            composer install --prefer-dist --no-interaction
                            cp .env.example .env.testing
                            php artisan key:generate --env=testing
                        '''
                    }
                }
            }
        }

        stage('Migration & Tests') {
            steps {
                script {
                    docker.image('php:8.2-bullseye').inside('--network host -u root') {
                        sh '''
                            echo "Attente de la base de données..."
                            until mysqladmin ping -h"127.0.0.1" -u"root" -p"root" --silent; do 
                                sleep 2
                            done

                            echo "Exécution des migrations"
                            php artisan migrate --env=testing --force

                            echo "Exécution des tests"
                            php artisan test --env=testing
                        '''
                    }
                }
            }
        }

    }

    post {
        always {
            echo "Nettoyage du conteneur MySQL"
            sh 'docker rm -f mysql_test || true'
        }

        failure {
            echo "Le build a échoué ❌, envoi d'email..."
            emailext(
                to: "${EMAIL_RECEIVER}",
                subject: "Échec du build Jenkins: ${env.JOB_NAME} #${env.BUILD_NUMBER}",
                body: "Le build Jenkins ${env.JOB_NAME} #${env.BUILD_NUMBER} a échoué. Consultez la console pour plus de détails."
            )
        }
    }
}