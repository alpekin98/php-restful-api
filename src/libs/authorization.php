<?php
use \Firebase\JWT\JWT;

class Authorization
{
    /**
     * This method create a valid token.
     *
     * @param    int        $id        User id
     * @param    string    $user    Username
     * @return    string    Authorization Valid token.
     */
    public static function createToken($id, $email) {

        $secret = SECRET;
        $starTimeOfToken = date('Y-m-d H:i:s');
        $_2yearsLater = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')+2));

        $token = array(
            'header' => [ // User Information
                'id' => $id, // User id
                'user_email' => $email, // username
            ],
            'payload' => [
                'iat' => $starTimeOfToken, // Start time of the token
                'exp' => $_2yearsLater,
            ],
        );
        // Encode Authentication Token
        return JWT::encode($token, $secret, "HS256");
    }

    public static function checkToken($token) {

        $secret = SECRET;
        $AuthorizationObject = JWT::decode($token, $secret, array("HS256")); // Decode Authentication Token

        if (isset($AuthorizationObject->payload)) {
            $now = strtotime(date('Y-m-d H:i:s'));
            $exp = strtotime($AuthorizationObject->payload->exp);
            if (($exp - $now) > 0) {
                return $AuthorizationObject;
            }
        }

        return false;
    }

}
