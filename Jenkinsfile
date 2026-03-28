pipeline {
    agent any

    environment {
        DB_ID   = "db-${env.BUILD_NUMBER}"
        NET_ID  = "net-${env.BUILD_NUMBER}"
        // Image déjà prête (extensions + composer inclus)
        PHP_IMG = "thecodingmachine/php:8.2-v4-cli" 
    }

    stages {
        stage('🚀 Quick Setup & Test') {
            steps {
                withCredentials([string(credentialsId: 'laravel-db-password', variable: 'DB_PASS')]) {
                    script {
                        echo "--- Lancement rapide de l'infrastructure ---"
                        sh "docker network create ${NET_ID} || true"
                        sh "docker run -d --name ${DB_ID} --network ${NET_ID} -e MYSQL_ROOT_PASSWORD=${DB_PASS} -e MYSQL_DATABASE=testing mysql:8.0"

                        sh """
                            docker run --rm --network ${NET_ID} \
                                -v ${WORKSPACE}:/var/www/html \
                                -e DB_PASSWORD=${DB_PASS} \
                                ${PHP_IMG} \
                                bash -c '
                                    # Plus de apt-get ! Plus de docker-php-ext-install !
                                    
                                    composer install --no-interaction --prefer-dist --quiet
                                    
                                    cp .env.example .env
                                    sed -i "s/DB_HOST=127.0.0.1/DB_HOST=${DB_ID}/" .env
                                    sed -i "s/DB_PASSWORD=/DB_PASSWORD=${DB_PASS}/" .env
                                    php artisan key:generate --quiet

                                    echo "Vérification MySQL..."
                                    timeout 30s bash -c "until timeout 1s bash -c \"cat < /dev/tcp/${DB_ID}/3306\" 2>/dev/null; do sleep 2; done"

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