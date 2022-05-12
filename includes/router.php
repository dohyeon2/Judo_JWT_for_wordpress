<?php
class JudoJWTLoginPlugin_router
{
    const ROUTER_NAMESPACE = "judo_jwt/v1";
    static public function add_endpoint($namespace, $path, $args)
    {
        add_action('rest_api_init', function () use ($namespace, $path, $args) {
            register_rest_route($namespace, $path, $args);
        });
    }
    static public function init_jwt_auth_rest_api()
    {
        add_filter('rest_request_before_callbacks', 'authorize_api_requests', 10, 3);
        function authorize_api_requests($response, $handler, WP_REST_Request $request)
        {
            $params = $request->get_params();
            if ($request->get_header("Authorization") !== "") {
                include_once plugin_dir_path(__FILE__) . 'token.php';
                $token = JudoJWTLoginToken::get_token_from_header($request->get_header("Authorization"));
                $refresh_token = @$params["refresh_token"];
                $parsed_token = JudoJWTLoginToken::parse_token($token);
                $user_id = JudoJWTLoginToken::decode_token_data($parsed_token["payload"])->data->user_id;
                //액세스 토큰 검증
                switch (JudoJWTLoginToken::validate_token($token, get_option("judo_jwt_login_hash"))) {
                    case "invalid":
                        include_once plugin_dir_path(__FILE__) . 'db.php';
                        JudoJWTDB::clear_user_tokens($user_id);
                        return new WP_Error("invalid_token", "유효하지 않은 토큰입니다.", ["status" => 403]);
                    case "expired":
                        //리프레시 토큰이 있는지?
                        if ($refresh_token === NULL) {
                            return new WP_Error("exp_no_reftoken", "만료된 액세스 토큰입니다. 유저에게 다시 로그인을 요청하거나, 리프레시 토큰과 같이 요청해주세요.", [
                                "status" => 403
                            ]);
                        }

                        //리프레시 토큰 검증
                        if (JudoJWTLoginToken::validate_token($refresh_token, get_option("judo_jwt_login_refresh_token_hash")) !== "valid") {
                            //리프레시 토큰 검증 실패
                            return new WP_Error("exp_no_reftoken", "리프레시 토큰 검증에 실패했습니다. 정확한 리프레시 토큰을 입력했는지 확인하거나, 유저에게 다시 로그인을 요청하세요.", ["status" => 403]);
                        }
                        //이상없으면 DB와 액세스 토큰 및 리프레시 토큰 비교
                        include_once plugin_dir_path(__FILE__) . 'db.php';
                        if (JudoJWTDB::validate_user_tokens($user_id, $token, $refresh_token) !== true) {
                            //이상있으면 403, 리프레시, 액세스 토큰 db에서 clear
                            JudoJWTDB::clear_user_tokens($user_id);
                            return new WP_Error("incorrect_acc_ref_token", "DB에 등록된 토큰 정보와 상이합니다. 디비에서 토큰 정보를 제거합니다. 유저에게 다시 로그인을 요청하세요.", ["status" => 403]);
                        }

                        $result = [
                            "user_id" => $user_id
                        ];
                        $token_instance = new JudoJWTLoginToken($result);
                        $refresh_token_instance = new JudoJWTLoginToken($result, true);
                        $new_access_token = $token_instance->get_token();
                        $new_refresh_token = $refresh_token_instance->get_token();

                        JudoJWTDB::add_tokens_to_db((int)$user_id, $new_access_token, $new_refresh_token);

                        return new WP_Error("new_tokens", "토큰 정보를 업데이트 했습니다. 새로운 토큰으로 다시 요청하세요.", [
                            "status" => 200,
                            "tokens" => [
                                "access_token" => $new_access_token,
                                "refresh_token" => $new_refresh_token
                            ]
                        ]);

                        break;
                    case "valid":
                        $token_row_data = JudoJWTLoginToken::parse_token($token);
                        $data = JudoJWTLoginToken::decode_token_data($token_row_data["payload"]);
                        wp_set_current_user($data->data->user_id);
                        break;
                    default:
                        break;
                }
            }
            return $response;
        }
    }
    static public function add_login_api_endpoint()
    {
        JudoJWTLoginPlugin_router::add_endpoint(self::ROUTER_NAMESPACE, "login", [
            'methods' => 'POST',
            'callback' => function (WP_REST_Request $request) {
                $param = $request->get_params();
                $username = $param["username"];
                $password = $param["password"];
                $auth = wp_authenticate($username, $password);
                if (is_wp_error($auth)) {
                    return new WP_REST_Response($auth, 404);
                }
                $result = [
                    "user_id" => $auth->ID
                ];
                $token_instance = new JudoJWTLoginToken($result);
                $refresh_token_instance = new JudoJWTLoginToken($result, true);
                $token = $token_instance->get_token();
                $refresh_token = $refresh_token_instance->get_token();

                include_once plugin_dir_path(__FILE__) . 'db.php';
                JudoJWTDB::add_tokens_to_db((int)$auth->ID, $token, $refresh_token);

                $result["access_token"] = $token;
                $result["refresh_token"] = $refresh_token;
                return new WP_REST_Response($result);
            },
            'permission_callback' => '__return_true'
        ]);
    }
}
