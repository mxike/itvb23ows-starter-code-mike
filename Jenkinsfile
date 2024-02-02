pipeline {
    agent any

    stages {
        stage('Build') {
            steps {
                echo 'Building the project...'
                checkout scm
            }
        }

        stage('Test') {
            steps {
                echo "running tests";
                dir('app/src') {
                    sh 'composer install phpunit'
                    sh 'phpunit /tests'
                }
            }
        }

        stage('SonarQube Analysis') {
            steps {
                script { scannerHome = tool 'Hive-SonarQube-Scanner' }
                withSonarQubeEnv('Hive-SonarQube-Server') {
                    sh "${scannerHome}/bin/sonar-scanner -Dsonar.projectKey=hive-dev-mike -Dsonar.login=squ_25844deed3a28da6371302d287dba553f8563fd3"
                }
                echo 'Sonarqube working...'
            }
        }

        stage('Deploy') {
            steps {
                echo 'Deploying...'
            }
        }
    }

    post {
        success {
            echo 'Build successful! Deploying...'
        }
        failure {
            echo 'Build failed! Notify the team...'
        }
    }
}