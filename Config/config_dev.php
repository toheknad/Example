<?php
// асоциативный массив конфигурации движка
$config = [
    'isDebug'  => true, // режим отладки включён

    // раздел пути
    'path'     => [
        'controllers' => 'Controllers/',   // путь к контролерам
        'models'      => 'Models/',        // путь к моделям
        'templates'   => 'Templates/',     // путь к моделям
        'cache'       => 'Cache/',     // путь к моделям
    ],

    // Маршруты 1-го уровня <> контролеры, тут можно делать и алиасы важен регистр в именах
    'alias'    => [
        'default' => 'DefaultController.php',   // дефолтный контролер
        'blog'    => 'BlogController.php',      // контролер блога
        'user'    => 'UserController.php',      // контролер управление пользователемя
    ],

    // настройки БД
    'database' => [
        'server'   => 'localhost',  // сервер БД
        'user'     => 'root',       // пользователь БД
        'password' => '',           // пароль пользователя БД
        'name'     => 'alevada'     // Имя БД
    ],


    'defaultAdminPassword' => '123', // Дефолтный пароль амдина при создании БД
    'saltPassword'         => '0192837465_pqwoeiru-YT', // соль сайта для паролей

];