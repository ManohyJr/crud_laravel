pipeline {
    agent any

    options {
        timeout(time: 15, unit: 'MINUTES') // Évite les builds infinis
        timestamps() // Ajoute l'heure devant chaque ligne de log
        ansiColor('xterm') // Rend les logs plus lisibles (nécessite le plugin AnsiColor)
    }

    environment {
        // Identifiants uniques pour éviter les collisions entre builds parallèles
        DB_ID           = "db-${env.BUILD_ID}"
        NET_ID          = "net-${env.BUILD_ID}"
        
        // Configuration Laravel / DB
        DB_PASSWORD     = credentials('jenkins-mysql-root-password')
        APP_ENV         = 'testing'
        DB_DATABASE     = 'testing'
        DB_USERNAME     = 'root'
    }

    stages {
        stage('🚀 Infrastructure') {
            steps {
                echo "--- Préparation du réseau isolé : ${env.NET_ID} ---"
                sh "docker network create ${env.NET_ID}"
                
                echo "--- Lancement de MySQL 8.0 ---"
                sh """
                    docker run -d --name ${env.DB_ID} \
                        --network ${env.NET_ID} \
                        -e MYSQL_ROOT_PASSWORD=${env.DB_PASSWORD} \
                        -e MYSQL_DATABASE=${env.DB_DATABASE} \
                        mysql:8.0
                """
            }
        }

        stage('🧪 Build & Test') {
            steps {
                script {
                    // Utilisation de l'image officielle PHP Bullseye
                    docker.image('php:8.2-bullseye').inside("--network=${env.NET_ID}") {
                        echo "--- Installation des dépendances Système & PHP ---"
                        sh '''
                            apt-get update -qq && apt-get install -y -qq libzip-dev mariadb-client unzip
                            docker-php-ext-install pdo_mysql zip > /dev/null
                            curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
                        '''

                        echo "--- Installation des dépendances Laravel ---"
                        sh "composer install --no-interaction --prefer-dist --optimize-autoloader"

                        echo "--- Configuration de l'environnement ---"
                        sh """
                            cp .env.example .env
                            sed -i 's/DB_HOST=127.0.0.1/DB_HOST=${env.DB_ID}/' .env
                            sed -i 's/DB_PASSWORD=/DB_PASSWORD=${env.DB_PASSWORD}/' .env
                            sed -i 's/DB_DATABASE=laravel/DB_DATABASE=${env.DB_DATABASE}/' .env
                            php artisan key:generate
                        """

                        echo "--- Attente de la base de données ---"
                        sh """
                            until mysqladmin ping -h${env.DB_ID} -u${env.DB_USERNAME} -p${env.DB_PASSWORD} --silent; do
                                echo "En attente de MySQL..."
                                sleep 3
                            done
                        """

                        echo "--- Migration et Tests Unitaires ---"
                        sh "php artisan migrate --force"
                        sh "php artisan test --without-tty"
                    }
                }
            }
        }
    }

    post {
        always {
            echo "🧹 Nettoyage des ressources Docker..."
            sh "docker rm -f ${env.DB_ID} || true"
            sh "docker network rm ${env.NET_ID} || true"
        }
        
        success {
            echo "✅ BUILD TERMINÉ AVEC SUCCÈS"
        }

        failure {
            echo "❌ BUILD ÉCHOUÉ"
        }
    }
}