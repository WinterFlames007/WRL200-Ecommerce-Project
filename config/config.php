<?php

return [
    'app' => [
        'name' => 'WorkRelated E-Commerce',
        'url' => 'https://s2209682.ncgrp.xyz',
        'session_name' => 'workrelated_session',
    ],

    'db' => [
        'host' => 'localhost',
        'port' => '3306',
        'dbname' => 'workrelateddb',
        'username' => 'workrelateddb',
        'password' => 'test1234',
    ],

    'stripe' => [
,
        'secret_key' => 'YOUR_STRIPE_SECRET_KEY',
        'publishable_key' => 'YOUR_STRIPE_PUBLISHABLE_KEY',
        'webhook_secret' => 'YOUR_WEBHOOK_SECRET',
        'currency' => 'gbp',
    ],
];