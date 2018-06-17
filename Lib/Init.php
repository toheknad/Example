<?php
/**
 * обрабоотка буфера вывода и вывода ошибок
 */
session_start(); // управление сессией будет пока тут, потом если что выделим. Сразу стартуем
ob_start(); // первым делом включаем буферизацию вывода, чтобы свободно работать с куками и http-заголовкам

require_once('Lib/Avd.php'); // подключаем движок
require_once('Lib/Controller.php'); // подключаем движок
require_once('Lib/Table.php'); // подключаем движок
require_once('Lib/Tmpl.php'); // подключаем шаблонизатор

Use Main\Avd;
Use Main\Controller;
Use Main\Table;


/** @var Avd $avd объект движка сайта */
$avd = null;

function shutdown() // на случай Exit или фатальной ошибки
{
    global $avd;
    $log = ob_get_clean();
    if ($avd) {
        echo $avd->getResponse();
        if ($avd->getIsDebug()) {
            $avd->log($log);
            echo '</br>+---------------------------------------------------------------------------------------------+</br>';
            echo 'Отладочная информация</br>';
            echo '+---------------------------------------------------------------------------------------------+</br>';
            echo $avd->getLog();
        }
    }
}

register_shutdown_function('shutdown');


function run($mode = 'dev')
{
    global $avd;

    try {
        $avd = new Avd($mode); // создаём класс движка и начинаем с ним работать, напихаем в него конфу

        /** @var  Controller $cnt */
        $cnt = $avd->getController();
        if ($cnt) {
            echo $cnt->Action();
        }

        // тут будет дозаполнение ответа контролера или его кэша
        // пока вывод сообщений и заполнение каких то системных констант, напрмимер адреса сайта и т.д.


        // отдаём пользователю

    } catch (Exception $e) {
        echo 'Ошибка </br>';
        echo $e->getMessage() . '</br>';

    } finally {
        // тут убиваем контролер
        echo 'Финал <br>';

    }

}


