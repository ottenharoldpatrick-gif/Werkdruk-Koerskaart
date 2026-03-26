<?php
/**
 * Plugin Name: Werkdruk KoersKaart
 * Description: Begeleide invoer stap 1 t/m 5 van de KoersKaart Werkdrukplan (CAO VO).
 *              Per team, meerdere invullers, overzicht zichtbaar voor iedereen.
 * Version:     2.0.0
 * Author:      eco.isdigitaal.nl
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WK_DIR',     plugin_dir_path( __FILE__ ) );
define( 'WK_VERSION', '2.0.0' );
define( 'WK_TABLE',   'werkdruk_entries' );

require_once WK_DIR . 'includes/class-form.php';
require_once WK_DIR . 'includes/class-overview.php';
require_once WK_DIR . 'includes/class-admin.php';

final class Werkdruk_KoersKaart_Plugin {

    private static ?self $inst = null;

    public static function init(): void {
        if ( self::$inst === null ) {
            self::$inst = new self();
        }
    }

    private function __construct() {
        register_activation_hook( __FILE__, [ $this, 'activate' ] );
        add_action( 'init',       [ $this, 'handle_post' ] );
        add_shortcode( 'werkdruk_koerskaart', [ $this, 'shortcode' ] );
        add_action( 'admin_menu', [ 'Werkdruk_Admin', 'register' ] );
        add_action( 'admin_post_werkdruk_export_csv',    [ 'Werkdruk_Admin', 'export_csv' ] );
        add_action( 'admin_post_werkdruk_delete_entry',  [ 'Werkdruk_Admin', 'delete_entry' ] );
    }

    /* ------------------------------------------------------------------ */
    /*  Activatie: tabel aanmaken                                           */
    /* ------------------------------------------------------------------ */

    public function activate(): void {
        global $wpdb;
        $sql = "CREATE TABLE " . self::tbl() . " (
            id          mediumint(9)  NOT NULL AUTO_INCREMENT,
            created_at  datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            team        varchar(255)  NOT NULL,
            name        varchar(255)  NOT NULL,
            wp_level    varchar(50)   NOT NULL,
            wp_note     text,
            causes      text,
            solutions   text,
            measures    longtext,
            PRIMARY KEY  (id),
            KEY idx_team    (team),
            KEY idx_created (created_at)
        ) " . $wpdb->get_charset_collate() . ";";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /* ------------------------------------------------------------------ */
    /*  POST-afhandeling                                                    */
    /* ------------------------------------------------------------------ */

    public function handle_post(): void {
        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' || empty( $_POST['werkdruk_submit'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['werkdruk_nonce'] ?? '', 'werkdruk_submit' ) ) {
            wp_die( 'Beveiligingscontrole mislukt. Ga terug en probeer opnieuw.', 'Fout', [ 'response' => 403 ] );
        }

        $team  = trim( sanitize_text_field(     $_POST['team']     ?? '' ) );
        $name  = trim( sanitize_text_field(     $_POST['name']     ?? '' ) );
        $level = trim( sanitize_text_field(     $_POST['wp_level'] ?? '' ) );
        $note  = trim( sanitize_textarea_field( $_POST['wp_note']  ?? '' ) );

        $causes    = self::clean_list(     $_POST['causes']    ?? [] );
        $solutions = self::clean_list(     $_POST['solutions'] ?? [] );
        $measures  = self::clean_measures( $_POST['measures']  ?? [] );

        $errors = [];
        if ( $team  === '' ) $errors[] = 'Vul de naam van het team of de sectie in.';
        if ( $name  === '' ) $errors[] = 'Vul jouw naam in.';
        if ( ! in_array( $level, [ 'laag', 'gemiddeld', 'hoog', 'nvt' ], true ) )
            $errors[] = 'Kies een werkdrukniveau (laag / gemiddeld / hoog / n.v.t.).';
        if ( empty( $causes ) )   $errors[] = 'Vul minimaal één oorzaak in, of typ "n.v.t.".';
        if ( empty( $measures ) ) $errors[] = 'Vul minimaal één maatregel in, of typ "n.v.t.".';

        if ( ! empty( $errors ) ) {
            $token = wp_generate_uuid4();
            set_transient( 'wk_err_' . $token, $errors, 120 );
            setcookie( 'wk_err_token', $token, time() + 120, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
            wp_safe_redirect( self::referer_url( 'error' ) );
            exit;
        }

        global $wpdb;
        $inserted = $wpdb->insert(
            self::tbl(),
            [
                'created_at' => current_time( 'mysql' ),
                'team'       => $team,
                'name'       => $name,
                'wp_level'   => $level,
                'wp_note'    => $note,
                'causes'     => wp_json_encode( $causes,    JSON_UNESCAPED_UNICODE ),
                'solutions'  => wp_json_encode( $solutions, JSON_UNESCAPED_UNICODE ),
                'measures'   => wp_json_encode( $measures,  JSON_UNESCAPED_UNICODE ),
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );

        if ( $inserted === false ) {
            wp_die( 'Er is een databasefout opgetreden. Probeer het opnieuw.', 'Fout', [ 'response' => 500 ] );
        }

        wp_safe_redirect( self::referer_url( 'ok' ) );
        exit;
    }

    /* ------------------------------------------------------------------ */
    /*  Shortcode                                                           */
    /* ------------------------------------------------------------------ */

    public function shortcode( array $atts ): string {
        $atts        = shortcode_atts( [ 'team' => '' ], $atts, 'werkdruk_koerskaart' );
        $team_preset = sanitize_text_field( $_GET['team'] ?? $atts['team'] );
        $status      = sanitize_text_field( $_GET['werkdruk'] ?? '' );

        $errors = [];
        if ( $status === 'error' && ! empty( $_COOKIE['wk_err_token'] ) ) {
            $token = sanitize_text_field( $_COOKIE['wk_err_token'] );
            if ( preg_match( '/^[0-9a-f\-]{36}$/', $token ) ) {
                $errors = (array) get_transient( 'wk_err_' . $token );
                delete_transient( 'wk_err_' . $token );
            }
            setcookie( 'wk_err_token', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
        }

        ob_start();
        Werkdruk_Form::render( $team_preset, $status, $errors );
        if ( current_user_can( 'edit_posts' ) ) {
            Werkdruk_Overview::render( $team_preset );
        }
        return ob_get_clean();
    }

    /* ------------------------------------------------------------------ */
    /*  Hulpfuncties                                                        */
    /* ------------------------------------------------------------------ */

    public static function tbl(): string {
        global $wpdb;
        return $wpdb->prefix . WK_TABLE;
    }

    private static function referer_url( string $status ): string {
        $ref = wp_get_referer() ?: home_url( '/' );
        return add_query_arg( 'werkdruk', rawurlencode( $status ), remove_query_arg( 'werkdruk', $ref ) );
    }

    public static function clean_list( array $arr ): array {
        $out = [];
        foreach ( $arr as $item ) {
            $clean = trim( sanitize_textarea_field( $item ) );
            if ( $clean !== '' ) $out[] = $clean;
        }
        return $out;
    }

    public static function clean_measures( array $arr ): array {
        $out = [];
        foreach ( $arr as $m ) {
            if ( ! is_array( $m ) ) continue;
            $desc = trim( sanitize_textarea_field( $m['desc'] ?? '' ) );
            if ( $desc === '' ) continue;
            $out[] = [
                'desc'        => $desc,
                'cat'         => sanitize_text_field( $m['cat']         ?? '' ),
                'effect'      => sanitize_text_field( $m['effect']      ?? '' ),
                'feasibility' => sanitize_text_field( $m['feasibility'] ?? '' ),
            ];
        }
        return $out;
    }

    public static function decode( ?string $val ): array {
        if ( ! $val ) return [];
        $decoded = json_decode( $val, true );
        return is_array( $decoded ) ? $decoded : [];
    }
}

Werkdruk_KoersKaart_Plugin::init();
