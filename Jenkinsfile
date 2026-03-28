pipeline {
    // Utilisation d’un agent Jenkins disponible
    agent any

    // Variables d'environnement globales
    environment {
        APP_ENV         = 'testing'                        // Environnement Laravel pour les tests
        DB_CONNECTION   = 'mysql'                          // Type de base de données
        DB_HOST         = 'mysql-ci'                       // Nom du conteneur MySQL
        DB_PORT         = '3306'                            // Port MySQL
        DB_DATABASE     = 'testing'                        // Nom de la base de données de test
        DB_USERNAME     = 'root'                           // Nom d’utilisateur MySQL
        DB_PASSWORD     = credentials('jenkins-mysql-root-password') // Mot de passe MySQL depuis Jenkins credentials
        DOCKER_NETWORK  = 'ci-network'                     // Réseau Docker pour connecter PHP et MySQL
        MYSQL_CONTAINER = 'mysql-ci'                       // Nom du conteneur MySQL
    }

    stages {

        stage('Prepare Environment') {
            steps {
                echo "Création du réseau Docker pour isoler les conteneurs..."
                sh 'docker network create $DOCKER_NETWORK || true'
            }
        }

        stage('Start MySQL') {
            steps {
                echo "Démarrage du conteneur MySQL..."
                sh '''
                docker run -d --name $MYSQL_CONTAINER \
                    --network $DOCKER_NETWORK \
                    -e MYSQL_ROOT_PASSWORD=$DB_PASSWORD \
                    -e MYSQL_DATABASE=$DB_DATABASE \
                    mysql:8.0 || true
                '''
            }
        }

        stage('Wait for Database') {
            steps {
                echo "Attente que MySQL soit prêt..."
                sh '''
                until docker exec $MYSQL_CONTAINER \
                    mysqladmin ping -h"localhost" -u$DB_USERNAME -p$DB_PASSWORD --silent; do
                    sleep 2
                done
                '''
                echo "MySQL est prêt !"
            }
        }

        stage('Run Laravel in PHP container') {
            steps {
                script {
                    // Exécution dans un conteneur PHP pour avoir un environnement propre
                    docker.image('php:8.2-bullseye').inside("--network=$DOCKER_NETWORK") {

                        echo "Installation des dépendances système et PHP..."
                        sh '''
                        apt-get update -y
                        apt-get install -y unzip git curl libzip-dev mariadb-client
                        docker-php-ext-install pdo_mysql zip
                        '''

                        echo "Installation de Composer..."
                        sh '''
                        curl -sS https://getcomposer.org/installer | php
                        mv composer.phar /usr/local/bin/composer
                        '''

                        echo "Installation des dépendances Laravel..."
                        sh 'composer install --no-interaction --prefer-dist'

                        echo "Configuration de Laravel..."
                        sh '''
                        cp .env.example .env.testing || true
                        php artisan key:generate --env=testing --force
                        '''

                        echo "Migration de la base de données..."
                        sh 'php artisan migrate --env=testing --force'

                        echo "Exécution des tests Laravel..."
                        sh 'php artisan test --env=testing --without-tty'
                    }
                }
            }
        }
    }

    post {
        always {
            // Encapsulé dans script pour éviter MissingContextVariableException
            script {
                echo "Nettoyage des conteneurs et du réseau Docker..."
                sh 'docker rm -f $MYSQL_CONTAINER || true'
                sh 'docker network rm $DOCKER_NETWORK || true'
            }
        }

        success {
            echo "✅ Pipeline réussie : code stable"
        }

        failure {
            echo "❌ Pipeline échouée : vérifier le code et les erreurs"
        }
    }
}