<?php

/**
 * класс движка сайта
 * 10.02.2018
 */

namespace Main;

Use Main\Controller;
use Mysqli;
use Exception;

class Avd
{

    private $num = 0; // порядковый номер движка, для внутренего использования

    private $config = []; // параметры конфигурационого файла

    private $uri; // запрос пользователя

    private $fullUri; // запрос пользователя, полный путь

    private $partsUri = []; // массив Uri

    private $log = []; // Логи отладки

    private $db = null; // подключение к БД

    private $response = ''; // Ответ пользователю

    /**
     * Avd constructor.
     *
     * @param string $mode режим в котором запускать, по сути постфикс конфигурациного файла
     *                     например mode='dev' загружаем конфигурацию config_dev.php, режим отладки
     *                     или mode='prod' загружаем конфигурацию config_prod.php, режим боевого сервера и т.д.
     */
    public function __construct($mode = '')
    {
        static $count = 0;
        $count++;
        $this->num = $count;

        // тут то что должно выполняться один раз, потому что движок должен быть один на запрос
        // ну чисто проверка на всякий случай, вдруг когда-то пондобится два движка, ну и как защита от запары
        if ($this->num === 1) {

        }

        // проверяем, подключаем и вычитываем конфигруционный файл в движок
        $fileName = 'Config/config';
        if ($mode) {
            $fileName .= '_' . $mode . '.php';
        } else {
            $fileName .= '.php';
        }

        if (file_exists($fileName)) {
            include_once $fileName;
        }

        if (isset($config)) {
            $this->config = $config;
        }

        // полный адрес страницы, запрошеной пользователем
        $this->fullUri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        // запрос пользователя
        $uri = strtolower($_SERVER['REQUEST_URI']);

        // проверяем, что бы в URL не было ничего, кроме символов
        // алфавита (a-zA-Z), цифр (0-9), а также . / - _
        if (preg_match("/([^a-zA-Z0-9\.\/\-\_])/", $uri)) {
            $this->uri = false; // ошибка запроса, для последующей передачи в 404 HTTP_REFERER
        } else {
            $this->uri      = $uri;
            $this->partsUri = preg_split("/(\/|-|_|\.)/", $uri, -1, PREG_SPLIT_NO_EMPTY);
        }

        $this->response = '';
    }


    /**
     * Режим отладки
     * @return bool
     */
    public function getIsDebug()
    {
        if (isset($this->config['isDebug'])) {
            return (bool) $this->config['isDebug'];
        }
        return false;
    }

    /**
     * Возвращает раздел конфига
     *
     * @param string $section
     *
     * @return array|mixed
     */
    public function getConfig($section = '')
    {
        if (isset($this->config[$section])) {
            return $this->config[$section];
        }
        return [];
    }

    public function getConfigAlias()
    {
        return $this->getConfig('alias');
    }

    public function getConfigPath()
    {
        return $this->getConfig('path');
    }

    public function getConfigDB()
    {
        return $this->getConfig('database');
    }


    public function getInfo()
    {
        return [
            'name'    => 'класс движка сайта',
            'version' => '1.0.0',
        ];
    }

    public function getNum()
    {
        return $this->num;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getFullUri()
    {
        return $this->fullUri;
    }

    public function getPartsUri()
    {
        return $this->partsUri;
    }

    /**
     * @param string $controllerAlias
     * если передан раут контролера, то возвращаем контролер по рауту
     * иначе возвращаем контролер, который определён пользовательским запросом
     *
     * @return null|object
     */

    public function getController($controllerAlias = '')
    {
        // возвращаем контролер
        $alias = 'default';

        if (empty($controllerAlias)) {
            if (isset($this->partsUri[0])) {
                if ($this->partsUri[0] !== 'index') { // если запросили index.html например
                    $alias = $this->partsUri[0];
                }
            }
        } else {
            $alias = $controllerAlias;
        }

        $this->log($alias);

        if (isset($this->getConfigPath()['controllers'])) {
            $path = $this->getConfigPath()['controllers'];
        } else {
            $path = 'Controllers/'; // Иначе по умолачнию
        }

        if (isset($this->getConfigAlias()[$alias])) {
            $classPath = $this->findClassFile($path, $this->getConfigAlias()[$alias]);
        } else {
            // TODO тут вероятно прийдётся доиграть и может быть вернуть дефолтный контролер
            return null;
        }

        if (file_exists($classPath)) {
            include_once $classPath;
            $name = basename($classPath, '.php');
            return new $name($this);
        }
        return null;
    }

    /**
     * @param $var
     *
     * @return $this
     */

    public function log($var)
    {
        if ($this->getIsDebug()) {
            if (is_array($var) or is_object($var)) {
                $this->log[] = sprintf("<pre>%s</pre>", print_r($var, true));
            } else {
                $this->log[] = print_r($var, true);
            }
        }
        return $this;
    }


    /**
     * Возвращает лог
     *
     * @param bool   $isString - вернуть лог строкой иначе массивом
     * @param string $delim    - символы переноса строки
     *
     * @return array|string
     */

    public function getLog($isString = true, $delim = '<br>')
    {
        if (!$isString) {
            return $this->log;
        }

        $res = $delim;
        foreach ($this->log as $item) {
            $res .= $item . $delim;

        }
        return $res;
    }

    /**
     * Возвращает подключение к БД, она же его и создаёт если ещё не создано
     * @return bool|Mysqli|null
     * @throws Exception
     */

    public function getDB()
    {
        if (!$this->db) {
            $configDB = $this->getConfigDB();

            if (!$configDB) {
                $this->db = null;

            } else {

                $server   = $configDB['server'];
                $user     = $configDB['user'];
                $password = $configDB['password'];
                $name     = $configDB['name'];
                $this->db = new mysqli($server, $user, $password, $name);

                // проверка соединения
                if ($this->db->connect_errno) {
                    throw new Exception(sprintf("Ошибка подключения к БД: %s \n", $this->db->connect_error));
                } else {
                    $this->log('База данных открыта.');
                }

                // установим кодировку подключения к БД
                $this->db->set_charset('utf8');
            }
        }
        return $this->db;
    }

    /**
     * @param string $NameTable - Имя класса таблицы
     *
     * @return null|object
     */
    public function getTable($NameTable = '')
    {
        // возвращаем объект таблицы

        if (isset($this->getConfigPath()['models'])) {
            $path = $this->getConfigPath()['models'];
        } else {
            $path = 'Models/'; // Иначе по умолачнию
        }

        $classPath = $this->findClassFile($path, $NameTable . 'Table.php');
        $this->log($classPath);
        if (file_exists($classPath)) {
            include_once $classPath;
            $name = basename($classPath, '.php');
            return new $name($this);
        }
        return null;
    }

    /**
     * определяем метод запроса/передачи данных
     * @return bool
     */
    public function isGet()
    {
        return ($_SERVER['REQUEST_METHOD'] == 'GET');
    }

    public function isPost()
    {
        return ($_SERVER['REQUEST_METHOD'] == 'POST');
    }

    public function isHead()
    {
        return ($_SERVER['REQUEST_METHOD'] == 'HEAD');
    }

    public function isPut()
    {
        return ($_SERVER['REQUEST_METHOD'] == 'PUT');
    }


    /**
     * Добавить в ответ
     *
     * @param $v
     *
     * @return $this
     */
    public function addResponse($v)
    {
        $this->response .= $v;
        return $this;
    }

    /**
     * получить ответ
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * установить ответ
     *
     * @param $v
     */
    public function setResponse($v)
    {
        $this->response = $v;
    }

    /**
     * Генерация html-ссылки по алиасу маршрута
     *
     * @param Controller $sender          // вызывающий контролер
     * @param            $controllerAlias // Имя контролера из которого маршрут
     * @param            $routeName       // Алиас маршрута
     * @param array      $params          // Значения переменных для ссылки
     *
     * @return mixed
     * @throws Exception
     */

    public function getURL(Controller $sender, $controllerAlias, $routeName, $params = [])
    {
        if (empty($routeName)) {
            throw new \Exception('Не указано имя маршрута');
        }
        /** @var Controller $controller */
        $controller = $sender;

        if (!empty($controllerAlias)) {
            $controller = $this->getController($controllerAlias);
            if (!$controller) {
                throw new \Exception('Не существующий алиас контролера - ' . $controllerAlias);
            }
        }

        if (!isset($controller->getRoutes()[$routeName])) {
            throw new \Exception('Не существующее имя маршрута - ' . $routeName);
        }

        $v     = str_replace('\\', ' / ', trim($controller->getRoutes()[$routeName]));
        $parts = preg_split("/(\/|-|_|\.)/", strtolower($v), -1, PREG_SPLIT_NO_EMPTY);
        $l     = 0;
        while ($l < count($parts)) {
            // проверяем наличие переменных
            $key = strtolower(trim(str_replace(['{', '}'], '', $parts[$l])));
            if ($key != $parts[$l]) {
                if ((!isset($params[$key])) || (is_null($params[$key]))) {
                    throw new \Exception('Не передан параметр - ' . $key);
                }

                $v = str_replace('{' . $key . '}', $params[$key], $v);
                unset($params[$key]);
            }
            $l++;
        }
        // тут потом наверное надо добавить оставшиеся переменные как get параметры, но потом

        return '/' . $v;
    }

    /**
     * метод проверяет наличие ключа в сесси
     *
     * @param $keyName
     *
     * @return bool
     */

    public function isSessoinHashKey($keyName)
    {
        return (isset($_SESSION[$keyName]));
    }

    /**
     * Возращаяет ключ из сессии если есть или Null если такого ключа нет
     *
     * @param $keyName
     *
     * @return null
     */

    public function getSessionKeyValue($keyName)
    {
        if ($this->isSessoinHashKey($keyName)) {
            return $_SESSION[$keyName];
        } else {
            return null;
        }
    }

    /**
     * Установливает в сесии ключу значение
     *
     * @param        $keyName
     * @param string $keyValue
     */

    public function setSessionKeyValue($keyName, $keyValue = null)
    {
        $_SESSION[$keyName] = $keyValue;
    }

    /**
     * функция редиректа
     *
     * @param string $uri
     */

    public function riderect($uri = '/')
    {
        header('Location:' . $uri);
        exit;
    }


    /**
     * функция обходит каталог
     *
     * @param $dir
     *
     * @return array
     */

    public function dirToArray($dir)
    {
        $result = array();
        $cdir   = scandir($dir);
        foreach ($cdir as $key => $value) {
            if (!in_array($value, array(".", ".."))) {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                    $result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
                } else {
                    $result[] = $value;
                }
            }
        }

        return $result;

    }

    /**
     * Возвращает модуль класса по имени класса без учёта регистров и т.д.
     *
     * @param $path
     * @param $className
     *
     * @return string
     */
    public function findClassFile($path, $className)
    {

        $result    = '';
        $className = strtolower($className);

        foreach (scandir($path) as $key => $value) {

            if (!in_array($value, array(".", ".."))) {

                if (!is_dir($path . DIRECTORY_SEPARATOR . $value)) {

                    if ($className == strtolower($value)) {
                        return $path . $value;
                    }
                }
            }
        }

        return $result;
    }

}