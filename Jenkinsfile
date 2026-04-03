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

        stage('Préparation de la DB') {
            steps {
                script {
                    echo "Nettoyage et lancement du conteneur MySQL"
                    sh 'docker rm -f mysql_test || true'
                    sh 'docker run -d --name mysql_test -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=testing -p 3306:3306 mysql:8.0'
                }
            }
        }

        stage('Installation PHP & Composer') {
            steps {
                script {
                    docker.image('php:8.2-bullseye').inside('--network host -u root') {
                        sh '''
                            echo "Installation des extensions PHP"
                            apt-get update -yqq || (sleep 5 && apt-get update -yqq)
                            apt-get install -yqq libzip-dev zip unzip git default-mysql-client
                            docker-php-ext-install pdo_mysql zip > /dev/null 2>&1
                            
                            echo "Installation de Composer"
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
                            
                            echo "Exécution des tests Laravel"
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
        success {
            echo "Build terminé avec succès 🎉"
        }
        failure {
            echo "Le build a échoué ❌, envoi d'email..."
            emailext(
                subject: "Build #$BUILD_NUMBER a échoué",
                body: """
                    Bonjour,

                    Le build numéro #$BUILD_NUMBER pour le projet $JOB_NAME a échoué.
                    Vérifie les logs ici : $BUILD_URL

                    Cordialement,
                    Jenkins
                """,
                to: 'manohydiary@gmail.com'  // <-- remplace par ton email
            )
        }
    }
}