<?php

\Logger::configure(
    [
        'rootLogger' => [
            'appenders' => ['facebook-instantarticles-traverser']
        ],
        'appenders' => [
            'facebook-instantarticles-traverser' => [
                'class' => 'LoggerAppenderConsole',
                'threshold' => 'INFO',
                'layout' => [
                    'class' => 'LoggerLayoutSimple'
                ]
            ]
        ]
    ]
);
