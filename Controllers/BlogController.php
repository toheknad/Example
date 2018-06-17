<?php

/**
 * контролер блога, чисто на пробу
 */

Use Main\Controller;
Use Main\Tmpl;

class BlogController extends Controller
{

    public function configRouters()
    {
        // именованные марщруты, которые обрабатываются в контролере
        // имеет значение, порядок добавления
        // функция находит первый маршрут удовлетворяющий условию
        return [
            'add'         => 'Blog/add',                            // добавить статью в блог
            'show'        => 'Blog/{id}',                           // показать статью
            'edit'        => 'Blog/{id}/edit',                      // редактировать статью блога
            'del'         => 'Blog/{id}/del',                       // удалить статью блога
            'addComment'  => 'Blog/{id}/addComment',                // добавить комментарий к статье
            'delComment'  => 'Blog/{id}/delComment/{idcomment}',    // удалить комментарий к статье
            'editComment' => 'Blog/{id}/editComment/{idcomment}',   // редактировать комментарий к статье
            'hideComment' => 'Blog/{id}/hideComment/{idcomment}',   // скрыть комментарий к статье
            'showComment' => 'Blog-{id}/showComment-{idcomment}',   // показать комментарий к статье
        ];
    }

    public function Action($params = [])
    {

        $this->log($this->routeCheck());

        $this->avd->log(' Контролер Блог !</br>');
        $this->avd->log($this->getURL('showComment', [
            'id'        => 123,
            'idcomment' => 1678,
        ])
        );

        /** @var Tmpl $tmpl */
        $tmpl = new Tmpl($this->avd);
        $this->avd->addResponse($tmpl->parse('index', []));
        /** @var \Main\Table $tUsers */
        $tUsers = $this->avd->getTable('Users');
        // Пример запроса для отладки любые или все ключи в findBy можно пропустить

        $res = $tUsers->findBy([
            // список нужных полей и как их назвать в массиве
            'fields'    => [
                'user_id'    => 'id',       // Поле оставляем как есть ИД-шник лучшее ни когда не переименовывать
                'user_login' => 'login',    // Поле перемиеновываем
                'user_email' => '',         // Поле оставляем как есть
            ],
            // по какому полю именовать ключи в массиве
            'key'       => 'id',            // имено по новому имени
            // как сортировать
            'order'     => [
                'login'   => 'ASC',
                'user_id' => '',
            ],
            // простые выражение через and
            //'where'     => [                // тут дальше бред, но чисто для проверки и примера
            //    'user_id' => [7, 8, 9, 10],
            //],
            // чего то хитрое если не and
            //'where_str' => " or (user_login='iprus')",
            'limit'=>2,
            'offset'=>5,
        ]);
        foreach ($res as $k => $item) {
            $this->avd->addResponse($k . ' ' . print_r($item, true))
                ->addResponse('<br>');
        }
    }

}