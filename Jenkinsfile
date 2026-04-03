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
        stage('Pipeline Complete') {
            steps {
                script {
                    // 1. Nettoyage et lancement de la DB sur l'hôte (Debian)
                    sh 'docker rm -f mysql_test || true'
                    sh 'docker run -d --name mysql_test -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=testing -p 3306:3306 mysql:8.0'
                    
                    // 2. Utilisation de PHP avec l'option --network host pour éviter les erreurs DNS
                    docker.image('php:8.2-bullseye').inside('--network host -u root') {
                        sh '''
                            # On installe tout d'un coup
                            apt-get update -yqq || (sleep 5 && apt-get update -yqq)
                            apt-get install -yqq libzip-dev zip unzip git default-mysql-client
                            docker-php-ext-install pdo_mysql zip > /dev/null 2>&1
                            
                            # Installation Composer
                            curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
                            
                            # Laravel Setup & Test
                            composer install --prefer-dist --no-interaction
                            cp .env.example .env.testing
                            php artisan key:generate --env=testing
                            
                            echo "Attente de la base de données..."
                            until mysqladmin ping -h"127.0.0.1" -u"root" -p"root" --silent; do 
                                sleep 2
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
            sh 'docker rm -f mysql_test || true'
        }
    }
}