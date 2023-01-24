<?php
class ControlMails
{
    private $table = "od_send_email_control";

    /**
     * install table
     * 
     * @return bool
     */

    public static function install(): bool
    {
        $string = "CREATE TABLE IF NOT EXISTS " . self::$table . " ( `id` INT NOT NULL AUTO_INCREMENT , `id_user` INT NOT NULL , `is_customer` INT(1) NOT NULL, date_send DATE, PRIMARY KEY (`id`))";
        return Db::getInstance()->execute($string);
    }

}
