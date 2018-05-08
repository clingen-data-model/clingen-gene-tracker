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
host('web3demo.schsr.unc.edu')
    ->stage('dep')
    ->set('deploy_path', '/mnt/web/project/deployer-test-project');

// Tasks

task('build', function () {
    run('cd {{release_path}} && build');
});

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.

// before('deploy:symlink', 'artisan:migrate');

task('pwd', function () {
    $result = run('cd /mnt/web/project/{{application}}-test && pwd');
    writeln("Current dir: $result");
});

task('deploy-git', function () {
    $result = run('cd /mnt/web/project/{{application}}-test && . deploy.sh');
    writeln($result);
    writeln("deployed.");
});
