pipeline {
    agent any

    stages {
        stage('🚀 Setup & Test') {
            steps {
                // On utilise withCredentials uniquement là où on en a besoin
                withCredentials([string(credentialsId: 'jenkins-mysql-root-password', variable: 'DB_PASS')]) {
                    script {
                        echo "Initialisation du réseau et de la DB..."
                        sh "docker network create net-${env.BUILD_NUMBER} || true"
                        sh "docker run -d --name db-${env.BUILD_NUMBER} --network net-${env.BUILD_NUMBER} -e MYSQL_ROOT_PASSWORD=${DB_PASS} -e MYSQL_DATABASE=testing mysql:8.0"

                        docker.image('php:8.2-bullseye').inside("--network=net-${env.BUILD_NUMBER}") {
                            sh """
                                apt-get update -qq && apt-get install -y -qq libzip-dev mariadb-client unzip
                                docker-php-ext-install pdo_mysql zip > /dev/null
                                curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
                                
                                composer install --no-interaction --prefer-dist
                                cp .env.example .env
                                sed -i "s/DB_HOST=127.0.0.1/DB_HOST=db-${env.BUILD_NUMBER}/" .env
                                sed -i "s/DB_PASSWORD=/DB_PASSWORD=${DB_PASS}/" .env
                                php artisan key:generate

                                until mysqladmin ping -hdb-${env.BUILD_NUMBER} -uroot -p${DB_PASS} --silent; do
                                    sleep 3
                                done

                                php artisan migrate --force
                                php artisan test --without-tty
                            """
                        }
                    }
                }
            }
        }
    }

    post {
        always {
            // Utilisation d'un bloc sh simple sans fioritures
            echo "Nettoyage final..."
            sh "docker rm -f db-${env.BUILD_NUMBER} || true"
            sh "docker network rm net-${env.BUILD_NUMBER} || true"
        }
    }
}