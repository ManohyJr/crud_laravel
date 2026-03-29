pipeline {
    agent any

    environment {
        // Identifiants uniques pour éviter les conflits entre builds
        DB_HOST     = "db-${env.BUILD_NUMBER}"
        NET_ID      = "net-${env.BUILD_NUMBER}"
        
        // Configuration de la base de données (reprise de ton GitLab)
        MYSQL_DATABASE      = 'testing'
        DB_CONNECTION       = 'mysql'
        DB_PORT             = '3306'
        DB_USERNAME         = 'root'
        
        // Récupération sécurisée du secret Jenkins
        DB_PASS_SECRET      = credentials('laravel-db-password')
    }

    stages {
        stage('🛠️ Initialisation Infra') {
            steps {
                script {
                    echo "--- Création du réseau isolé ---"
                    sh "docker network create ${NET_ID} || true"

                    echo "--- Lancement du service MySQL ---"
                    sh """
                        docker run -d --name ${DB_HOST} \
                            --network ${NET_ID} \
                            -e MYSQL_ROOT_PASSWORD=${DB_PASS_SECRET} \
                            -e MYSQL_DATABASE=${MYSQL_DATABASE} \
                            mysql:8.0
                    """
                }
            }
        }

        stage('🧪 Build & Tests Laravel') {
            steps {
                script {
                    echo "--- Exécution des tests dans le conteneur PHP ---"
                    // On monte le dossier actuel (pwd) dans /app du conteneur
                    sh """
                        docker run --rm --network ${NET_ID} \
                            -v \$(pwd):/app \
                            -w /app \
                            -e DB_CONNECTION=${DB_CONNECTION} \
                            -e DB_HOST=${DB_HOST} \
                            -e DB_PORT=${DB_PORT} \
                            -e DB_DATABASE=${MYSQL_DATABASE} \
                            -e DB_USERNAME=${DB_USERNAME} \
                            -e DB_PASSWORD=${DB_PASS_SECRET} \
                            php:8.2-bullseye \
                            bash -c '
                                # 1. Installation des dépendances (Comme ton before_script)
                                apt-get update -yqq && apt-get install -yqq libzip-dev zip unzip git mariadb-client > /dev/null
                                docker-php-ext-install pdo_mysql zip > /dev/null
                                curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer > /dev/null

                                # 2. Préparation Laravel
                                composer install --prefer-dist --no-interaction --quiet
                                cp .env.example .env
                                php artisan key:generate

                                # 3. Attente de MySQL (Ta logique GitLab)
                                echo "Attente du démarrage de MySQL sur ${DB_HOST}..."
                                until mysqladmin ping -h"${DB_HOST}" -u"${DB_USERNAME}" -p"${DB_PASS_SECRET}" --silent; do 
                                    sleep 2
                                done
                                echo "MySQL est prêt !"

                                # 4. Migration et exécution des tests
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
                echo "🧹 Nettoyage des ressources du build ${env.BUILD_NUMBER}..."
                // On supprime le conteneur DB et le réseau pour libérer la RAM
                sh "docker rm -f ${DB_HOST} || true"
                sh "docker network rm ${NET_ID} || true"
            }
        }
        success {
            echo "✅ Félicitations ! Tous les tests Laravel sont passés."
        }
        failure {
            echo "❌ Le pipeline a échoué. Vérifie les logs de migration ou de test."
        }
    }
}