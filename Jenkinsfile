pipeline {
    agent any

    stages {
        stage('Prepare') {
            steps {
                sh 'composer update'
                sh 'rm -rf build/release'
                sh 'rm -rf build/release.tar'
                sh 'rm -rf build/coverage'
                sh 'rm -rf build/logs'
                sh 'rm -rf build/pdepend'
                sh 'mkdir -p build/'
                sh 'mkdir build/coverage'
                sh 'mkdir build/logs'
                sh 'mkdir build/pdepend'
                sh 'mkdir build/release'
            }
        }

        stage('Test'){
            steps {
                sh 'vendor/bin/phpunit -c phpunit.xml.dist || exit 0'
            }
        }

        stage('Checkstyle') {
            steps {
                sh 'vendor/bin/phpcs --report=xml --report-file=build/logs/checkstyle.xml --standard=PSR2 --extensions=php src/ || exit 0'
            }
        }

        stage('Static analyze') {
            steps {
                sh 'vendor/bin/phpstan analyse src/ --level 8 --error-format=checkstyle > build/logs/phpstan-output.xml || exit 0'
            }
        }

        stage('SonarQube analysis') {
            environment {
                scannerHome = tool 'sonar-scanner'
            }
            steps {
                withSonarQubeEnv('sonarqube') {
                    sh "${scannerHome}/bin/sonar-scanner"
                }
                timeout(time: 10, unit: 'MINUTES') {
                    waitForQualityGate abortPipeline: true
                }
            }
        }

        stage('Lines of Code') { steps { sh 'vendor/bin/phploc --count-tests --exclude vendor/ --log-csv build/logs/phploc.csv --log-xml build/logs/phploc.xml .' } }

        stage('Copy paste detection') {
            steps {
                sh 'vendor/bin/phpcpd --log-pmd build/logs/pmd-cpd.xml --exclude vendor .'
                /* dry canRunOnFailed: true, pattern: 'build/logs/pmd-cpd.xml' */
            }
        }

        stage('Mess detection') {
            steps {
                sh 'vendor/bin/phpmd . xml build/phpmd.xml --reportfile build/logs/pmd.xml --exclude vendor/ || exit 0'
                /* Rules set */
                /* pmd canRunOnFailed: true, pattern: 'build/logs/pmd.xml' */
            }
        }

        stage('Software metrics') {
            steps {
                sh 'vendor/bin/pdepend --jdepend-xml=build/logs/jdepend.xml --jdepend-chart=build/pdepend/dependencies.svg --overview-pyramid=build/pdepend/overview-pyramid.svg --ignore=vendor .'
            }
        }

        stage('Generate documentation') { steps { sh 'phpdox -f phpdox.xml' } }

        stage('Publish Analysis Reports') {
            steps {
                echo "Code coverage clover..."
                step([
                    $class: 'CloverPublisher',
                    cloverReportDir: 'build/coverage/',
                    cloverReportFileName: 'coverage-clover.xml',
                    healthyTarget: [methodCoverage: 70, conditionalCoverage: 80, statementCoverage: 80],
                    unhealthyTarget: [methodCoverage: 50, conditionalCoverage: 50, statementCoverage: 50],
                    failingTarget: [methodCoverage: 0, conditionalCoverage: 0, statementCoverage: 0]
                ])
                /*
                publishHTML(target: [
                    allowMissing: false,
                    alwaysLinkToLastBuild: false,
                    keepAll: true,
                    reportDir: 'build/coverage/coverage.html/',
                    reportFiles: 'index.html',
                    reportName: 'Code coverage'
                ])
                */
            }
        }

        stage('Push to Nexus') {
            steps {
                nexusArtifactUploader(
                    nexusVersion: 'nexus3',
                    protocol: 'http',
                    nexusUrl: 'http://nexus:8081',
                    groupId: 'com.example',
                    version: 1.0,
                    repository: 'demo-ci-php-2',
                    credentialsId: 'jenkins:jenkins',
                    artifacts: [
                        [artifactId: 'demo-ci-php-2',
                         classifier: '',
                         file: 'release/release.tar',
                         type: 'tar']
                    ]
                )
            }
        }
    }
}