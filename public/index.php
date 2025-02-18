<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config/config.php';
use app\classes\Database;
use app\classes\Bot;

$db = Database::getInstance();
$conn = $db->getConnection();

$bot = new Bot(API_TOKEN);
$bot->request();