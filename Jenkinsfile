pipeline {
    agent any

    options {
        timeout(time: 15, unit: 'MINUTES')
        timestamps()
        // Empêche de voir les secrets dans les logs si une commande échoue
        buildDiscarder(logRotator(numToKeepStr: '10'))
    }

    environment {
        // Variables techniques (non secrètes)
        DB_ID  = "db-${env.BUILD_NUMBER}"
        NET_ID = "net-${env.BUILD_NUMBER}"
    }

    stages {
        stage('🔒 Setup & Test') {
            steps {
                // La méthode la plus sûre : le secret est masqué dans les logs (****)
                withCredentials([string(credentialsId: 'laravel-db-password', variable: 'DB_PASS')]) {
                    script {
                        echo "--- Initialisation de l'infrastructure sécurisée ---"
                        sh "docker network create ${NET_ID} || true"
                        
                        // Utilisation du secret uniquement dans le shell
                        sh """
                            docker run -d --name ${DB_ID} \
                                --network ${NET_ID} \
                                -e MYSQL_ROOT_PASSWORD=${DB_PASS} \
                                -e MYSQL_DATABASE=testing \
                                mysql:8.0
                        """

                        docker.image('php:8.2-bullseye').inside("--network=${NET_ID}") {
                            sh """
                                apt-get update -qq && apt-get install -y -qq libzip-dev mariadb-client unzip
                                docker-php-ext-install pdo_mysql zip > /dev/null
                                curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
                                
                                composer install --no-interaction --prefer-dist --optimize-autoloader
                                
                                cp .env.example .env
                                # Injection sécurisée dans le .env
                                sed -i "s/DB_HOST=127.0.0.1/DB_HOST=${DB_ID}/" .env
                                sed -i "s/DB_PASSWORD=/DB_PASSWORD=${DB_PASS}/" .env
                                php artisan key:generate

                                echo "Attente de la base de données..."
                                until mysqladmin ping -h${DB_ID} -uroot -p${DB_PASS} --silent; do
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
            script {
                // Nettoyage systématique pour ne laisser aucune trace
                echo "--- Nettoyage des ressources temporaires ---"
                sh "docker rm -f ${DB_ID} || true"
                sh "docker network rm ${NET_ID} || true"
            }
        }
        failure {
            echo "❌ Le build a échoué. Vérifiez les logs (les secrets sont masqués)."
        }
    }
}