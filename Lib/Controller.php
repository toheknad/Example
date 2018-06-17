<?php
/**
 * Класс предок контролера
 */

namespace Main;

Use Main\Avd;

abstract class Controller
{
    /** @var Avd|null */
    protected $avd = null;
    protected $routes = [];

    public function __construct(Avd $avd)
    {
        $this->avd    = $avd;
        $this->routes = $this->configRouters();
    }

    // методы перекрываем в наследниках

    /**
     * @return array
     */
    public function configRouters()
    {
        // здесь у потомка реализаиция настрайки маршрутов
        return [];
    }

    /**
     * @return string
     */
    public function Action($params = [])
    {
        // здесь у потомка реализаиця основаной обработки запроса пользователя и ответа
        return '';
    }

    // end  методы перекрываем в наследниках

    public function getRoutes($routes = [])
    {
        return $this->routes;
    }


    public function setRoutes($routes = [])
    {
        $this->routes = $routes;
        return $this;
    }

    /**
     * метод проверки запроса на соотвествиие маршрутам контролера
     *
     * @return array|bool
     */
    public function routeCheck()
    {
        // пробегаемся по переданным маршрутам и находим первый совпадающий
        $partsURI = $this->avd->getPartsURI();
        foreach ($this->routes as $k => $v) {
            // делим маршрут на части
            $v      = str_replace('\\', '/', trim(strtolower($v)));
            $parts  = preg_split("/(\/|-|_|\.)/", $v, -1, PREG_SPLIT_NO_EMPTY);
            $l      = 0;
            $params = [];
            // проверяем только те которые подходят по длине
            $fl = (count($parts) === count($partsURI));
            while (($fl) and ($l < count($parts))) {
                // забираем переменные
                $key = trim(str_replace(['{', '}'], '', $parts[$l]));
                if ($key != $parts[$l]) {
                    $params[$key] = $partsURI[$l];
                } else {
                    $fl = ($parts[$l] == $partsURI[$l]);
                }
                $l++;
            }

            // тут будет ещё проверка пользовательскими колбэками

            if ($fl) {
                return array_merge(['_route' => $k], $params);
            }
        }
        return false;
    }

    /**
     * Дублируем лог, короче писать, можно потом чего то добавить, перекрыть
     *
     * @param $var
     *
     * @return $this
     */
    public function log($var)
    {
        $this->avd->log($var);
        return $this;
    }

    /**
     * Генерация html-ссылки по алиасу маршрута из текущего контролера
     *
     * @param       $routeName // Алиас маршрута
     * @param array $params    // Значения переменных для ссылки
     *
     * @return mixed
     */
    public function getURL($routeName, $params = [])
    {
        return $this->avd->getURL($this, '', $routeName, $params);
    }


    /**
     * Генерация html-ссылки по алиасу маршрута из другого контролера
     *
     * @param       $controllerAlias // Имя контролера из которого маршрут
     * @param       $routeName       // Алиас маршрута
     * @param array $params          // Значения переменных для ссылки
     *
     * @return mixed
     */
    public function getURLFromController($controllerAlias, $routeName, $params = [])
    {
        return $this->avd->getURL($this, $controllerAlias, $routeName, $params);
    }

}