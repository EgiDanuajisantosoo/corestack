<?php

return [
    'install_sets' => [
        'core' => [
            [
                'source' => 'ResponseService.stub',
                'target' => 'app/Services/ResponseService.php',
            ],
            [
                'source' => 'Controller.stub',
                'target' => 'app/Http/Controllers/Controller.php',
            ],
        ],
        'gitlab' => [
            [
                'source' => 'gitlab-mr.stub',
                'target' => '.gitlab/merge_request_templates/pull-request.md',
            ],
        ],
    ],

    'templates' => [
        [
            'source' => 'app/Support/ApiResponse.stub',
            'target' => 'app/Support/ApiResponse.php',
        ],
        [
            'source' => 'app/Traits/HasApiResponse.stub',
            'target' => 'app/Traits/HasApiResponse.php',
        ],
        [
            'source' => 'app/Repositories/BaseRepository.stub',
            'target' => 'app/Repositories/BaseRepository.php',
        ],
        [
            'source' => 'app/Services/BaseService.stub',
            'target' => 'app/Services/BaseService.php',
        ],
        [
            'source' => '.gitlab/merge_request_templates/pull-request.stub',
            'target' => '.gitlab/merge_request_templates/pull-request.md',
        ],
    ],
];
