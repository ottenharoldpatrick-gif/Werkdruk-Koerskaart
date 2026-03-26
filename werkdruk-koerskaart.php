<?php
/**
 * Plugin Name: Werkdruk KoersKaart
 * Description: Begeleide invoer stap 1 t/m 6 van de KoersKaart Werkdrukplan (CAO VO).
 * Version:     2.0.0
 * Author:      eco.isdigitaal.nl
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WK_DIR',     plugin_dir_path( __FILE__ ) );
define( 'WK_VERSION', '2.0.0' );
define( 'WK_TABLE',   'werkdruk_entries' );

// We laden alleen de nodige bestanden
require_once WK_DIR . 'includes/class-form.php';
require_once WK_DIR . 'includes/class-overview.php';

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
        
        // Koppeling met de administratie (back-end)
        add_action( 'admin_menu', [ 'Werkdruk_Overview', 'register' ] );
        add_action( 'admin_post_werkdruk_export_csv',    [ 'Werkdruk_Overview', 'export_csv' ] );
        add_action( 'admin_post_werkdruk_delete_entry',  [ 'Werkdruk_Overview', 'delete_entry' ] );
    }

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
            PRIMARY KEY  (id)
        ) " . $wpdb->get_charset_collate() . ";";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    // Deze functie verwerkt de verzending
    public function handle_post(): void {
        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' || empty( $_POST['werkdruk_submit'] ) ) return;

        if ( ! wp_verify_nonce( $_POST['werkdruk_nonce'] ?? '', 'werkdruk_submit' ) ) {
            wp_die( 'Beveiliging verlopen, probeer het opnieuw.' );
        }

        global $wpdb;
        $wpdb->insert( self::tbl(), [
            'created_at' => current_time( 'mysql' ),
            'team'       => sanitize_text_field( $_POST['team'] ?? '' ),
            'name'       => sanitize_text_field( $_POST['name'] ?? '' ),
            'wp_level'   => sanitize_text_field( $_POST['wp_level'] ?? '' ),
            'wp_note'    => sanitize_textarea_field( $_POST['wp_note'] ?? '' ),
            'causes'     => wp_json_encode( self::clean_list( $_POST['causes'] ?? [] ) ),
            'solutions'  => wp_json_encode( self::clean_list( $_POST['solutions'] ?? [] ) ),
            'measures'   => wp_json_encode( self::clean_measures( $_POST['measures'] ?? [] ) ),
        ]);

        // Hier sturen we de gebruiker terug naar de pagina waar hij vandaan kwam
        wp_safe_redirect( self::referer_url( 'ok' ) );
        exit;
    }

    public function shortcode( array $atts ): string {
        $atts   = shortcode_atts( [ 'team' => '' ], $atts, 'werkdruk_koerskaart' );
        $status = sanitize_text_field( $_GET['werkdruk'] ?? '' );
        $team   = sanitize_text_field( $_GET['team'] ?? $atts['team'] );

        ob_start();
        
        // TOON BEDANKT BERICHT
        if ( $status === 'ok' ) {
            echo '<div id="wk-success" style="background-color: #e6fffa; color: #234e52; padding: 20px; border-left: 5px solid #38b2ac; margin-bottom: 30px; border-radius: 4px;">';
            echo '<strong>Gelukt!</strong> Je formulier is verzonden en de resultaten zijn bijgewerkt.';
            echo '</div>';
        }

        Werkdruk_Form::render( $team, $status, [] );

        // Alleen voor beheerders laten we de tabel onder het formulier zien
        if ( current_user_can( 'edit_posts' ) ) {
            echo '<hr style="margin: 50px 0;"><h3>Beheerdersoverzicht: Inzendingen voor dit team</h3>';
            Werkdruk_Overview::render( $team );
        }

        return ob_get_clean();
    }

    public static function tbl(): string {
        global $wpdb;
        return $wpdb->prefix . WK_TABLE;
    }

    // Hulpfunctie voor het bepalen van de terugstuur-URL
    private static function referer_url( string $status ): string {
        $ref = $_SERVER['HTTP_REFERER'] ?? home_url( '/' );
        $ref = remove_query_arg( 'werkdruk', $ref );
        return add_query_arg( 'werkdruk', $status, $ref );
    }

    public static function clean_list( array $arr ): array {
        return array_filter( array_map( 'sanitize_textarea_field', $arr ) );
    }

    public static function clean_measures( array $arr ): array {
        $out = [];
        foreach ( (array)$arr as $m ) {
            if ( empty( $m['desc'] ) ) continue;
            $out[] = [
                'desc' => sanitize_textarea_field( $m['desc'] ),
                'cat'  => sanitize_text_field( $m['cat'] ?? '' ),
                'effect' => sanitize_text_field( $m['effect'] ?? '' ),
                'feasibility' => sanitize_text_field( $m['feasibility'] ?? '' ),
            ];
        }
        return $out;
    }

    public static function decode( ?string $val ): array {
        $decoded = json_decode( $val ?? '', true );
        return is_array( $decoded ) ? $decoded : [];
    }
}

Werkdruk_KoersKaart_Plugin::init();
