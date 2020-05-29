<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->add(new \Slim\Middleware\JwtAuthentication([
	// The secret key
	"secret" => SECRET,
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
        $authtoken = bin2hex(openssl_random_pseudo_bytes(16));

        $db = new Db();

        try {

            $db = $db->connect();

            $query = $db->prepare("INSERT INTO users(username,email,password,fullname,authtoken) VALUES(?,?,?,?,?)");
            $query->execute([$username, $email, $password, $fullname, $authtoken]);

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

                    if ($password === $fetchUserData->password) {  
                        
                        return $response->withStatus(200)->withHeader("Content-Type", "application/json")->withJson(
                            array(
                                "data" => array(
                                    "message" => "Operation completed!",
                                    "success" => true,
                                    "data" => $fetchUserData,
                                    "token" => Authorization::createToken($fetchUserData->id, $fetchUserData->email)                                ),
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

        echo "<pre>";
        echo "debugg";
        echo "</pre>";
        die();

        $db = new Db();
        
        $HTTPToken = str_replace("Bearer ", "", $request->getServerParams()["HTTP_AUTHORIZATION"]);
		// Verify the token.
		$result = Authorization::checkToken($HTTPToken);
		/** @var string $user - User ID */
		$user_id = $result->header->id;
           
        try {
            $authtoken =  bin2hex(openssl_random_pseudo_bytes(16));
            $db = $db->connect();

            $query = $db->prepare("UPDATE `users` SET `authtoken` = :authtoken  WHERE id = :user_id");
            $query->execute([':authtoken' => $authtoken, ':user_id' => $user_id]);

            return $response->withStatus(200)->withHeader("Content-Type", "application/json")->withJson(
                array(
                    "data" => array(
                        "message" => "Token Created!",
                        "success" => true,
                        "data" => $authenticationToken
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