<?php
class JudoJWTLoginToken
{
    function __construct($payload = [], $is_refresh = false)
    {
        $this->header = [
            "typ" => "JWT",
            "alg" => "HS256"
        ];
        $this->payload = $payload;
        $this->is_refresh = $is_refresh;
    }
    private function get_hash()
    {
        if ($this->is_refresh) {
            return get_option("judo_jwt_login_refresh_token_hash");
        } else {
            return get_option("judo_jwt_login_hash");
        }
    }
    private function get_expire_hours()
    {
        if ($this->is_refresh) {
            return get_option("judo_jwt_login_refresh_token_expire_hours");
        } else {
            return get_option("judo_jwt_login_access_token_expire_hours");
        }
    }
    private function make_token()
    {
        $payload = [];
        $payload["data"] = $this->payload;
        $payload["iss"] = $_SERVER["SERVER_NAME"];
        $payload["iat"] = time();
        $payload["nbf"] = time();
        $payload["exp"] = time() + ($this->get_expire_hours() * HOUR_IN_SECONDS);
        $header = self::base64url_encode(json_encode($this->header));
        $payload = self::base64url_encode(json_encode($payload));
        $secret = self::base64url_encode(
            self::make_secret(
                $header . "." . $payload,
                $this->get_hash()
            )
        );
        $token = $header . "." . $payload . "." . $secret;
        return $token;
    }
    public function get_token()
    {
        return $this->make_token();
    }
    static public function get_token_from_header($authorization)
    {
        $splitted_authorization = explode(" ", $authorization);
        [$schema, $token] = @$splitted_authorization;
        if ($schema !== "Bearer") {
            return false;
        }
        return $token;
    }
    static public function parse_token($token)
    {
        $token_split = explode(".", $token);
        [$header, $payload, $secret] = @$token_split;
        $arr = [
            "header" => $header,
            "payload" => $payload,
            "secret" => $secret,
        ];
        return $arr;
    }
    static public function decode_token_data($data)
    {
        return json_decode(self::base64url_decode($data));
    }
    static public function validate_token($token, $hash)
    {
        $token = self::parse_token($token);
        extract($token);
        if ($secret !== self::base64url_encode(
            self::make_secret(
                $header . "." . $payload,
                $hash
            )
        )) {
            return "invalid";
        }
        $data = self::decode_token_data($payload);
        if ($data->exp < time()) {
            return "expired";
        }
        if ($data->nbf > time()) {
            return "notbefore";
        }
        return "valid";
    }
    static public function make_secret($data, $hash)
    {
        return hash_hmac('sha256', $data, $hash);
    }
    static public function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    static public function base64url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
