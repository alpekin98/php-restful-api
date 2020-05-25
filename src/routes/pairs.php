<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/*
 ** Endpoint for pair user.
 */

$app->post('/pairs/create', function (Request $request, Response $response) {

    $senderID = $request->getParam('sender_id');
    $receiverToken = $request->getParam('receiver_token');

    $db = new Db();

    try {

        $db = $db->connect();

        $query = $db->prepare("INSERT INTO pairs(sender_id,receiver_token) VALUES(?,?)");
        $query->execute([$senderID, $receiverToken]);

        return $response->withStatus(200)->withHeader("Content-Type", "application/json")->withJson(
            array(
                "data" => array(
                    "message" => "Created!",
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
 ** Endpoint for check auth.
 */

$app->post('/pairs/check', function (Request $request, Response $response) {

    $authToken = $request->getParam('authtoken');

    $db = new Db();

    try {

        $db = $db->connect();

        $query = $db->prepare("SELECT * FROM `pairs` WHERE receiver_token = :authToken");
        $query->execute([':authToken' => $authToken]);
        $rowCount = $query->rowCount();

        if ($rowCount > 0) {

            $fetchPairData = $query->fetch(PDO::FETCH_OBJ);

            $query = $db->prepare("SELECT * FROM `users` WHERE id = :senderID");
            $query->execute([':senderID' => $fetchPairData->sender_id]);
            
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
                        "message" => "No pair requests!",
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

/*
 ** Endpoint for update.
 */

$app->post('/pairs/update', function (Request $request, Response $response) {

    $sender_id = $request->getParam('sender_id');
    $my_id = $request->getParam('my_id');
    $approve = $request->getParam('approve');

    $db = new Db();

    try {

        $db = $db->connect();

        $query = $db->prepare("UPDATE `pairs` SET `status` = :approve  WHERE sender_id = :sender_id");
        $query->execute([':sender_id' => $sender_id, ':approve' => $approve]);

        if($approve == true) {

            $query = $db->prepare("UPDATE `users` SET `pair_id` = :sender_id WHERE id = :my_id");
            $query->execute([':my_id' => $my_id, ':sender_id' => $sender_id]);

            $query = $db->prepare("UPDATE `users` SET `pair_id` = :my_id WHERE id = :sender_id");
            $query->execute([':my_id' => $my_id, ':sender_id' => $sender_id]);

        }

        return $response->withStatus(200)->withHeader("Content-Type", "application/json")->withJson(
            array(
                "data" => array(
                    "message" => "Pair operation completed!",
                    "success" => true,
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
