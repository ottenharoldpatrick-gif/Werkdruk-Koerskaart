<?php
/**
 * Plugin Name: Werkdruk KoersKaart
 * Description: Begeleide invoer stap 1 t/m 6 van de KoersKaart Werkdrukplan (CAO VO). Per team, meerdere invullers, overzicht zichtbaar voor iedereen.
 * Version:     1.2.0
 * Author:      eco.isdigitaal.nl
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WK_DIR', plugin_dir_path( __FILE__ ) );

require_once WK_DIR . 'includes/class-form.php';
require_once WK_DIR . 'includes/class-overview.php';
require_once WK_DIR . 'includes/class-admin.php';

class Werkdruk_KoersKaart_Plugin {

    private static $inst = null;
    
    public static function init() {
        if ( self::$inst === null ) {
            self::$inst = new self();
        }
        return self::$inst;
    }

    private function __construct() {
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        add_action( 'init', array( $this, 'handle_post' ) );
        add_shortcode( 'werkdruk_koerskaart', array( $this, 'shortcode' ) );
        add_filter( 'the_content', 'shortcode_unautop' );
        add_filter( 'the_content', 'do_shortcode' );
        add_action( 'admin_menu', array( 'Werkdruk_Admin', 'register' ) );
        add_action( 'admin_post_werkdruk_export_csv', array( 'Werkdruk_Admin', 'export_csv' ) );
        add_action( 'admin_post_werkdruk_delete_entry', array( 'Werkdruk_Admin', 'delete_entry' ) );
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'werkdruk_entries';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id          mediumint(9) NOT NULL AUTO_INCREMENT,
            created_at  datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            team        varchar(255) NOT NULL,
            name        varchar(255) NOT NULL,
            wp_level    varchar(50),
            wp_note     text,
            causes      text,
            solutions   text,
            measures    LONGTEXT     NULL,
            PRIMARY KEY (id),
            KEY idx_team    (team),
            KEY idx_created (created_at)
        ) $charset;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public function handle_post() {
        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' || empty( $_POST['werkdruk_submit'] ) ) {
            return;
        }
        if ( ! wp_verify_nonce(
            isset( $_POST['werkdruk_nonce'] ) ? $_POST['werkdruk_nonce'] : '',
            'werkdruk_submit'
        ) ) {
            wp_die(
                'Beveiligingscontrole mislukt. Ga terug en probeer opnieuw.',
                'Fout',
                array( 'response' => 403 )
            );
        }

        $team  = trim( sanitize_text_field(     isset( $_POST['team'] )     ? $_POST['team']     : '' ) );
        $name  = trim( sanitize_text_field(     isset( $_POST['name'] )     ? $_POST['name']     : '' ) );
        $level = trim( sanitize_text_field(     isset( $_POST['wp_level'] ) ? $_POST['wp_level'] : '' ) );
        $note  = trim( sanitize_textarea_field( isset( $_POST['wp_note'] )  ? $_POST['wp_note']  : '' ) );

        $causes    = self::clean_list(     isset( $_POST['causes'] )   ? $_POST['causes']   : array() );
        $solutions = self::clean_list(     isset( $_POST['solutions'] ) ? $_POST['solutions'] : array() );
        $measures  = self::clean_measures( isset( $_POST['measures'] )  ? $_POST['measures']  : array() );

        $errors = array();
        if ( $team === '' ) {
            $errors[] = 'Vul de naam van het team of de sectie in.';
        }
        if ( $name === '' ) {
            $errors[] = 'Vul jouw naam in.';
        }
        if ( ! in_array( $level, array( 'laag', 'gemiddeld', 'hoog', 'nvt' ), true ) ) {
            $errors[] = 'Kies een werkdrukniveau (laag / gemiddeld / hoog / n.v.t.).';
        }
        if ( empty( $causes ) ) {
            $errors[] = 'Vul minimaal één oorzaak in, of typ "n.v.t.".';
        }
        if ( empty( $measures ) ) {
            $errors[] = 'Vul minimaal één maatregel in, of typ "n.v.t.".';
        }

        if ( ! empty( $errors ) ) {
            $token = wp_generate_uuid4();
            set_transient( 'wk_err_' . $token, $errors, 120 );
            setcookie( 'wk_err_token', $token, time() + 120, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
//             wp_safe_redirect( self::referer_url( 'error' ) );
//             exit;
        }

        global $wpdb;
        $wpdb->insert(
            self::tbl(),
            array(
                'created_at' => current_time( 'mysql' ),
                'team'       => $team,
                'name'       => $name,
                'wp_level'   => $level,
                'wp_note'    => $note,
                'causes'     => wp_json_encode( $causes, JSON_UNESCAPED_UNICODE ),
                'solutions'  => wp_json_encode( $solutions, JSON_UNESCAPED_UNICODE ),
                'measures'   => wp_json_encode( $measures, JSON_UNESCAPED_UNICODE )
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

//         wp_safe_redirect( self::referer_url( 'ok' ) );
//         exit;
    }

    public function shortcode( $atts ) {
        $atts        = shortcode_atts( array( 'team' => '' ), $atts, 'werkdruk_koerskaart' );
        $team_preset = sanitize_text_field( isset( $_GET['team'] ) ? $_GET['team'] : $atts['team'] );
        $status      = sanitize_text_field( isset( $_GET['werkdruk'] ) ? $_GET['werkdruk'] : '' );

        $errors = array();
        if ( $status === 'error' && ! empty( $_COOKIE['wk_err_token'] ) ) {
            $token = sanitize_text_field( $_COOKIE['wk_err_token'] );
            $errors = get_transient( 'wk_err_' . $token );
            if ( ! is_array( $errors ) ) {
                $errors = array();
            }
            delete_transient( 'wk_err_' . $token );
            setcookie( 'wk_err_token', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
        }

        ob_start();
        Werkdruk_Form::render( $team_preset, $status, $errors );
        if ( current_user_can( 'edit_posts' ) ) {
            Werkdruk_Overview::render( $team_preset );
        }
        return ob_get_clean();
    }

    public static function tbl() {
        global $wpdb;
        return $wpdb->prefix . 'werkdruk_entries';
    }

    private static function referer_url( $status ) {
        $ref = wp_get_referer();
        if ( ! $ref ) {
            $ref = home_url( '/' );
        }
        $ref = remove_query_arg( 'werkdruk', $ref );
        return add_query_arg( 'werkdruk', rawurlencode( $status ), $ref );
    }

    public static function clean_list( $arr ) {
        if ( ! is_array( $arr ) ) {
            return array();
        }
        $out = array();
        foreach ( $arr as $item ) {
            $clean = trim( sanitize_text_field( $item ) );
            if ( $clean !== '' ) {
                $out[] = $clean;
            }
        }
        return $out;
    }

    public static function clean_measures( $arr ) {
        if ( ! is_array( $arr ) ) {
            return array();
        }
        $out = array();
        foreach ( $arr as $m ) {
            if ( ! is_array( $m ) ) {
                continue;
            }
            $desc = isset( $m['desc'] ) ? trim( sanitize_text_field( $m['desc'] ) ) : '';
            if ( $desc === '' ) {
                continue;
            }
            $out[] = array(
                'desc'        => $desc,
                'cat'         => isset( $m['cat'] ) ? sanitize_text_field( $m['cat'] ) : '',
                'effect'      => isset( $m['effect'] ) ? sanitize_text_field( $m['effect'] ) : '',
                'feasibility' => isset( $m['feasibility'] ) ? sanitize_text_field( $m['feasibility'] ) : ''
            );
        }
        return $out;
    }

    public static function decode( $val ) {
        if ( ! $val ) {
            return array();
        }
        $decoded = json_decode( $val, true );
        return is_array( $decoded ) ? $decoded : array();
    }
}

Werkdruk_KoersKaart_Plugin::init();
