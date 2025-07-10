<?php

include __DIR__ . '/src/Medoo.php';
include __DIR__ . '/src/function.php';
include __DIR__ . '/src/model.php';
include __DIR__ . '/src/compare.php';
global $_db, $_db_active, $_db_connects;
//连接默认数据库
if ($config['db_user'] && $config['db_pwd']) {
    if (!isset($config['db_dsn'])) {
        $config['db_dsn'] = "mysql:dbname={$config['db_name']};host={$config['db_host']};port={$config['db_port']}";
    }
    try {
        $pdo = new PDO($config['db_dsn'], $config['db_user'], $config['db_pwd']);
        $_db = new Medoo\Medoo([
            'pdo'     => $pdo,
            'type'    => 'mysql',
            'option'  => [
                PDO::ATTR_CASE => PDO::CASE_NATURAL
            ],
            'command' => [
                'SET SQL_MODE=ANSI_QUOTES'
            ],
            'error' => PDO::ERRMODE_WARNING
        ]);
        $_db_connects['default'] = $_db;
        $_db_active  = 'default';
    } catch (Exception $e) {
        $err = $e->getMessage();
    }
}

/**
 * 连接平台数据库
 */
if (
    isset($config['main_db_host'])
    && $config['main_db_name']
    && $config['main_db_user']
    && $config['main_db_pwd']

) {
    $config['main_db_port'] = $config['main_db_port'] ?: 3306;
    $main_db_config = [
        'db_host' => $config['main_db_host'],
        'db_name' => $config['main_db_name'],
        'db_user' => $config['main_db_user'],
        'db_pwd' => $config['main_db_pwd'],
        'db_port' => $config['main_db_port'],
    ];
    new_db($main_db_config, 'main');
}

/**
 * 连接读从库
 */

if (
    isset($config['read_db_host'])
    && $config['read_db_name']
    && $config['read_db_user']
    && $config['read_db_pwd']

) {
    $config['read_db_port'] = $config['read_db_port'] ?: 3306;
    $read_db_name = $config['read_db_name'];
    if (is_array($read_db_name)) {
        $read_db_name = $read_db_name[array_rand($read_db_name)];
    }
    $read_db_config = [
        'db_host' => $config['read_db_host'],
        'db_name' => $read_db_name,
        'db_user' => $config['read_db_user'],
        'db_pwd' => $config['read_db_pwd'],
        'db_port' => $config['read_db_port'],
    ];
    new_db($read_db_config, 'read');
}
