<?php
/**
 * класс шаблонизатора
 */

namespace Main;


class Tmpl
{

    protected $avd = null;
    protected $path = 'Templates/';
    protected $cnt = '';
    protected $tmplOut = [];
    protected $stack_blocks = []; // стэк блоков
    protected $blocks_tmpl = []; //  содержимое блоков
    protected $stack_blocks_tmpl = []; // стэк содержимого перед переключением блока

    protected $key = []; // массив пользоательских ключей
    protected $cycleCounter = 0; // счётчик вложений, рекурсий для контроля ошибок

    public function __construct(Avd $avd)
    {
        $this->avd = $avd;

        if (isset($this->avd->getConfigPath()['templates']))
            $this->path = $this->avd->getConfigPath()['templates'];
    }


    public function parse($tmplName = '', $params = [], $key = [])
    {

        $fn = $this->avd->getConfigPath()['cache'] . $tmplName . '.php';
        if ($this->avd->getIsDebug() || !file_exists($fn)) {
            $fn = $this->compile($this->load($tmplName), $tmplName);
        }
        extract($params);
        $this->key               = $key;
        $this->tmplOut           = []; // выходной файл по строкам
        $this->stack_blocks      = []; // стэк блоков
        $this->blocks_tmpl       = []; // содержимое блоков
        $this->stack_blocks_tmpl = []; // стэк содержимого перед переключением блока

        if ((include $fn) !== 1) { // если подключить скомпилированный шабон не удалось, то генерим исключение
            throw new \Exception('Указанная страница  не найдена!');
        }

        $str_out = '';
        foreach ($this->tmplOut as $v) {
            $str_out .= ' ' . trim($v);
        }
        return $str_out;
    }


    private function load($tmplName = '')
    {
        // загрузка шаблона в массив-цепочку
        $res                = [];  // результат загрузки
        $code               = [];  // код первый уровень
        $blocks             = [];  // блоки
        $this->cycleCounter = 0;


        $tmplName = $this->path . $tmplName . '.tmpl';
        if (!file_exists($tmplName)) {
            return $res;
        }

        // загружаем шаблон в массив строк
        $tmpl = file($tmplName);

        // обработка Include и Extend
        // наращиваем в глубину и ширину пока всё не поподключаем и не унаследуем
        do {
            $fl = 0;
            foreach ($tmpl as $k => $v) {

                // команда extend наследование шаблона
                preg_match('|{{[^extend]*?extend(.*?)}}|sei', $v, $matches);
                if (isset($matches[1])) {
                    // такого быть не должно, но если будет несколько extend то они все будут втыкаться
                    // каждый раз в самый вверх
                    // можно накопить проверку но и так будет видно
                    // TODO надо проверку на зацикливание, чтобы шаблон не ссылася сам на себя.
                    $fl = 1; // extend
                    break;
                }

                // команда include подключение шаблона
                preg_match('|{{[^include]*?include(.*?)}}|sei', $v, $matches);
                if (isset($matches[1])) {
                    // добавляем по месту
                    $fl = 2; // include
                    break;
                }
            }


            if ($fl > 0) {
                $tmplNameAdd = $this->path . trim($matches[1]) . '.tmpl';

                if (!file_exists($tmplNameAdd)) {
                    return [];
                }
                $tmpl[$k] = '';

                switch ($fl) {
                    case 1: // extend
                        $tmpl = array_merge(file($tmplNameAdd), $tmpl);
                        break;

                    case 2: // include
                        $tmpl = array_merge(
                            array_slice($tmpl, 0, $k),
                            file($tmplNameAdd),
                            array_slice($tmpl, $k));
                        break;
                }
            }

            $this->cycleCounter++; // увеличиваем счеткич циклов для защиты от зацикливания
            if ($this->cycleCounter > 100) {
                $this->avd->log('Зацикливание в шаблоне extend or include')
                    ->log('</br>');
                throw new \Exception('Зацикливание в шаблоне');
            }
        } while ($fl > 0);


        // все шаблоны подключены и унаследованы теперь парсим свои блоки
        $stackBlock = []; // стэк блоков
        foreach ($tmpl as $k => $v) {

            do {
                // проверяем на блоки
                preg_match('|{{[^endblock]*?endblock(.*?)}}|sei', $v, $matches); // конец
                if (isset($matches[1])) {
                    array_shift($stackBlock);
                    $v = '';
                    break;
                }

                preg_match('|{{[^block]*?block(.*?)}}|sei', $v, $matches); // начало
                if (isset($matches[1])) {
                    $n = strtolower(trim($matches[1]));

                    if (!array_key_exists($n, $blocks)) {
                        $blocks[$n] = [];

                        if (empty($stackBlock)) {
                            $code[$n] = [];
                        } else {
                            $blocks[$stackBlock[0]][0][$n] = [];
                        }
                    }

                    array_unshift($stackBlock, $n);
                    array_unshift($blocks[$n], []); // parent проталкиваем в стек, есть нету не важно
                    $v = '';
                    break;
                }

                // проверка на однострочный неразрывный блок и заодно на parent
                preg_match('|{{(.*?)}}|sei', $v, $matches);
                if (isset($matches[1])) {
                    $n = strtolower(trim($matches[1]));

                    if ($n == 'parent') {
                        // наследуем родителя
                        if (empty($stackBlock)) {
                            // чтобы наследовать родителя должна быть фамилия
                            break;
                        }

                        foreach ($blocks[$stackBlock[0]][1] as $v) {
                            $blocks[$stackBlock[0]][0][] = $v;
                        }

                        $v = '';
                        break;
                    }

                    if (!array_key_exists($n, $blocks)) {
                        $blocks[$n] = [];

                        if (empty($stackBlock)) {
                            $code[$n] = [];
                        } else {
                            $blocks[$stackBlock[0]][0][$n] = [];
                        }
                    }

                    array_unshift($blocks[$n], []); // parent проталкиваем в стек, есть нету не важно
                    $v = '';
                    break;
                }

                // иначе просто строка
                $v = trim(str_replace(array("\r\n", "\r", "\n"), '', $v));

            } while (false);


            if (!empty($v)) {
                if (empty($stackBlock)) {
                    $code[] = $v;
                } else {
                    $blocks[$stackBlock[0]][0][] = $v;
                }
            }
        }

        //теперь сложим в один уровень code и blocks в res
        $res = $this->loadCode($code, $blocks);

        //$this->avd->log($res);
        return $res;
    }

    // плохая рекурсивная функция, которая любит вызывать сама себя :)
    // при ошибках в шаблонах,
    // может выполняться до бесконечности. Надо бы ограничение вложенности 100, 200 штук я думаю будет вполне
    private function loadCode($code, $blocks)
    {
        $this->cycleCounter++; // увеличиваем счеткич циклов для защиты от зацикливания
        if ($this->cycleCounter > 100) {
            $this->avd->log('Зацикливание в шаблоне в структуре блоков!')
                ->log('</br>')
                ->log($blocks);
            throw new \Exception('Зацикливание в шаблоне');
        }

        $res = [];
        foreach ($code as $k => $v) {
            if (!is_array($v)) {
                $res[] = $v; // если не массив, значит не блок
            } else {
                $res = array_merge($res, $this->loadCode($blocks[$k][0], $blocks));
            }
        }
        return $res;
    }


    private function compile($source = [], $name = '')
    {
        $this->cnt = '';
        $this->cnt .= '<?php' . PHP_EOL;
        $this->cnt .= '$this->tmplOut = [];' . PHP_EOL;
        $l         = 0;
        foreach ($source as $k => $v) {
            $fl = true;
            while ($fl) {
                $r = explode('<?', $v, 2);
                if (count($r) === 2) {
                    $this->cnt .= $r[0] . PHP_EOL;
                    $v         = $r[1];
                    $l++;
                    continue;
                }
                $r = explode('?>', $v, 2);
                if (count($r) === 2) {
                    $this->cnt .= $r[0] . PHP_EOL;
                    $v         = $r[1];
                    $l--;
                    continue;
                }
                $v = trim($v);
                if (!empty($v)) {
                    if ($l > 0) {
                        $this->cnt .= $v . PHP_EOL;
                    } else {
                        $v         = str_replace('"', '\"', $v);
                        $this->cnt .= '$this->tmplOut[]="' . $v . ' ";' . PHP_EOL;
                    }
                }
                $fl = false;

            }
        }

        $fn = $this->avd->getConfigPath()['cache'] . $name . '.php';
        file_put_contents($fn, $this->cnt);

        return $fn;
    }

    private function beginBlock($blockName)
    {
        $this->avd->log('in - ' . $blockName);
        array_unshift($this->stack_blocks, $blockName);  // запихиваем в стэк имя блока
        array_unshift($this->stack_blocks_tmpl, $this->tmplOut); // запихиваем в блок содержимое вывода
        $this->tmplOut = []; // обнуляем содержимое вывода
    }

    private function endBlock($blockName)
    {
        $stack = array_shift($this->stack_blocks); // Получаем имя блока в стеке
        if ($stack !== $blockName) {
            throw new \Exception(sprintf("Ошибка в шаблоне: вложение блоков - %s \n", $blockName));
        }
        $t             = $this->tmplOut;         // сохраняем содержимое блока
        $this->tmplOut = array_shift($this->stack_blocks_tmpl); // востанавливаем вывод
        // Добавляем содержимое блока по месту в указаный блок

        foreach ($t as $item) {
            $this->tmplOut[$blockName] .= $item;
        }
        $this->avd->log('out - ' . $blockName);
    }


    /**
     *  //обработка пользовательского массива ключей
     *
     * @param        $name
     * @param string $value
     *
     * @return mixed|string
     */
    public function key($name, $value = '')
    {
        if (isset($this->key[$name])) {
            return $this->key[$name];
        }

        return $value;
    }

}