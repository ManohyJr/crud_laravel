pipeline {
    agent any

    options {
        timeout(time: 15, unit: 'MINUTES')
        timestamps()
    }

    environment {
        DB_ID  = "db-${env.BUILD_NUMBER}"
        NET_ID = "net-${env.BUILD_NUMBER}"
        // On définit l'image ici pour plus de clarté
        PHP_IMG = "php:8.2-bullseye"
    }

    stages {
        stage('🚀 Setup & Test') {
            steps {
                withCredentials([string(credentialsId: 'laravel-db-password', variable: 'DB_PASS')]) {
                    script {
                        echo "--- Initialisation du réseau et de MySQL ---"
                        sh "docker network create ${NET_ID} || true"
                        sh "docker run -d --name ${DB_ID} --network ${NET_ID} -e MYSQL_ROOT_PASSWORD=${DB_PASS} -e MYSQL_DATABASE=testing mysql:8.0"

                        echo "--- Lancement des tests dans le conteneur PHP ---"
                        // On remplace docker.image().inside par un docker run --rm
                        sh """
                            docker run --rm --network ${NET_ID} \
                                -v ${WORKSPACE}:/app \
                                -w /app \
                                ${PHP_IMG} \
                                sh -c '
                                    apt-get update -qq && apt-get install -y -qq libzip-dev mariadb-client unzip > /dev/null
                                    docker-php-ext-install pdo_mysql zip > /dev/null
                                    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
                                    
                                    composer install --no-interaction --prefer-dist
                                    cp .env.example .env
                                    sed -i "s/DB_HOST=127.0.0.1/DB_HOST=${DB_ID}/" .env
                                    sed -i "s/DB_PASSWORD=/DB_PASSWORD=${DB_PASS}/" .env
                                    php artisan key:generate

                                    echo "Attente MySQL..."
                                    until mysqladmin ping -h${DB_ID} -uroot -p${DB_PASS} --silent; do sleep 3; done

                                    php artisan migrate --force
                                    php artisan test --without-tty
                                '
                        """
                    }
                }
            }
        }
    }

    post {
        always {
            script {
                echo "--- Nettoyage ---"
                sh "docker rm -f ${DB_ID} || true"
                sh "docker network rm ${NET_ID} || true"
            }
        }
    }
}