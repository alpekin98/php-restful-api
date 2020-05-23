<?php

class Db
{

    private $dbhost = "localhost";
    private $dbname = "mobile_app";
    private $dbuser = "root";
    private $dbpass = "12345678";

    public function connect()
    {

        $connection = new PDO("mysql:host=$this->dbhost;dbname=$this->dbname", $this->dbuser, $this->dbpass);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $connection;

    }

}
