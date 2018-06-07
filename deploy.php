<?php

namespace Deployer;

require 'recipe/laravel.php';

// Project name
set('application', 'clingen');

// Project repository
set('repository', 'git@bitbucket.org:shepsweb/clingen.git');

// [Optional] Allocate tty for git clone. Default value is false.
// set('git_tty', true);

// Shared files/dirs between deploys
add('shared_files', []);
add('shared_dirs', []);

// Writable dirs by web server
add('writable_dirs', []);

// Hosts
host('test')
    ->hostname('web3demo.schsr.unc.edu')
    ->stage('test')
    ->set('branch', 'demo')
    ->set('deploy_path', '/mnt/web/project/{{application}}-test');

host('demo')
    ->hostname('web3demo.schsr.unc.edu')
    ->stage('demo')
    ->set('branch', 'demo')
    ->set('deploy_path', '/mnt/web/project/{{application}}');

host('web3.schsr.unc.edu')
    ->stage('production')
    ->set('branch', 'master')
    ->set('deploy_path', '/mnt/web/project/{{application}}');

// Tasks
desc('Build for deploy, merge, and push');
task('build', function () {
    runLocally('npm run prod');
    runLocally('git ci -am "build js and css for deploy"');
    runLocally('git push');
})->local();

before('deploy:prepare', 'build');

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.

before('deploy:symlink', 'artisan:migrate');
