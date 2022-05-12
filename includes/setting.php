<?php
class JudoJWTLoginPlugin_setting
{
    static function init()
    {
        add_action('admin_menu', ['JudoJWTLoginPlugin_setting', 'login_options_page']);
        add_action('admin_init', array('JudoJWTLoginPlugin_setting', 'login_setting_init'));
    }

    static function login_options_page()
    {
        add_options_page(
            'JWT Login Options', // page <title>Title</title>
            'Judo JWT Login', // menu link text
            'manage_options', // capability to access the page
            'judo-jwt-login', // page URL slug
            ['JudoJWTLoginPlugin_setting', 'login_options_page_content'], // callback function with content
            0 // priority
        );
    }

    static function login_options_page_content()
    {
        ob_start();
?>
        <div class="wrap">
            <h1>JWT 로그인 관리자 설정</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('judo_jwt_general_options');
                do_settings_sections('judo-jwt-login');
                submit_button("설정하기");
                ?>
            </form>
        </div>
    <?php
        echo ob_get_clean();
    }

    static function login_setting_init()
    {
        add_settings_section(
            'judo_jwt_login_inputs',
            'Judo JWT Login 설정',
            ['JudoJWTLoginPlugin_setting', 'login_section_callback'],
            'judo-jwt-login'
        );

        register_setting('judo_jwt_general_options', 'judo_jwt_login_hash');
        add_settings_field(
            'judo_jwt_login_hash',
            '<label for="judo_jwt_login_hash">해쉬 설정</label>',
            ['JudoJWTLoginPlugin_setting', 'hash_callback'],
            'judo-jwt-login',
            'judo_jwt_login_inputs'
        );

        register_setting('judo_jwt_general_options', 'judo_jwt_login_refresh_token_hash');
        add_settings_field(
            'judo_jwt_login_refresh_token_hash',
            '<label for="judo_jwt_login_refresh_token_hash">리프레시 토큰 해쉬 설정</label>',
            ['JudoJWTLoginPlugin_setting', 'refresh_token_hash_callback'],
            'judo-jwt-login',
            'judo_jwt_login_inputs'
        );

        register_setting('judo_jwt_general_options', 'judo_jwt_login_access_token_expire_hours');
        add_settings_field(
            'judo_jwt_login_access_token_expire_hours',
            '<label for="judo_jwt_login_access_token_expire_hours">액세스 토큰 만료 시간 설정</label>',
            ['JudoJWTLoginPlugin_setting', 'access_token_expire_hours_callback'],
            'judo-jwt-login',
            'judo_jwt_login_inputs'
        );

        register_setting('judo_jwt_general_options', 'judo_jwt_login_refresh_token_expire_hours');
        add_settings_field(
            'judo_jwt_login_refresh_token_expire_hours',
            '<label for="judo_jwt_login_refresh_token_expire_hours">리프레시 토큰 만료 시간 설정</label>',
            ['JudoJWTLoginPlugin_setting', 'refresh_token_expire_hours_callback'],
            'judo-jwt-login',
            'judo_jwt_login_inputs'
        );
    }
    static function login_section_callback()
    {
        echo '<p>Judo JWT Login 설정입니다.</p>';
    }

    static function hash_callback()
    {
        ob_start();
    ?>
        <input type="text" name="judo_jwt_login_hash" id="judo_jwt_login_hash" value="<?= get_option("judo_jwt_login_hash", "") ?>" /><br />
    <?php
        echo ob_get_clean();
    }
    static function refresh_token_hash_callback()
    {
        ob_start();
    ?>
        <input type="text" name="judo_jwt_login_refresh_token_hash" id="judo_jwt_login_refresh_token_hash" value="<?= get_option("judo_jwt_login_refresh_token_hash", "") ?>" /><br />
    <?php
        echo ob_get_clean();
    }

    static function access_token_expire_hours_callback()
    {
        ob_start();
    ?>
        <input type="number" min="1" max="24" name="judo_jwt_login_access_token_expire_hours" id="judo_jwt_login_access_token_expire_hours" value="<?= get_option("judo_jwt_login_access_token_expire_hours", "") ?>" /><br />
    <?php
        echo ob_get_clean();
    }

    static function refresh_token_expire_hours_callback()
    {
        ob_start();
    ?>
        <input type="number" min="1" max="336" name="judo_jwt_login_refresh_token_expire_hours" id="judo_jwt_login_refresh_token_expire_hours" value="<?= get_option("judo_jwt_login_refresh_token_expire_hours", "") ?>" /><br />
<?php
        echo ob_get_clean();
    }
}
