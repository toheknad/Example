<?php
require_once('Lib/Init.php');

// Старт приложения.
// Подаём постфикс конфигурационного файла например  dev-отладочная конфигурация, prod - боевая и т.д.
// соответствено в директории Config\ для каждого постфикс должен быть свой файл
// например config_dev.php и/или config_prod.php
//
// потом сюда ещё веротяно добавим некоторые ыозможности по конфигурации приложения

run('dev');