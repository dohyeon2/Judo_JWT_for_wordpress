<?php

/**
 * Plugin Name: Judo JWT Login
 * Description: 워드프레스 JWT 인증 플러그인 입니다.
 * Version: 1.0.0:beta
 * Author: Dohyeon Ju
 */

if (!class_exists('JudoJWTLoginPlugin')) {
    class JudoJWTLoginPlugin
    {
        const INCLUDES_DIR =  '/includes/';
        const DEFAULT_ACCESS_TOKEN_EXPIRE_HOURS = 1;
        const DEFAULT_REFRESH_TOKEN_EXPIRE_HOURS = 336;
        public static function init()
        {
            self::init_setting();
            self::init_router();
            register_activation_hook(__FILE__, ['JudoJWTLoginPlugin', 'judo_jwt_login_activate']);
        }
        private static function init_setting()
        {
            include_once plugin_dir_path(__FILE__) . self::INCLUDES_DIR . 'setting.php';
            JudoJWTLoginPlugin_setting::init();
        }
        private static function init_router()
        {
            include_once plugin_dir_path(__FILE__) . self::INCLUDES_DIR . 'router.php';
            include_once plugin_dir_path(__FILE__) . self::INCLUDES_DIR . 'token.php';
            include_once plugin_dir_path(__FILE__) . self::INCLUDES_DIR . 'db.php';
            JudoJWTLoginPlugin_router::init_jwt_auth_rest_api();
            JudoJWTLoginPlugin_router::add_login_api_endpoint();
            JudoJWTDB::create_db();
        }
        public static function judo_jwt_login_activate()
        {
            self::judo_jwt_login_set_initial_options();
        }
        public static function judo_jwt_login_set_initial_options()
        {
            update_option("judo_jwt_login_hash", uniqid());
            update_option("judo_jwt_login_refresh_token_hash", uniqid());
            update_option("judo_jwt_login_access_token_expire_hours", self::DEFAULT_ACCESS_TOKEN_EXPIRE_HOURS);
            update_option("judo_jwt_login_refresh_token_expire_hours", self::DEFAULT_REFRESH_TOKEN_EXPIRE_HOURS);
        }
    }

    JudoJWTLoginPlugin::init();
}
