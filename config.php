<?php
session_start();

$db_host = '134.90.167.42';
$db_port = 10306;
$db_user = 'Karpova';
$db_pass = '9TkG_K';
$db_name = 'project_Karpova';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($db->connect_error) {
    die("Ошибка подключения к БД: " . $db->connect_error);
}

$db->set_charset("utf8mb4");
?>