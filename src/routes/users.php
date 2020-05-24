<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app = new \Slim\App;

/*
 ** Endpoint for getting all user informations.
 */

$app->get('/users', function (Request $request, Response $response) {

    $db = new Db();

    try {

        $db = $db->connect();

        $users = $db->query("SELECT * FROM users")->fetchAll(PDO::FETCH_OBJ);

        return $response->withStatus(200)->withHeader("Content-Type", "application/json")->withJson(
            array(
                "data" => array(
                    "message" => "Operation completed!",
                    "success" => true,
                    "data" => $users
                ),
            )
        );

    } catch (PDOException $e) {
        return $response->withStatus(400)->withHeader("Content-Type", "application/json")->withJson(
            array(
                "error" => array(
                    "message" => $e->getMessage(),
                    "success" => $e->getCode()
                ),
            )
        );
    }

});

/*
 ** Endpoint for register user.
 */

$app->post('/users/register', function (Request $request, Response $response) {

    $username = $request->getParam('username');
    $email = $request->getParam('email');
    $password = $request->getParam('password');
    $fullname = $request->getParam('fullname');

    $db = new Db();

    try {

        $db = $db->connect();

        $query = $db->prepare("INSERT INTO users(username,email,password,fullname) VALUES(?,?,?,?)");
        $query->execute([$username, $email, $password, $fullname]);

        return $response->withStatus(200)->withHeader("Content-Type", "application/json")->withJson(
            array(
                "data" => array(
                    "message" => "Registiration completed!",
                    "success" => true
                ),
            )
        );

    } catch (PDOException $e) {
        return $response->withStatus(400)->withHeader("Content-Type", "application/json")->withJson(
            array(
                "error" => array(
                    "message" => $e->getMessage(),
                    "success" => $e->getCode()
                ),
            )
        );
    }

});

/*
 ** Endpoint for login auth.
 */

$app->post('/users/login', function (Request $request, Response $response) {

    $email = $request->getParam('email');
    $password = $request->getParam('password');

    $db = new Db();

    try {

        $db = $db->connect();

        $query = $db->prepare("SELECT * FROM `users` WHERE email = :email AND password = :password");
        $query->execute([':email' => $email, ':password' => $password]);
        $rowCount = $query->rowCount();

        if ($rowCount > 0) {

            $fetchUserData = $query->fetch(PDO::FETCH_OBJ);

            return $response->withStatus(200)->withHeader("Content-Type", "application/json")->withJson(
                array(
                    "data" => array(
                        "message" => "Operation completed!",
                        "success" => true,
                        "data" => $fetchUserData
                    ),
                )
            );

        } else {
            
            return $response->withStatus(200)->withHeader("Content-Type", "application/json")->withJson(
                array(
                    "data" => array(
                        "message" => "Username and password does not match!!",
                        "success" => false,
                        "data" => $fetchUserData
                    ),
                )
            );
        }

    } catch (PDOException $e) {
        return $response->withStatus(400)->withHeader("Content-Type", "application/json")->withJson(
            array(
                "error" => array(
                    "message" => $e->getMessage(),
                    "success" => $e->getCode()
                ),
            )
        );
    }

});
