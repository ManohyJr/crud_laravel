pipeline {
    agent any

    environment {
        DB_ID   = "db-${env.BUILD_NUMBER}"
        NET_ID  = "net-${env.BUILD_NUMBER}"
        PHP_IMG = "thecodingmachine/php:8.2-v4-cli"
    }

    stages {
        stage('🚀 Setup & Test') {
            steps {
                withCredentials([string(credentialsId: 'laravel-db-password', variable: 'DB_PASS')]) {
                    script {
                        echo "--- Infra ---"
                        sh "docker network create ${NET_ID} || true"
                        sh "docker run -d --name ${DB_ID} --network ${NET_ID} -e MYSQL_ROOT_PASSWORD=${DB_PASS} -e MYSQL_DATABASE=testing mysql:8.0"

                        echo "--- Tests ---"
                        sh """
                            docker run --rm --network ${NET_ID} \
                                -v \$(pwd):/usr/src/app \
                                -w /usr/src/app \
                                -e DB_PASSWORD=${DB_PASS} \
                                ${PHP_IMG} \
                                bash -c '
                                    # On vérifie où on est
                                    ls -la
                                    
                                    composer install --no-interaction --prefer-dist --quiet
                                    
                                    if [ -f .env.example ]; then
                                        cp .env.example .env
                                        sed -i "s/DB_HOST=127.0.0.1/DB_HOST=${DB_ID}/" .env
                                        sed -i "s/DB_PASSWORD=/DB_PASSWORD=${DB_PASS}/" .env
                                        php artisan key:generate
                                    fi

                                    echo "Vérification MySQL..."
                                    # Version simplifiée du test de connexion
                                    sleep 10
                                    
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
                sh "docker rm -f ${DB_ID} || true"
                sh "docker network rm ${NET_ID} || true"
            }
        }
    }
}