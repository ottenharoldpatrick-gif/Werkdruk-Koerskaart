<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Werkdruk_Overview {

    /* ------------------------------------------------------------------ */
    /* Menu Registratie                                                  */
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

    public static function render_pagina(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $team_filter = sanitize_text_field( $_GET['team'] ?? '' );

        echo '<div class="wrap"><h1>Werkdruk KoersKaart – Beheer</h1>';
        self::export_knop( $team_filter );
        // In de backend tonen we de tabel MET verwijder-optie
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

    /* ------------------------------------------------------------------ */
    /* De Tabel (Zowel voor site als dashboard)                          */
    /* ------------------------------------------------------------------ */

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
            echo '<p>Nog geen inzendingen gevonden.</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped" style="margin-top:20px; border: 1px solid #ccc;">';
        echo '<thead><tr>';
        echo '<th style="width:120px;">Datum</th>';
        echo '<th style="width:150px;">Wie?</th>';
        echo '<th>Inhoud (Oorzaken, Oplossingen, Maatregelen)</th>';
        if ( $is_backend ) {
            echo '<th style="width:100px;">Actie</th>';
        }
        echo '</tr></thead><tbody>';

        foreach ( $results as $row ) {
            echo '<tr>';
            echo '<td>' . esc_html( date('d-m-Y', strtotime($row->created_at)) ) . '<br><small>Team: ' . esc_html($row->team) . '</small></td>';
            echo '<td><strong>' . esc_html($row->name) . '</strong><br><small>' . esc_html($row->wp_level) . '</small></td>';
            echo '<td>';
            echo '<strong>Oorzaken:</strong> ' . esc_html( self::json_naar_tekst($row->causes) ) . '<br><br>';
            echo '<strong>Oplossingen:</strong> ' . esc_html( self::json_naar_tekst($row->solutions) ) . '<br><br>';
            echo '<strong>Maatregelen:</strong> ' . esc_html( self::maatregelen_naar_tekst($row->measures) );
            echo '</td>';
            
            if ( $is_backend ) {
                $delete_url = wp_nonce_url( admin_url( 'admin-post.php?action=werkdruk_delete_entry&entry_id=' . $row->id ), 'werkdruk_delete' );
                echo '<td><a href="' . $delete_url . '" style="color:#a00; font-weight:bold;" onclick="return confirm(\'Zeker weten?\')">Verwijderen</a></td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    /* ------------------------------------------------------------------ */
    /* Export naar CSV (Excel) - Nu met aparte kolommen per maatregel      */
    /* ------------------------------------------------------------------ */

    public static function export_csv(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Geen toegang.' );
        check_admin_referer( 'werkdruk_export' );

        global $wpdb;
        $tbl  = Werkdruk_KoersKaart_Plugin::tbl();
        $team = sanitize_text_field( $_GET['team'] ?? '' );

        $rows = $team !== ''
            ? $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$tbl` WHERE team = %s ORDER BY created_at DESC", $team ), ARRAY_A )
            : $wpdb->get_results( "SELECT * FROM `$tbl` ORDER BY team ASC, created_at DESC", ARRAY_A );

        if ( empty( $rows ) ) wp_die( 'Geen gegevens om te exporteren.' );

        // 1. Bepaal het maximale aantal maatregelen voor de kolomkoppen
        $max_measures = 0;
        foreach ( $rows as $r ) {
            $m = Werkdruk_KoersKaart_Plugin::decode( $r['measures'] );
            $max_measures = max( $max_measures, count( $m ) );
        }

        $filename = 'export-werkdruk-' . date('Y-m-d') . '.csv';
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        
        $out = fopen( 'php://output', 'w' );
        fwrite( $out, "\xEF\xBB\xBF" ); // UTF-8 BOM voor Excel (zorgt dat vreemde tekens goed getoond worden)

        // 2. Maak de kopregel
        $header = [ 'Datum', 'Team', 'Naam', 'Niveau', 'Oorzaken', 'Oplossingen' ];
        for ( $i = 1; $i <= $max_measures; $i++ ) {
            $header[] = "Maatregel $i";
        }
        fputcsv( $out, $header, ';' );

        // 3. Vul de rijen
        foreach ( $rows as $r ) {
            $measures = Werkdruk_KoersKaart_Plugin::decode( $r['measures'] );
            
            $data_row = [
                $r['created_at'], 
                $r['team'], 
                $r['name'], 
                $r['wp_level'],
                self::json_naar_tekst( $r['causes'] ),
                self::json_naar_tekst( $r['solutions'] )
            ];

            // Voeg elke maatregel toe aan een eigen kolom
            for ( $i = 0; $i < $max_measures; $i++ ) {
                if ( isset( $measures[$i] ) ) {
                    $m = $measures[$i];
                    $meta = array_filter( [ $m['cat'] ?? '', $m['effect'] ?? '', $m['feasibility'] ?? '' ] );
                    // We zetten de details tussen haakjes achter de omschrijving
                    $data_row[] = $m['desc'] . ( $meta ? ' (' . implode(', ', $meta) . ')' : '' );
                } else {
                    $data_row[] = ''; // Lege cel als deze persoon minder maatregelen had
                }
            }
            fputcsv( $out, $data_row, ';' );
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

    /* ------------------------------------------------------------------ */
    /* Hulpfuncties                                                      */
    /* ------------------------------------------------------------------ */

    private static function json_naar_tekst( ?string $json ): string {
        $items = Werkdruk_KoersKaart_Plugin::decode( $json );
        return implode( ' | ', array_map( 'strval', $items ) );
    }

    private static function maatregelen_naar_tekst( ?string $json ): string {
        $items = Werkdruk_KoersKaart_Plugin::decode( $json );
        $parts = [];
        foreach ( $items as $m ) {
            if ( is_array($m) && !empty($m['desc']) ) {
                $meta = array_filter( [ $m['cat'] ?? '', $m['effect'] ?? '', $m['feasibility'] ?? '' ] );
                $parts[] = $m['desc'] . ( $meta ? ' (' . implode(', ', $meta) . ')' : '' );
            }
        }
        return implode( ' | ', $parts );
    }
}
