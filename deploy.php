<?php

declare(strict_types=1);

namespace Deployer;

require 'recipe/common.php';

set('application', 'calendar-ifsc-stream');

host('production')
    ->hostname('95.85.49.210')
    ->user(getenv('DEPLOY_USER'))
    ->set('deploy_path', '/home/{{user}}/web/cal.ifsc.stream/public_html')
    ->identityFile('~/.ssh/deploy_key');

add('shared_dirs', ['var']);
add('writable_dirs', ['var']);

set('writable_mode', 'chmod');

// Upload CI-built artifact instead of git-cloning on server
task('deploy:update_code', function () {
    upload(
        __DIR__ . '/',
        '{{release_path}}',
        [
            'options' => [
                '--include=public/***',
                '--include=src/***',
                '--include=config/***',
                '--include=vendor/***',
                '--include=var/***',
                '--exclude=*',
            ],
        ]
    );
});

// Composer install already done in CI — vendor uploaded
task('deploy:vendors', function () {
    writeln('Skipping composer install — vendor uploaded from CI build');
});

task('deploy', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'deploy:writable',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
]);

after('deploy:failed', 'deploy:unlock');
