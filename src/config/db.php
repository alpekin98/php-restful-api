<?php

class Db
{

    private $dbhost = DB_HOST;
    private $dbname = DB_NAME;
    private $dbuser = DB_USER;
    private $dbpass = DB_PASS;

    public function connect() {

        $connection = new PDO("mysql:host=$this->dbhost;dbname=$this->dbname", $this->dbuser, $this->dbpass);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $connection;

    }

}
