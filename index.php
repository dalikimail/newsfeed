<?php
/**
 *  @file index.php
 *  @brief Точка входа, ядро сайта грузится через core.php, запускается Router для перехвата GET запросов
 */
ini_set('display_errors', 0);
require_once('classes/core.php');

$Router = new Router( $_GET );
