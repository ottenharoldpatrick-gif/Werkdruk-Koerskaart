<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Werkdruk_Admin {

    /* ------------------------------------------------------------------ */
    /*  Menu                                                                */
    /* ------------------------------------------------------------------ */

    public static function register(): void {
        add_menu_page(
            'Werkdruk KoersKaart',
            'Werkdruk KoersKaart',
            'manage_options',
            'werkdruk-koerskaart',
            [ self::class, 'render_pagina' ],
            'dashicons-feedback',
            26
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Admin-overzichtspagina                                              */
    /* ------------------------------------------------------------------ */

    public static function render_pagina(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $team_filter = sanitize_text_field( $_GET['team'] ?? '' );

        echo '<div class="wrap">';
        echo '<h1>Werkdruk KoersKaart – Beheer</h1>';
        self::export_knop( $team_filter );
        Werkdruk_Overview::render( $team_filter );
        echo '</div>';
    }

    private static function export_knop( string $team ): void {
        $url = add_query_arg( [
            'action' => 'werkdruk_export_csv',
            'team'   => rawurlencode( $team ),
            '_wpnonce' => wp_create_nonce( 'werkdruk_export' ),
        ], admin_url( 'admin-post.php' ) );

        echo '<p><a href="' . esc_url( $url ) . '" class="button button-primary">↓ Exporteer CSV</a></p>';
    }

    /* ------------------------------------------------------------------ */
    /*  CSV-export                                                          */
    /* ------------------------------------------------------------------ */

    public static function export_csv(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Geen toegang.', 403 );
        check_admin_referer( 'werkdruk_export' );

        global $wpdb;
        $tbl  = Werkdruk_KoersKaart_Plugin::tbl();
        $team = sanitize_text_field( $_GET['team'] ?? '' );

        $rows = $team !== ''
            ? $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$tbl` WHERE team = %s ORDER BY created_at DESC", $team ), ARRAY_A )
            : $wpdb->get_results( "SELECT * FROM `$tbl` ORDER BY team ASC, created_at DESC", ARRAY_A );

        $filename = 'werkdruk-koerskaart-' . date( 'Y-m-d' ) . ( $team ? '-' . sanitize_title( $team ) : '' ) . '.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );

        $out = fopen( 'php://output', 'w' );
        // BOM voor correcte weergave in Excel
        fwrite( $out, "\xEF\xBB\xBF" );
        fputcsv( $out, [ 'ID', 'Datum', 'Team', 'Naam', 'Niveau', 'Toelichting', 'Oorzaken', 'Oplossingen', 'Maatregelen' ], ';' );

        foreach ( $rows as $r ) {
            fputcsv( $out, [
                $r['id'],
                $r['created_at'],
                $r['team'],
                $r['name'],
                $r['wp_level'],
                $r['wp_note'],
                self::json_naar_tekst( $r['causes'] ),
                self::json_naar_tekst( $r['solutions'] ),
                self::maatregelen_naar_tekst( $r['measures'] ),
            ], ';' );
        }
        fclose( $out );
        exit;
    }

    /* ------------------------------------------------------------------ */*/
    /*  Verwijder entry                                                     */
    /* ------------------------------------------------------------------ */

    public static function delete_entry(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Geen toegang.', 403 );
        check_admin_referer( 'werkdruk_delete' );

        $id = absint( $_POST['entry_id'] ?? 0 );
        if ( $id > 0 ) {
            global $wpdb;
            $wpdb->delete( Werkdruk_KoersKaart_Plugin::tbl(), [ 'id' => $id ], [ '%d' ] );
        }

        wp_safe_redirect( add_query_arg( 'deleted', '1', admin_url( 'admin.php?page=werkdruk-koerskaart' ) ) );
        exit;
    }

    /* ------------------------------------------------------------------ */
    /*  Hulpfuncties voor CSV                                               */
    /* ------------------------------------------------------------------ */

    private static function json_naar_tekst( ?string $json ): string {
        $items = Werkdruk_KoersKaart_Plugin::decode( $json );
        return implode( ' | ', array_map( 'strval', $items ) );
    }

    private static function maatregelen_naar_tekst( ?string $json ): string {
        $items = Werkdruk_KoersKaart_Plugin::decode( $json );
        $parts = [];
        foreach ( $items as $m ) {
            if ( ! is_array( $m ) ) continue;
            $desc = $m['desc'] ?? '';
            $meta = array_filter( [ $m['cat'] ?? '', $m['effect'] ?? '', $m['feasibility'] ?? '' ] );
            $parts[] = $meta ? $desc . ' (' . implode( ', ', $meta ) . ')' : $desc;
        }
        return implode( ' | ', $parts );
    }
}
