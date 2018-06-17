<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 18.03.2018
 * Time: 9:55
 */

//$s = '{{ Extend rooms_tmpl 2 }}  // василий пупкин';
//
//preg_match('|{{[^extend]*?extend(.*?)}}|sei', $s, $matches);
//echo "<pre>";
//print_r($matches);
//
//$s = "<?";
//print_r(explode('<?', $s, 2));


//        $this->tmplOut[] = '<pre>';
//       // $this->tmplOut[] = print_r($this->stack_blocks, true);
//        $this->tmplOut[] = print_r($this->stack_blocks_tmpl, true);
//        $this->tmplOut[] = '</pre>';

//   $this->stack_blocks      = []; // стэк блоков
//   $this->blocks_tmpl       = []; // содержимое блоков
//   $this->stack_blocks_tmpl = []; // стэк содержимого перед переключением блока


//$key = [
//    'first' => 'первый',
//    'last'  => 'последний',
//];
//

class foo
{

    public $kol = 0;

    public function mkey($name = '')
    {
        return $name . '12334556666';
    }

    public function __set($name, $value)
    {
        // TODO: Implement __set() method.
        echo $name . ' ' . $value;
    }

    public function setKol($value)
    {
        echo 'kol- ' . $value;

    }

}

$f = new foo();

//echo " first = ${key['last']}";
//echo " first = {$f->mkey('qwer')} ";

echo method_exists($f,'mKey');



//$i = 0;
//$r = [
//    '0' => 2,
//    '1' => 2, // extend
//    '2' => 0,
//    '3' => 1, // include
//    '4' => 1,
//];
//
//$r1 = [
//    '10' => -5,
//    '11' => -6,
//    '12' => -7,
//];
//
//echo PHP_EOL;
//
//do {
//    $fl = 0;
//
//    $k = 0;
//    foreach ($r as $k => $item) {
//        echo $k . ' => ' . $item . PHP_EOL;
//
//        if ($item > 0) {
//            $fl = $item;
//            break;
//        }
//    }
//
//    if ($fl == 2) {
//        echo '--Extend--' . PHP_EOL;
//        $r[$k] = 0;
//        $r     = array_merge($r1, $r);
//    } else if ($fl == 1) {
//        echo '--Incelude--' . PHP_EOL;
//        $r[$k] = 0;
//        $r     = array_merge(
//            array_slice($r, 0, $k),
//            $r1,
//            array_slice($r, $k)
//        );
//    }
//
//
//} while ($fl > 0);
//
//
echo  PHP_EOL;
echo preg_replace('/(\d)(?=(\d\d\d)+([^\d]|$))/','${1} ', '123456');