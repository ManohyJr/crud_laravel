pipeline {
    agent any

    stages {
        stage('Checkout') {
            steps {
                // Jenkins récupère automatiquement le code si configuré avec Git
                echo "Récupération du code source..."
            }
        }

        stage('Installation') {
            steps {
                echo "Installation des dépendances avec Composer..."
                // On utilise --no-interaction pour éviter que le build bloque
                sh 'composer install --no-interaction --prefer-dist'
            }
        }

        stage('Configuration') {
            steps {
                echo "Préparation de l'environnement Laravel..."
                // Création du .env s'il n'existe pas
                sh 'cp -n .env.example .env || true'
                sh 'php artisan key:generate --force'
            }
        }

        stage('Tests') {
            steps {
                echo "Exécution des tests unitaires et fonctionnels..."
                // La commande de test pure
                sh 'php artisan test --without-tty'
            }
        }
    }

    post {
        success {
            echo "✅ Tests réussis ! Le code est stable."
        }
        failure {
            echo "❌ Échec des tests. Vérifie le code avant de commit."
        }
    }
}