pipeline {
    agent any

    environment {
        // Configuration pour que Laravel trouve MySQL sur l'hôte
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
                    // Syntaxe simplifiée pour Debian 13 et Jenkins
                    args '-u root --network=host'
                }
            }
            steps {
                sh '''
                    # Installation des dépendances système
                    apt-get update -yqq
                    apt-get install -yqq libzip-dev zip unzip git default-mysql-client
                    
                    # Extensions PHP nécessaires pour Laravel
                    docker-php-ext-install pdo_mysql zip > /dev/null 2>&1
                    
                    # Installation de Composer
                    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer > /dev/null 2>&1
                    
                    # Installation des packages PHP
                    composer install --prefer-dist --no-ansi --no-interaction --no-progress
                '''
                // On sauvegarde le dossier vendor pour le stage suivant
                stash includes: 'vendor/**', name: 'vendor-deps'
            }
        }

        stage('Test') {
            agent {
                docker {
                    image 'php:8.2-bullseye'
                    args '-u root --network=host'
                }
            }
            steps {
                unstash 'vendor-deps'
                
                script {
                    // On s'assure qu'aucun vieux conteneur MySQL ne traîne
                    sh 'docker rm -f mysql_test || true'
                    
                    // Lancement du conteneur MySQL 8.0
                    sh 'docker run -d --name mysql_test -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=testing -p 3306:3306 mysql:8.0'
                    
                    try {
                        sh '''
                            # Config Laravel
                            cp .env.example .env.testing
                            php artisan key:generate --env=testing

                            # Attente du démarrage réel de MySQL
                            echo "Vérification de la connexion MySQL..."
                            until mysqladmin ping -h"127.0.0.1" -u"root" -p"root" --silent; do 
                                echo "MySQL n'est pas encore prêt..."
                                sleep 3
                            done
                            echo "Connexion établie !"

                            # Exécution des migrations et des tests
                            php artisan migrate --env=testing --force
                            php artisan test --env=testing
                        '''
                    } finally {
                        // Très important sur Debian : on nettoie le conteneur après le test
                        sh 'docker rm -f mysql_test || true'
                    }
                }
            }
        }
    }
}