<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Werkdruk_Overview {

    /* ------------------------------------------------------------------ */
    /* Menu Registratie (vervangt class-admin.php)                         */
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
    /* Admin-overzichtspagina                                              */
    /* ------------------------------------------------------------------ */

    public static function render_pagina(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $team_filter = sanitize_text_field( $_GET['team'] ?? '' );

        echo '<div class="wrap">';
        echo '<h1>Werkdruk KoersKaart – Beheer</h1>';
        self::export_knop( $team_filter );
        self::render( $team_filter ); // Aanroep naar de eigen render functie
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
    /* Render Overzicht (Tabel weergave)                                   */
    /* ------------------------------------------------------------------ */

    public static function render( string $team_filter = '' ): void {
        global $wpdb;
        $tbl = Werkdruk_KoersKaart_Plugin::tbl();
        
        $query = "SELECT * FROM `$tbl`";
        if ( $team_filter !== '' ) {
            $query = $wpdb->prepare( "$query WHERE team = %s", $team_filter );
        }
        $query .= " ORDER BY created_at DESC";
        
        $results = $wpdb->get_results( $query );

        if ( empty( $results ) ) {
            echo '<p>Geen resultaten gevonden.</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Datum</th><th>Team</th><th>Naam</th><th>Niveau</th><th>Acties</th></tr></thead>';
        echo '<tbody>';
        foreach ( $results as $row ) {
            $delete_url = wp_nonce_url( admin_url( 'admin-post.php?action=werkdruk_delete_entry&entry_id=' . $row->id ), 'werkdruk_delete' );
            echo '<tr>';
            echo '<td>' . esc_html( $row->created_at ) . '</td>';
            echo '<td>' . esc_html( $row->team ) . '</td>';
            echo '<td>' . esc_html( $row->name ) . '</td>';
            echo '<td>' . esc_html( $row->wp_level ) . '</td>';
            echo '<td><a href="' . $delete_url . '" class="submitdelete" onclick="return confirm(\'Zeker weten?\')">Verwijderen</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    /* ------------------------------------------------------------------ */
    /* CSV-export Logica                                                   */
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

    public static function delete_entry(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Geen toegang.', 403 );
        
        $id = absint( $_REQUEST['entry_id'] ?? 0 );
        check_admin_referer( 'werkdruk_delete' );

        if ( $id > 0 ) {
            global $wpdb;
            $wpdb->delete( Werkdruk_KoersKaart_Plugin::tbl(), [ 'id' => $id ], [ '%d' ] );
        }

        wp_safe_redirect( add_query_arg( 'deleted', '1', admin_url( 'admin.php?page=werkdruk-koerskaart' ) ) );
        exit;
    }

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
