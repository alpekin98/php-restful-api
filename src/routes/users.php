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

        return $response
            ->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->withJson($users);

    } catch (PDOException $e) {
        return $response->withJson(
            array(
                "error" => array(
                    "message" => $e->getMessage(),
                    "code" => $e->getCode(),
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

    $db = new Db();

    try {

        $db = $db->connect();

        $query = $db->prepare("INSERT INTO users(username,email,password) VALUES(?,?,?)");
        $query->execute([$username, $email, $password]);

        return $response
            ->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->withJson("Operation completed!");

    } catch (PDOException $e) {
        return $response->withJson(
            array(
                "error" => array(
                    "message" => $e->getMessage(),
                    "code" => $e->getCode(),
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

        if($rowCount > 0) {
            
            $fetchUserData = $query->fetch(PDO::FETCH_OBJ);

            return $response
            ->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->withJson($fetchUserData);

        } else {
            throw new PDOException("Username or password wrong!", 400);
        }

    } catch (PDOException $e) {
        return $response->withJson(
            array(
                "error" => array(
                    "message" => $e->getMessage(),
                    "code" => $e->getCode(),
                ),
            )
        );
    }

});
