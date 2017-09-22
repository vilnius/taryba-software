<?php
include_once('./lib/config.php');
include_once('./helper/MysqliDb.php');
class Council {
    public function getCurrentQuestion() {
        $db = new MysqliDb (Array (
                'host' => DB_host,
                'username' => DB_user, 
                'password' => DB_password,
                'db'=>DB_name,
                'charset' => 'utf8'));
        $db->where ("ar_rodomas", 1);
        $message = $db->getOne ("tarybosPranesimai");
        return $message;
    }
}
?>