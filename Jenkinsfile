stage('Test') {
            agent {
                docker {
                    image 'php:8.2-bullseye'
                    args '-u root --network=host'
                }
            }
            steps {
                unstash 'vendor-deps'
                
                script {
                    // Nettoyage de sécurité
                    sh 'docker rm -f mysql_test || true'
                    
                    // Lancement MySQL
                    sh 'docker run -d --name mysql_test -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=testing -p 3306:3306 mysql:8.0'
                    
                    try {
                        sh '''
                            cp .env.example .env.testing
                            php artisan key:generate --env=testing

                            echo "Attente de MySQL sur 127.0.0.1..."
                            # On utilise -h 127.0.0.1 pour forcer le protocole TCP (important sur Debian 13)
                            until mysqladmin ping -h"127.0.0.1" -u"root" -p"root" --silent; do 
                                sleep 3
                            done
                            echo "MySQL est en ligne !"

                            php artisan migrate --env=testing --force
                            php artisan test --env=testing
                        '''
                    } finally {
                        sh 'docker rm -f mysql_test || true'
                    }
                }
            }
        }