<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Werkdruk_Overview {

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

    public static function render_pagina(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $team_filter = sanitize_text_field( $_GET['team'] ?? '' );

        echo '<div class="wrap"><h1>Werkdruk KoersKaart – Beheer</h1>';
        self::export_knop( $team_filter );
        // In het beheer laten we ALLES zien
        self::render( $team_filter, true ); 
        echo '</div>';
    }

    private static function export_knop( string $team ): void {
        $url = add_query_arg( [
            'action' => 'werkdruk_export_csv',
            'team'   => rawurlencode( $team ),
            '_wpnonce' => wp_create_nonce( 'werkdruk_export' ),
        ], admin_url( 'admin-post.php' ) );
        echo '<p><a href="' . esc_url( $url ) . '" class="button button-primary">↓ Exporteer naar Excel (CSV)</a></p>';
    }

    public static function render( string $team_filter = '', bool $is_backend = false ): void {
        global $wpdb;
        $tbl = Werkdruk_KoersKaart_Plugin::tbl();
        
        $query = "SELECT * FROM `$tbl`";
        if ( $team_filter !== '' ) {
            $query = $wpdb->prepare( "$query WHERE team = %s", $team_filter );
        }
        $query .= " ORDER BY created_at DESC";
        
        $results = $wpdb->get_results( $query );

        if ( empty( $results ) ) {
            echo '<p>Nog geen invoer gevonden.</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped" style="margin-top:20px;">';
        echo '<thead><tr>';
        echo '<th style="width:15%">Datum & Team</th>';
        echo '<th style="width:15%">Naam & Niveau</th>';
        echo '<th>Inhoud (Oorzaken & Oplossingen)</th>';
        if ( $is_backend ) echo '<th style="width:100px;">Actie</th>';
        echo '</tr></thead><tbody>';

        foreach ( $results as $row ) {
            echo '<tr>';
            // Kolom 1: Info
            echo '<td><strong>' . esc_html( date('d-m-Y', strtotime($row->created_at)) ) . '</strong><br><small>' . esc_html($row->team) . '</small></td>';
            // Kolom 2: Persoon
            echo '<td><strong>' . esc_html($row->name) . '</strong><br>' . esc_html($row->wp_level) . '</td>';
            // Kolom 3: De inhoud (Overzichtelijk onder elkaar)
            echo '<td>';
            echo '<strong>Oorzaken:</strong> ' . esc_html( self::json_naar_tekst($row->causes) ) . '<br>';
            echo '<strong>Oplossingen:</strong> ' . esc_html( self::json_naar_tekst($row->solutions) ) . '<br>';
            echo '<strong>Maatregelen:</strong> ' . esc_html( self::maatregelen_naar_tekst($row->measures) );
            echo '</td>';
            
            // Kolom 4: Alleen verwijderknop in de backend
            if ( $is_backend ) {
                $delete_url = wp_nonce_url( admin_url( 'admin-post.php?action=werkdruk_delete_entry&entry_id=' . $row->id ), 'werkdruk_delete' );
                echo '<td><a href="' . $delete_url . '" style="color:#a00;" onclick="return confirm(\'Weet je zeker dat je deze inzending wilt verwijderen?\')">Verwijderen</a></td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    public static function export_csv(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Geen toegang.' );
        check_admin_referer( 'werkdruk_export' );

        global $wpdb;
        $tbl  = Werkdruk_KoersKaart_Plugin::tbl();
        $team = sanitize_text_field( $_GET['team'] ?? '' );

        $rows = $team !== ''
            ? $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$tbl` WHERE team = %s ORDER BY created_at DESC", $team ), ARRAY_A )
            : $wpdb->get_results( "SELECT * FROM `$tbl` ORDER BY team ASC, created_at DESC", ARRAY_A );

        $filename = 'export-werkdruk-' . date('Y-m-d') . '.csv';
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        
        $out = fopen( 'php://output', 'w' );
        fwrite( $out, "\xEF\xBB\xBF" ); // UTF-8 BOM voor Excel
        fputcsv( $out, [ 'Datum', 'Team', 'Naam', 'Niveau', 'Oorzaken', 'Oplossingen', 'Maatregelen' ], ';' );

        foreach ( $rows as $r ) {
            fputcsv( $out, [
                $r['created_at'], $r['team'], $r['name'], $r['wp_level'],
                self::json_naar_tekst( $r['causes'] ),
                self::json_naar_tekst( $r['solutions'] ),
                self::maatregelen_naar_tekst( $r['measures'] )
            ], ';' );
        }
        fclose( $out );
        exit;
    }

    public static function delete_entry(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Geen toegang.' );
        $id = absint( $_REQUEST['entry_id'] ?? 0 );
        check_admin_referer( 'werkdruk_delete' );

        if ( $id > 0 ) {
            global $wpdb;
            $wpdb->delete( Werkdruk_KoersKaart_Plugin::tbl(), [ 'id' => $id ] );
        }
        wp_safe_redirect( admin_url( 'admin.php?page=werkdruk-koerskaart&deleted=1' ) );
        exit;
    }

    private static function json_naar_tekst( ?string $json ): string {
        $items = Werkdruk_KoersKaart_Plugin::decode( $json );
        return implode( ', ', array_map( 'strval', $items ) );
    }

    private static function maatregelen_naar_tekst( ?string $json ): string {
        $items = Werkdruk_KoersKaart_Plugin::decode( $json );
        $parts = [];
        foreach ( $items as $m ) {
            if ( is_array($m) ) $parts[] = $m['desc'] ?? '';
        }
        return implode( ', ', $parts );
    }
}
