pipeline {
    // Jenkins peut exécuter ce pipeline sur n’importe quel agent disponible
    agent any  

    // Définition des variables d'environnement globales
    environment {
        APP_ENV         = 'testing'  // Environnement Laravel pour les tests
        DB_CONNECTION   = 'mysql'    // Type de base de données utilisé
        DB_HOST         = 'mysql-ci' // Nom du conteneur MySQL
        DB_PORT         = '3306'     // Port MySQL
        DB_DATABASE     = 'testing'  // Nom de la base de données de test
        DB_USERNAME     = 'root'     // Nom d'utilisateur pour MySQL
        DB_PASSWORD     = credentials('jenkins-mysql-root-password') // Mot de passe stocké dans Jenkins Credentials (sécurisé)
        DOCKER_NETWORK  = 'ci-network' // Réseau Docker pour connecter PHP et MySQL
        MYSQL_CONTAINER = 'mysql-ci'   // Nom du conteneur MySQL
    }

    stages {

        stage('Prepare Environment') {
            steps {
                echo "Création du réseau Docker pour isoler les conteneurs..."
                // Crée un réseau Docker nommé ci-network si pas déjà existant
                sh 'docker network create $DOCKER_NETWORK || true'
            }
        }

        stage('Start MySQL') {
            steps {
                echo "Démarrage du conteneur MySQL..."
                // Lance MySQL dans un conteneur Docker sur le réseau dédié
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
                // Boucle jusqu'à ce que MySQL soit disponible
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
                    // On utilise un conteneur PHP comme environnement d'exécution
                    docker.image('php:8.2-bullseye').inside("--network=$DOCKER_NETWORK") {

                        echo "Installation des dépendances système et PHP..."
                        // Installe les bibliothèques nécessaires pour Laravel
                        sh '''
                        apt-get update -y
                        apt-get install -y unzip git curl libzip-dev mariadb-client
                        docker-php-ext-install pdo_mysql zip
                        '''

                        echo "Installation de Composer..."
                        // Télécharge et installe Composer globalement
                        sh '''
                        curl -sS https://getcomposer.org/installer | php
                        mv composer.phar /usr/local/bin/composer
                        '''

                        echo "Installation des dépendances Laravel..."
                        // Installe toutes les dépendances PHP du projet Laravel
                        sh 'composer install --no-interaction --prefer-dist'

                        echo "Configuration de Laravel..."
                        // Copie le fichier .env exemple pour créer .env.testing
                        sh '''
                        cp .env.example .env.testing || true
                        php artisan key:generate --env=testing --force
                        '''

                        echo "Migration de la base de données..."
                        // Exécute les migrations pour créer les tables dans MySQL
                        sh 'php artisan migrate --env=testing --force'

                        echo "Exécution des tests Laravel..."
                        // Lancement des tests unitaires et fonctionnels
                        sh 'php artisan test --env=testing --without-tty'
                    }
                }
            }
        }
    }

    post {
        always {
            echo "Nettoyage des conteneurs et du réseau Docker..."
            // Supprime le conteneur MySQL et le réseau Docker pour éviter la pollution
            sh '''
            docker rm -f $MYSQL_CONTAINER || true
            docker network rm $DOCKER_NETWORK || true
            '''
        }

        success {
            echo "Pipeline réussie ✅ : application stable"
        }

        failure {
            echo "Pipeline échouée ❌ : corriger les erreurs"
        }
    }
}