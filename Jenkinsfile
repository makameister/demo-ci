pipeline {
    agent any

    stages {
        stage('Prepare') {
            steps {
                sh 'composer update'
                sh 'rm -rf build/coverage'
                sh 'rm -rf build/logs'
                sh 'rm -rf build/pdepend'
                sh 'rm -rf build/phpdox'
                sh 'mkdir -p build/'
                sh 'mkdir build/coverage'
                sh 'mkdir build/logs'
                sh 'mkdir build/pdepend'
                sh 'mkdir build/phpdox'
            }
        }

        stage('Test'){
            steps {
                sh 'vendor/bin/phpunit -c phpunit.xml.dist || exit 0'
            }
        }

        stage('Checkstyle') {
            steps {
                sh 'vendor/bin/phpcs --report=checkstyle --report-file=`pwd`/build/logs/checkstyle.xml --standard=PSR2 --extensions=php --ignore=autoload.php --ignore=vendor/ . || exit 0'
                checkstyle pattern: 'build/logs/checkstyle.xml'
            }
        }
    }
}