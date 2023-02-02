<?php
class ControlMails
{
    private static $table = "od_send_email_control";

    /**
     * install table
     * 
     * @return bool
     */
    public static function install(): bool
    {
        $string = "CREATE TABLE IF NOT EXISTS " . self::$table . " ( `id` INT NOT NULL AUTO_INCREMENT , `id_user` INT NOT NULL , `is_customer` INT(1) NOT NULL, date_send DATETIME, PRIMARY KEY (`id`))";
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
        $string = "DELETE FROM `" . self::$table . "` WHERE id=" . $id;
        return Db::getInstance()->execute($string);
    }

    /**
     * get table´s data
     * @param string $orderBy 
     * @param string $orderWay 
     * 
     * @return array|false
     */
    public static function select($orderBy="id",$orderWay="ASC")
    {
        $query = "(SELECT osec.id, if(pe.firstname<=>null,'No encontrado',pe.firstname) as firstname, osec.is_customer, osec.date_send FROM od_send_email_control AS osec LEFT JOIN ps_employee as pe on pe.id_employee=osec.id_user WHERE osec.is_customer=0) UNION (SELECT osec.id, if(pc.firstname<=>null,'No encontrado',pc.firstname)as firstname, osec.is_customer, osec.date_send FROM od_send_email_control AS osec LEFT JOIN ps_customer as pc ON pc.id_customer=osec.id_user WHERE osec.is_customer=1) order by ".$orderBy." ".$orderWay;
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
}
