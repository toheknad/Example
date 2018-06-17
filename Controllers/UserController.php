<?php

/**
 * Created by PhpStorm.
 * User: User
 * Date: 24.02.2018
 * Time: 12:28
 */

Use Main\Controller;

class UserController extends Controller
{

    public function configRouters()
    {
        // именованные марщруты, которые обрабатываются в контролере
        // имеет значение, порядок добавления
        // функция находит первый маршрут удовлетворяющий условию
        return [
            'register' => 'user/register',      // зарегистрировать нового пользователя
            'activate' => 'user/activate',      // активировать нового пользователя через email
            'profile'  => 'user/profile/{id}',  // публичный профиль пользователя
            'edit'     => 'user/edit/{id}',     // редактировать пользователя
            'block'    => 'user/block/{id}',    // блокировать пользователя
        ];

    }

    public function Action($params = [])
    {

        $this->avd->addResponse(' Контролер управление пользователем!</br>');
        $params = $this->routeCheck();
        switch ($params['_route']) {
            case 'register': {
                $this->avd->addResponse(' Регистрация пользователя.</br>');
                break;
            }
            case 'activate': {
                $this->avd->addResponse(' Активация пользователя.</br>');
                break;
            }
            case 'profile': {
                $this->avd->addResponse(' Профиль пользователя - ' . $params['id'] . '.</br>');
                break;
            }
            case 'edit': {
                $this->avd->addResponse(' Редактирование пользователя - ' . $params['id'] . '.</br>');
                break;
            }
            case 'block': {
                $this->avd->addResponse(' Блокировка пользователя - ' . $params['id'] . '.</br>');
                break;
            }
        }

        $this->avd->addResponse(' Пример генерации локального  маршрута.</br>');
        $this->avd->addResponse($this->getURL('block', ['id' => 123]));
        $this->avd->addResponse(' Пример генерации маршрута из другого контролера.</br>');
        $this->avd->addResponse($this->getURLFromController('blog', 'edit', ['id' => 123]));

    }
}