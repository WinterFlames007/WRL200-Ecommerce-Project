<?php
namespace App\Core;

class Database
{
    private static $connection = null;

    public static function connect()
    {
        if (self::$connection === null) {
            $config = require __DIR__ . '/../../config/config.php';
            $db = $config['db'];

            self::$connection = mysqli_connect(
                $db['host'],
                $db['username'],
                $db['password'],
                $db['dbname'],
                (int)$db['port']
            );

            if (!self::$connection) {
                die('Database connection failed: ' . mysqli_connect_error());
            }
        }

        return self::$connection;
    }
}