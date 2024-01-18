pipeline {
    agent any

    stages {
        stage('Build') {
            steps {
                echo 'Building the project...'
            }
        }

        stage('Test') {
            steps {
                echo 'Running tests...'
            }
        }

        stage('SonarQube Analysis') {
            steps {
                script {
                    def scannerHome = tool 'Hive-SonarQube-Scanner'
                    echo "Scanner Home: ${scannerHome}"
                    sh(script: "${scannerHome}/bin/sonar-scanner -Dsonar.projectKey=hive-project-mike")
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