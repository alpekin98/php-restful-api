<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->add(new \Slim\Middleware\JwtAuthentication([
	// The secret key
    "secret" => SECRET,
    "secure" => false,
	"rules" => [
		new \Slim\Middleware\JwtAuthentication\RequestPathRule([
			// Degenerate access to "/users"
			"path" => "/users",
			// It allows access to "login" without a token
			"passthrough" => [
                "/users/login",
                "/users/register"
			]
		])
	]
]));

$app->add(function (Request $request, Response $response, $next) {
	$response = $next($request, $response);
	// Access-Control-Allow-Origin: <domain>, ... | *
	$response = $response->withHeader('Access-Control-Allow-Origin', '*')
		->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
		->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
	return $response;
});

$app->group("/users", function () use ($app) {

    /*
    ** Endpoint for register user.
    */
    $app->post('/register', function (Request $request, Response $response) {

        $username = $request->getParam('username');
        $email = $request->getParam('email');
        $password = $request->getParam('password');
        $fullname = $request->getParam('fullname');
        $authtoken = bin2hex(openssl_random_pseudo_bytes(4));

        $options = array("cost" => 4);
        $hashPassword = password_hash($password, PASSWORD_BCRYPT, $options);

        $db = new Db();

        try {

            $db = $db->connect();

            $emailQuery = $db->prepare("SELECT * FROM users WHERE email = ?");
            $emailQuery->execute([$email]);
            $emailRowCount = $emailQuery->rowCount();

            $usernameQuery = $db->prepare("SELECT * FROM users WHERE username = ?");
            $usernameQuery->execute([$username]);
            $usernameRowCount = $usernameQuery->rowCount();

            if($usernameRowCount > 0 || $usernameRowCount > 0){
                throw new PDOException();
                // TODO TODO TODO TODO
            }

            $query = $db->prepare("INSERT INTO users(username,email,password,fullname,authtoken) VALUES(?,?,?,?,?)");
            $query->execute([$username, $email, $hashPassword, $fullname, $authtoken]);

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

    $app->post('/login', function (Request $request, Response $response) {

        $email = $request->getParam('email');
        $password = $request->getParam('password');

        $db = new Db();

        try {

            $db = $db->connect();
            $query = $db->prepare("SELECT * FROM `users` WHERE email = :email");
            $query->execute([':email' => $email]);
            $rowCount = $query->rowCount();

            if ($rowCount > 0) {
                $fetchUserData = $query->fetch(PDO::FETCH_OBJ);
                if($fetchUserData) {
                    if (password_verify($password,$fetchUserData->password)) {                          
                        return $response->withStatus(200)->withHeader("Content-Type", "application/json")->withJson(
                            array(
                                "data" => array(
                                    "message" => "Operation completed!",
                                    "success" => true,
                                    "data" => $fetchUserData,
                                    "token" => Authorization::createToken($fetchUserData->id, $fetchUserData->email)                                
                                ),
                            )
                        );
                    }
                }
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

    $app->post('/resettoken', function (Request $request, Response $response) {

        $db = new Db();
        
        $HTTPToken = str_replace("Bearer ", "", $request->getServerParams()["HTTP_AUTHORIZATION"]);
		// Verify the token.
		$result = Authorization::checkToken($HTTPToken);
		/** @var string $user - User ID */
		$user_id = $result->header->id;
        
        try {
            $authtoken =  bin2hex(openssl_random_pseudo_bytes(4));
            $db = $db->connect();

            $query = $db->prepare("UPDATE `users` SET `authtoken` = :authtoken  WHERE id = :user_id");
            $query->execute([':authtoken' => $authtoken, ':user_id' => $user_id]);

            return $response->withStatus(200)->withHeader("Content-Type", "application/json")->withJson(
                array(
                    "data" => array(
                        "message" => "Token Created!",
                        "success" => true,
                        "data" => $authtoken
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
    ** Endpoint for pair user.
    */

    $app->post('/pairs/create', function (Request $request, Response $response) {

        $HTTPToken = str_replace("Bearer ", "", $request->getServerParams()["HTTP_AUTHORIZATION"]);
        // Verify the token.
        $result = Authorization::checkToken($HTTPToken);
        /** @var string $user - User ID */
        $senderID = $result->header->id;
        
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

        $HTTPToken = str_replace("Bearer ", "", $request->getServerParams()["HTTP_AUTHORIZATION"]);
        // Verify the token.
        $result = Authorization::checkToken($HTTPToken);
        /** @var string $user - User ID */
        $senderID = $result->header->id;
        
        $authToken = $request->getParam('authtoken');

        $db = new Db();

        try {

            if(!$senderID) {
                echo "Error!";
                die();
            }

            $db = $db->connect();
            $query = $db->prepare("SELECT * FROM `pairs` WHERE receiver_token = :authToken AND `status` = 0");
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
    ** Endpoint for pair update.
    */

    $app->post('/pairs/update', function (Request $request, Response $response) {

        $sender_id = $request->getParam('sender_id');

        $HTTPToken = str_replace("Bearer ", "", $request->getServerParams()["HTTP_AUTHORIZATION"]);
        // Verify the token.
        $result = Authorization::checkToken($HTTPToken);
        /** @var string $user - User ID */
        $my_id = $result->header->id;
        
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

    /*
    ** Endpoint for save location.
    */

    $app->post('/savelocation', function (Request $request, Response $response) {

        $getXCordinates = $request->getParam('x_cordinate');
        $getYCordinates = $request->getParam('y_cordinate');

        $HTTPToken = str_replace("Bearer ", "", $request->getServerParams()["HTTP_AUTHORIZATION"]);
        $result = Authorization::checkToken($HTTPToken);
        $my_id = $result->header->id;

        $db = new Db();

        try {

            $db = $db->connect();
            $query = $db->prepare("SELECT * FROM `locations` WHERE user_id = :my_id");
            $query->execute([':my_id' => $my_id]);
            $rows = $query->rowCount();

            if($rows > 0) {
                /* Updating location */
                $query = $db->prepare("UPDATE `locations` SET `x_cordinate` = :getXCordinates , `y_cordinate` = :getYCordinates  WHERE user_id = :my_id");
                $query->execute([':getXCordinates' => $getXCordinates, ':getYCordinates' => $getYCordinates, ':my_id' => $my_id]);
                $message = "Updated location operation completed!";
            } else {
                /* Insert new location */
                $query = $db->prepare("INSERT INTO locations(x_cordinate,y_cordinate,user_id) VALUES(?,?,?)");
                $query->execute([$getXCordinates, $getYCordinates, $my_id]);
                $message = "Adding location operation completed!";
            }

            return $response->withStatus(200)->withHeader("Content-Type", "application/json")->withJson(
                array(
                    "data" => array(
                        "message" => $message,
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

    $app->post('/checkPair', function (Request $request, Response $response) {

        $HTTPToken = str_replace("Bearer ", "", $request->getServerParams()["HTTP_AUTHORIZATION"]);
        $result = Authorization::checkToken($HTTPToken);
        $my_id = $result->header->id;

        $db = new Db();

        try {

            $db = $db->connect();
            $query = $db->prepare("SELECT * FROM `users` WHERE id = :my_id");
            $query->execute([':my_id' => $my_id]);
            $user = $query->fetch(PDO::FETCH_OBJ);

            if($user->pair_id == null) {
                $message = "You have no pair";
                return $response->withStatus(200)->withHeader("Content-Type", "application/json")->withJson(
                    array(
                        "data" => array(
                            "message" => $message,
                            "success" => false
                        ),
                    )
                );
            } else {
                $query = $db->prepare("SELECT * FROM `users` WHERE id = :pair_id");
                $query->execute([':pair_id' => $user->pair_id]);
                $pair = $query->fetch(PDO::FETCH_OBJ);
                $message = "Pair found!";
            }

            return $response->withStatus(200)->withHeader("Content-Type", "application/json")->withJson(
                array(
                    "data" => array(
                        "message" => $message,
                        "success" => true,
                        "data" => $pair
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

});