pipeline {
    agent any

    environment {
        // Force la récupération des credentials dès le début
        DB_PASS_SECRET  = credentials('jenkins-mysql-root-password')
        DB_ID           = "db-${env.BUILD_NUMBER}"
        NET_ID          = "net-${env.BUILD_NUMBER}"
    }

    stages {
        stage('🚀 Setup') {
            steps {
                sh "docker network create ${env.NET_ID} || true"
                sh """
                    docker run -d --name ${env.DB_ID} \
                        --network ${env.NET_ID} \
                        -e MYSQL_ROOT_PASSWORD=${env.DB_PASS_SECRET} \
                        -e MYSQL_DATABASE=testing \
                        mysql:8.0
                """
            }
        }

        stage('🧪 Build & Test') {
            steps {
                script {
                    docker.image('php:8.2-bullseye').inside("--network=${env.NET_ID}") {
                        sh '''
                            apt-get update -qq && apt-get install -y -qq libzip-dev mariadb-client unzip
                            docker-php-ext-install pdo_mysql zip > /dev/null
                            curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
                            
                            composer install --no-interaction --prefer-dist
                            
                            cp .env.example .env
                            sed -i "s/DB_HOST=127.0.0.1/DB_HOST=${DB_ID}/" .env
                            sed -i "s/DB_PASSWORD=/DB_PASSWORD=${DB_PASS_SECRET}/" .env
                            sed -i "s/DB_DATABASE=laravel/DB_DATABASE=testing/" .env
                            php artisan key:generate

                            until mysqladmin ping -h${DB_ID} -uroot -p${DB_PASS_SECRET} --silent; do
                                sleep 3
                            done

                            php artisan migrate --force
                            php artisan test --without-tty
                        '''
                    }
                }
            }
        }
    }

    post {
        always {
            // CRITIQUE : On ré-alloue un node pour être sûr d'avoir accès au shell Docker
            node('built-in || main || master') { 
                echo "🧹 Nettoyage forcé..."
                sh "docker rm -f ${env.DB_ID} || true"
                sh "docker network rm ${env.NET_ID} || true"
            }
        }
        success { echo "✅ Super ! Ça fonctionne." }
        failure { echo "❌ Zut, regarde les logs au-dessus." }
    }
}