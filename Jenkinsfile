pipeline {
    agent any

    // On définit les variables ici, mais on utilisera env.BUILD_NUMBER directement dans le shell
    environment {
        DB_PASS_SECRET = credentials('jenkins-mysql-root-password')
    }

    stages {
        stage('🚀 Setup Infra') {
            steps {
                // Utilisation de BUILD_NUMBER directement pour garantir que ce n'est jamais null
                sh "docker network create net-${env.BUILD_NUMBER} || true"
                sh """
                    docker run -d --name db-${env.BUILD_NUMBER} \
                        --network net-${env.BUILD_NUMBER} \
                        -e MYSQL_ROOT_PASSWORD=${env.DB_PASS_SECRET} \
                        -e MYSQL_DATABASE=testing \
                        mysql:8.0
                """
            }
        }

        stage('🧪 Build & Test') {
            steps {
                script {
                    docker.image('php:8.2-bullseye').inside("--network=net-${env.BUILD_NUMBER}") {
                        sh """
                            apt-get update -qq && apt-get install -y -qq libzip-dev mariadb-client unzip
                            docker-php-ext-install pdo_mysql zip > /dev/null
                            curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
                            
                            composer install --no-interaction --prefer-dist
                            
                            cp .env.example .env
                            sed -i "s/DB_HOST=127.0.0.1/DB_HOST=db-${env.BUILD_NUMBER}/" .env
                            sed -i "s/DB_PASSWORD=/DB_PASSWORD=${env.DB_PASS_SECRET}/" .env
                            sed -i "s/DB_DATABASE=laravel/DB_DATABASE=testing/" .env
                            php artisan key:generate

                            until mysqladmin ping -hdb-${env.BUILD_NUMBER} -uroot -p${env.DB_PASS_SECRET} --silent; do
                                echo "Attente de MySQL..."
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

    post {
        always {
            // Utilisation du bloc script pour forcer le contexte sur l'agent actuel
            script {
                echo "🧹 Nettoyage des ressources du build ${env.BUILD_NUMBER}..."
                sh "docker rm -f db-${env.BUILD_NUMBER} || true"
                sh "docker network rm net-${env.BUILD_NUMBER} || true"
            }
        }
    }
}