<?php

return [
    'app' => [
        'name' => 'WorkRelated E-Commerce',
        'url' => 'http://localhost',
        'session_name' => 'workrelated_session',
    ],

    'db' => [
        'host' => 'localhost',
        'port' => '3306',
        'dbname' => 'your_database_name',
        'username' => 'your_database_user',
        'password' => 'your_database_password',
    ],

    'stripe' => [
        'publishable_key' => 'your_publishable_key',
        'secret_key' => 'your_secret_key',
        'webhook_secret' => 'your_webhook_secret',
    ],

    'mail' => [
        'host' => 'smtp.example.com',
        'username' => 'your_email@example.com',
        'password' => 'your_email_password',
        'port' => 587,
        'encryption' => 'tls',
        'from_email' => 'your_email@example.com',
        'from_name' => 'E-Commerce App',
    ],
];
