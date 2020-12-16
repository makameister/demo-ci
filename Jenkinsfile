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

        stage('SonarQube analysis') {
            steps {
                def scannerHome = tool 'sonar-scanner';
                withSonarQubeEnv('sonar-scanner') {
                  sh "${scannerHome}/bin/sonar-scanner"
                }
            }
        }

        stage('Lines of Code') { steps { sh 'vendor/bin/phploc --count-tests --exclude vendor/ --log-csv build/logs/phploc.csv --log-xml build/logs/phploc.xml .' } }

        stage('Copy paste detection') {
            steps {
                sh 'vendor/bin/phpcpd --log-pmd build/logs/pmd-cpd.xml --exclude vendor . || exit 0'
                dry canRunOnFailed: true, pattern: 'build/logs/pmd-cpd.xml'
            }
        }

        stage('Software metrics') {
            steps {
                sh 'vendor/bin/pdepend --jdepend-xml=build/logs/jdepend.xml --jdepend-chart=build/pdepend/dependencies.svg --overview-pyramid=build/pdepend/overview-pyramid.svg --ignore=vendor .'
            }
        }
    }
}