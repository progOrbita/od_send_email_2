<?php
class ControlMails
{
    private static $table = _DB_PREFIX_ . "od_send_email_control";

    /**
     * install table
     * 
     * @return bool
     */
    public static function install(): bool
    {
        $string = "CREATE TABLE IF NOT EXISTS " . self::$table . " ( `id_od_send_email_table` INT NOT NULL AUTO_INCREMENT , `id_user` INT NOT NULL , `is_customer` INT(1) NOT NULL, date_send DATETIME, PRIMARY KEY (`id_od_send_email_table`))";
        return Db::getInstance()->execute($string);
    }

    /**
     * uninstall table on database
     * 
     * @return bool
     */
    public static function uninstall(): bool
    {
        $string = "DROP TABLE IF EXISTS `" . self::$table . "`";
        return Db::getInstance()->execute($string);
    }

    /**
     * insert row 
     * 
     * @param int id
     * @param bool is_customer
     * 
     * @return bool
     */
    public static function insert(int $id, bool $is_customer): bool
    {
        $string = "INSERT INTO `" . self::$table . "` (`id_user`, `is_customer`,`date_send`) VALUES (" . $id . ", " . (int) $is_customer . ",'" . date('Y-m-d H:i:s') . "')";
        return Db::getInstance()->execute($string);
    }

    /**
     * delete row
     * 
     * @param int id
     * 
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $string = "DELETE FROM `" . self::$table . "` WHERE id_od_send_email_table=" . $id;
        return Db::getInstance()->execute($string);
    }

    /**
     * get table´s data
     * @param string $orderBy 
     * @param string $orderWay 
     * @param array $where
     * 
     * @return array|false
     */
    public static function select($orderBy = "id_od_send_email_table", $orderWay = "ASC", $where = [])
    {
        $whereQuery = '';
        if (!empty($where)) {
            $whereQuery = ' WHERE ' . implode(' AND ', $where);
        }

        $stquery = ControlMails::buildSelectQuery("employee", "pe", 0);
        $ndquery = ControlMails::buildSelectQuery("customer", "pc", 1);
        $query = 'SELECT a.* FROM(' . $stquery . ' UNION ' . $ndquery . ') as a ' . $whereQuery . ' ORDER BY ' . $orderBy . " " . $orderWay;
        return Db::getInstance()->executeS($query);
    }

    /**
     * update row
     * 
     * @return bool 
     */
    public static function update(): bool
    {
        return true;
    }

    /**
     * function to build select querys
     * @param string $table
     * @param string $as
     * @param int $isCustomer
     * 
     * @return string
     */
    private static function buildSelectQuery(string $table, string $as, int $isCustomer)
    {
        return "(SELECT osec.id, CONCAT(if(" . $as . ".firstname<=>null,'No encontrado'," . $as . ".firstname),' ',if(" . $as . ".lastname<=>null,' '," . $as . ".lastname)) AS firstname, osec.is_customer, osec.date_send 
        FROM od_send_email_control AS osec 
        LEFT JOIN " . _DB_PREFIX_ . $table . " AS " . $as . " on " . $as . ".id_" . $table . "=osec.id_user 
        WHERE osec.is_customer=" . $isCustomer . ")";
    }
}
