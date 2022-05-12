<?php
class JudoJWTDB
{
    static function get_table_name()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'judo_jwt_tokens';
        return $table_name;
    }
    static function create_db()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = self::get_table_name();

        $sql = "CREATE TABLE $table_name (
            id bigint NOT NULL AUTO_INCREMENT,
            user_id bigint NOT NULL,
            access_token text,
            refresh_token text,
            PRIMARY KEY id (id)
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    static function validate_user_tokens($user_id, $access_token, $refresh_token)
    {
        global $wpdb;
        $table = self::get_table_name();
        $sql = "SELECT * FROM $table WHERE user_id = $user_id";
        $tokens = $wpdb->get_row($sql);
        if ($tokens->access_token !== $access_token) {
            return false;
        };
        if ($tokens->refresh_token !== $refresh_token) {
            return false;
        }
        return true;
    }
    static function clear_user_tokens($user_id)
    {
        global $wpdb;
        $table = self::get_table_name();
        $sql = "UPDATE $table SET access_token = NULL, refresh_token = NULL WHERE user_id = $user_id";
        $wpdb->get_results($sql);
    }
    static function check_user_id_in_table($user_id)
    {
        global $wpdb;
        $table_name = self::get_table_name();
        $sql = "SELECT user_id FROM $table_name WHERE user_id = $user_id";
        $result = $wpdb->get_results($sql, ARRAY_A);
        if (count($result) > 0) return true;
        else return false;
    }
    static function add_tokens_to_db($user_id, $access_token, $refresh_token)
    {
        global $wpdb;
        $table_name = self::get_table_name();
        $sql = "";
        if (!self::check_user_id_in_table($user_id)) {
            $sql = "INSERT INTO $table_name(user_id) VALUES($user_id);";
            $wpdb->get_results($sql);
        }
        $sql = "UPDATE $table_name SET access_token = '$access_token', refresh_token = '$refresh_token' WHERE user_id = $user_id;";
        $result = $wpdb->get_results($sql);
        return $result;
    }
}
