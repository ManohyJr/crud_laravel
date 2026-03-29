pipeline {
    agent any

    environment {
        DB_HOST = "db-${env.BUILD_NUMBER}"
        NET_ID  = "net-${env.BUILD_NUMBER}"
        // Image complète avec PHP 8.2 + Composer + Extensions
        PHP_IMG = "thecodingmachine/php:8.2-v4-cli"
        
        MYSQL_DATABASE = 'testing'
        DB_PASS_SECRET = credentials('laravel-db-password')
    }

    stages {
        stage('🚀 Build & Test') {
            steps {
                script {
                    sh "docker network create ${NET_ID} || true"

                    // 1. Lancement de MySQL
                    sh """
                        docker run -d --name ${DB_HOST} \
                            --network ${NET_ID} \
                            -e MYSQL_ROOT_PASSWORD=${DB_PASS_SECRET} \
                            -e MYSQL_DATABASE=${MYSQL_DATABASE} \
                            mysql:8.0
                    """

                    // 2. Exécution des tests avec attente PHP
                    sh """
                        docker run --rm --network ${NET_ID} \
                            -v \$(pwd):/app \
                            -w /app \
                            -e DB_CONNECTION=mysql \
                            -e DB_HOST=${DB_HOST} \
                            -e DB_PORT=3306 \
                            -e DB_DATABASE=${MYSQL_DATABASE} \
                            -e DB_USERNAME=root \
                            -e DB_PASSWORD=${DB_PASS_SECRET} \
                            ${PHP_IMG} \
                            bash -c '
                                # Installation des dépendances Laravel
                                composer install --no-interaction --prefer-dist --quiet
                                if [ ! -f .env ]; then cp .env.example .env; fi
                                php artisan key:generate --quiet

                                echo "Attente de MySQL via PHP..."
                                # Ce script remplace mysqladmin ping
                                php -r "
                                    \\\$stderr = fopen(\'php://stderr\', \'w\');
                                    for (\\\$i = 0; \\\$i < 30; \\\$i++) {
                                        try {
                                            new PDO(\'mysql:host=${DB_HOST};dbname=${MYSQL_DATABASE}\', \'root\', \'${DB_PASS_SECRET}\');
                                            fwrite(\\\$stderr, \'MySQL est prêt !\\n\');
                                            exit(0);
                                        } catch (Exception \\\$e) {
                                            fwrite(\\\$stderr, \'En attente de MySQL...\\n\');
                                            sleep(2);
                                        }
                                    }
                                    exit(1);
                                "

                                # Une fois connecté, on lance tout
                                php artisan migrate --force
                                php artisan test --without-tty
                            '
                    """
                }
            }
        }
    }

    post {
        always {
            script {
                sh "docker rm -f ${DB_HOST} || true"
                sh "docker network rm ${NET_ID} || true"
            }
        }
    }
}