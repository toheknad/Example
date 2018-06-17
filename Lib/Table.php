<?php
/**
 * класс табличек БД
 */

namespace Main;

Use Main\Avd;

abstract class Table
{
    const  ID = 'id'; // перекрывать в наследниках
    const  NAME = 'tableName'; // перекрывать в наследниках

    protected $avd = null;
    protected $db = null;
    protected $fieldID = '';
    protected $tableName = '';

    public function __construct(Avd $avd)
    {
        $this->avd = $avd;
        $this->db  = $avd->getDB();

        // перенесём из констант в переменные объекта
        $c               = get_called_class();
        $this->tableName = $c::NAME;
        $this->fieldID   = $c::ID;

    }

    /**
     * Дублируем лог, короче писать, можно потом чего то добавить, перекрыть
     *
     * @param $var
     */
    public function log($var)
    {
        $this->avd->log($var);
    }

    /**
     * Выбор одной записи из табицы БД по ИД
     *
     * @param        $id
     * @param string $fields // перечень полей через запятую, если нужно не все
     *
     * @return array|bool
     */
    public function findOneById($id, $fields = '*')
    {
        $tableName = $this->tableName;
        $fieldID   = $this->fieldID;

        $sql    = "SELECT $fields FROM $tableName WHERE $fieldID=$id";
        $db_res = $this->db->query($sql);
        if (($db_res) and ($db_res->num_rows == 1)) {
            $r = $db_res->fetch_assoc();
            $db_res->close();
            return $r; // возвращаем запись
        } else return false; // если записей нет, или больше одной то возвращаем пустой массив
    }

    /**
     * Возвращает из таблицы записи по условию из params
     *
     * @param array $params // занчения по именам полей для выржаения where, выбираются по and*
     *                      // ключи params
     *                      // where - условия поле=>значение или поле=>[список значений через запятую
     *                      // выражения по полям объеденяются через AND
     *                      // where_str - условие просто строкой sql как есть например ' or ( id>10 and id<20)
     *                      // fields - список полей поле=>алиас (новое название поля) или поле=>'' будет называтся
     *                      // как есть
     *                      // order - поля сортировки поле=>Порядок сортировки (ASC,DESC,'' - по умолчанию ASC)
     *                      // key - имя поля для асоциативного массива выборки, только уникальные поля, иначе выборка
     *                      // схлопнется до последниих уникальных значений по умолчанию ID-таблицы
     *                      // offset - смещение от начала выборки, с какой записи выбирать
     *                      // limit  - максимальное кол-во записей которые надо выбрать
     *
     * @return array
     */
    public function findBy($params = [])
    {
        $tableName = $this->tableName;
        $fieldID   = $this->fieldID;

        // выражение where
        $sqlWhere = '';
        $dlm      = '';
        if (isset($params['where'])) {
            foreach ($params['where'] as $k => $v) {
                if (is_array($v)) { // используем выражение IN
                    if (!empty($v)) {
                        $sqlIN    = "'" . implode("','", $v) . "'";
                        $sqlWhere .= " $dlm ( $k in ($sqlIN) ) ";
                    }
                } else {
                    $sqlWhere .= " $dlm ( $k='$v' ) ";
                }
                $dlm = 'and';
            }
        }
        // если возьмём в скобки то хужее не будет
        if (!empty($sqlWhere)) {
            $sqlWhere = " WHERE ( $sqlWhere ) ";
        }

        // выражение where_str
        if (isset($params['where_str'])) {
            if (empty($sqlWhere)) {
                $sqlWhere = " WHERE ";
            }
            $sqlWhere .= $params['where_str'];
        }

        // выражение fields
        $sqlFields = '';
        $dlm       = '';
        if (isset($params['fields'])) {
            foreach ($params['fields'] as $k => $v) {
                $sqlFields .= $dlm . " $k  $v";
                $dlm       = ',';
            }
        } else {
            $sqlFields = '*'; // все поля
        }

        // выражение key
        if (isset($params['key'])) {
            $fieldID = $params['key'];
        };

        // выражение order
        $sqlOrder = '';
        $dlm      = '';
        if (isset($params['order'])) {
            $dlm = ' ORDER BY ';
            foreach ($params['order'] as $k => $v) {
                $sqlOrder .= $dlm . " $k $v";
                $dlm      = ',';
            }
        } else {
            $sqlOrder = ' ORDER BY ' . $fieldID; // по умлочанию по ID или key
        }

        // выражение limit, offset
        $limit = '';
        if (isset($params['limit'])) {
            $limit = "LIMIT {$params['limit']}";
            if (isset($params['offset'])) {
                $limit .= ", {$params['offset']}";
            }
        }

        $sql = "SELECT $sqlFields FROM $tableName $sqlWhere  $sqlOrder $limit";
        $this->log($sql);
        $db_res = $this->db->query($sql);
        $r      = [];
        if ($db_res) {
            while ($item = $db_res->fetch_assoc()) {
                $r[$item[$fieldID]] = $item;
            }
            $db_res->close();
        }
        return $r;
    }

    /**
     * Метод выполняет поизволльный SQL в таблице, по сути в БД
     * потому что в самом SQL могут быть указаны любые таблицы
     *
     * @param $sql
     *
     * @return bool
     */
    public function execSql($sql)
    {
        $db_res = $this->db->query($sql);
        if (!$db_res) {
            $this->log(" $this->tableName execSql Error - $sql ");
            return false;
        }
        return true;
    }

    /**
     * Метод вставляет запись в таблицу
     *
     * @param array $fieldsValue // массив поле => значение
     * @param bool  $isClear     // очищать данные
     *
     * @return bool|mixed
     */
    public function insert($fieldsValue = [], $isClear = true)
    {
        $fields = '';
        $values = '';
        $dlm    = '';
        foreach ($fieldsValue as $f => $v) {
            $fields .= $dlm . $f;
            if ($isClear) {
                $v = htmlspecialchars($v);
            }
            $values .= $dlm . "'$v'";
            $dlm    = ', ';
        }

        $sql    = "INSERT INTO {$this->tableName} ({$fields}) VALUES ({$values})";
        $db_res = $this->db->query($sql);
        if (!$db_res) {
            $this->log(" $this->tableName insert Error - $sql ");
            $db_res->close();
            return false;
        }

        $db_res->close();
        return $this->db->insert_id;;
    }

    /**
     * метод обновляет запись по ID
     *
     * @param       $id
     * @param array $fieldsValue
     * @param bool  $isClear // очищать данные
     *
     * @return bool
     */
    public function updateById($id, $fieldsValue = [], $isClear = true)
    {
        $fields = '';
        $dlm    = ' SET ';
        foreach ($fieldsValue as $f => $v) {
            if ($isClear) {
                $v = htmlspecialchars($v);
            }
            $fields .= $dlm . "{$f}='{$v}'";
            $dlm    = ', ';
        }

        if (empty($fields)) {
            $this->log(" $this->tableName updateById empty query - $fields ");
            return false;
        }

        $sql    = "UPDATE {$this->tableName} {$fields} WHERE {$this->fieldID}={$id}";
        $db_res = $this->db->query($sql);
        if (!$db_res) {
            $this->log(" $this->tableName updateById Error - $sql ");
            $db_res->close();
            return false;
        }

        $db_res->close();
        return true;
    }
}