<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Werkdruk_Overview {

    private const PER_PAGINA = 25;

    /* ------------------------------------------------------------------ */
    /*  Publieke ingang (shortcode + admin)                                 */
    /* ------------------------------------------------------------------ */

    public static function render( string $team_filter = '' ): void {
        $rows  = self::ophalen( $team_filter );
        $teams = self::alle_teams();
        self::styles();
        self::html( $rows, $teams, $team_filter );
    }

    /* ------------------------------------------------------------------ */
    /*  Data                                                                */
    /* ------------------------------------------------------------------ */

    private static function ophalen( string $team ): array {
        global $wpdb;
        $tbl = Werkdruk_KoersKaart_Plugin::tbl();

        if ( $team !== '' ) {
            return $wpdb->get_results(
                $wpdb->prepare( "SELECT * FROM `$tbl` WHERE team = %s ORDER BY created_at DESC", $team ),
                ARRAY_A
            ) ?: [];
        }
        return $wpdb->get_results(
            "SELECT * FROM `$tbl` ORDER BY team ASC, created_at DESC",
            ARRAY_A
        ) ?: [];
    }

    private static function alle_teams(): array {
        global $wpdb;
        $tbl = Werkdruk_KoersKaart_Plugin::tbl();
        return $wpdb->get_col( "SELECT DISTINCT team FROM `$tbl` ORDER BY team ASC" ) ?: [];
    }

    /* ------------------------------------------------------------------ */
    /*  CSS (éénmalig)                                                      */
    /* ------------------------------------------------------------------ */

    private static function styles(): void {
        static $done = false;
        if ( $done ) return;
        $done = true;
        // phpcs:disable
        echo <<<'CSS'
<style>
.wkov{max-width:960px;margin:40px auto 0;font-family:inherit;color:#1a1a1a}
.wkov h2{font-size:1.3em;color:#004080;margin:0 0 16px}
.wkov-filter{display:flex;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap}
.wkov-filter label{font-weight:600;font-size:.95em}
.wkov-filter select{padding:7px 10px;border:1px solid #b0bec5;border-radius:4px;font-size:.95em;font-family:inherit;background:#fafafa;color:#1a1a1a}
.wkov-table-wrap{overflow-x:auto}
.wkov table{width:100%;border-collapse:collapse;font-size:.9em}
.wkov th{background:#004080;color:#fff;padding:9px 12px;text-align:left;white-space:nowrap}
.wkov td{padding:9px 12px;border-bottom:1px solid #e8edf2;vertical-align:top}
.wkov tr:hover td{background:#f5f8fc}
.wkov-level{display:inline-block;padding:2px 10px;border-radius:12px;font-size:.83em;font-weight:700;white-space:nowrap}
.wkov-laag{background:#eaf7ea;color:#1a4d1a}
.wkov-gemiddeld{background:#fff8e1;color:#7a5800}
.wkov-hoog{background:#fdf2f2;color:#6b1a1a}
.wkov-nvt{background:#f0f0f0;color:#555}
.wkov-lijst{margin:0;padding:0 0 0 16px;line-height:1.6}
.wkov-mblok{margin-bottom:6px;padding:6px 8px;background:#f5f8fc;border-left:3px solid #004080;border-radius:2px;font-size:.88em;line-height:1.5}
.wkov-leeg{text-align:center;padding:32px;color:#888;font-style:italic}
@media(max-width:600px){.wkov th,.wkov td{padding:7px 8px}}
</style>
CSS;
        // phpcs:enable
    }

    /* ------------------------------------------------------------------ */
    /*  HTML                                                                */
    /* ------------------------------------------------------------------ */

    private static function html( array $rows, array $teams, string $actief ): void {
        echo '<div class="wkov">';
        echo '<h2>Overzicht inzendingen</h2>';
        self::filter_form( $teams, $actief );
        if ( empty( $rows ) ) {
            echo '<p class="wkov-leeg">Nog geen inzendingen' . ( $actief ? ' voor dit team' : '' ) . '.</p>';
        } else {
            self::tabel( $rows );
        }
        echo '</div>';
    }

    private static function filter_form( array $teams, string $actief ): void {
        $base = remove_query_arg( [ 'team', 'werkdruk' ] );
        echo '<form class="wkov-filter" method="get" action="' . esc_url( $base ) . '">';
        // Behoud overige query-params (bijv. pagina-ID).
        foreach ( $_GET as $k => $v ) {
            if ( in_array( $k, [ 'team', 'werkdruk' ], true ) ) continue;
            echo '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
        }
        echo '<label for="wkov-team-filter">Filter op team:</label>';
        echo '<select id="wkov-team-filter" name="team" onchange="this.form.submit()">';
        echo '<option value="">— alle teams —</option>';
        foreach ( $teams as $t ) {
            $sel = selected( $t, $actief, false );
            echo '<option value="' . esc_attr( $t ) . '"' . $sel . '>' . esc_html( $t ) . '</option>';
        }
        echo '</select>';
        echo '<noscript><button type="submit">Toon</button></noscript>';
        echo '</form>';
    }

    private static function tabel( array $rows ): void {
        echo '<div class="wkov-table-wrap"><table>';
        echo '<thead><tr>'
            . '<th>Datum</th><th>Team</th><th>Naam</th><th>Niveau</th>'
            . '<th>Oorzaken</th><th>Oplossingen</th><th>Maatregelen</th>'
            . '</tr></thead><tbody>';
        foreach ( $rows as $r ) {
            echo '<tr>';
            echo '<td>' . esc_html( date_i18n( 'd-m-Y H:i', strtotime( $r['created_at'] ) ) ) . '</td>';
            echo '<td>' . esc_html( $r['team'] ) . '</td>';
            echo '<td>' . esc_html( $r['name'] ) . '</td>';
            echo '<td>' . self::level_badge( $r['wp_level'] ) . '</td>';
            echo '<td>' . self::lijst( $r['causes'] ) . '</td>';
            echo '<td>' . self::lijst( $r['solutions'] ) . '</td>';
            echo '<td>' . self::maatregelen_cel( $r['measures'] ) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    /* ------------------------------------------------------------------ */
    /*  Cel-helpers                                                         */
    /* ------------------------------------------------------------------ */

    private static function level_badge( string $level ): string {
        $klasse = match ( $level ) {
            'laag'      => 'wkov-laag',
            'gemiddeld' => 'wkov-gemiddeld',
            'hoog'      => 'wkov-hoog',
            default     => 'wkov-nvt',
        };
        $label = match ( $level ) {
            'laag'      => 'Laag',
            'gemiddeld' => 'Gemiddeld',
            'hoog'      => 'Hoog',
            default     => 'n.v.t.',
        };
        return '<span class="wkov-level ' . $klasse . '">' . $label . '</span>';
    }

    private static function lijst( ?string $json ): string {
        $items = Werkdruk_KoersKaart_Plugin::decode( $json );
        if ( empty( $items ) ) return '<em>—</em>';
        $h = '<ul class="wkov-lijst">';
        foreach ( $items as $item ) {
            $h .= '<li>' . esc_html( $item ) . '</li>';
        }
        return $h . '</ul>';
    }

    private static function maatregelen_cel( ?string $json ): string {
        $items = Werkdruk_KoersKaart_Plugin::decode( $json );
        if ( empty( $items ) ) return '<em>—</em>';
        $h = '';
        foreach ( $items as $m ) {
            if ( ! is_array( $m ) ) continue;
            $meta = array_filter( [
                $m['cat']         ?? '',
                isset( $m['effect'] )      && $m['effect']      !== '' ? 'effect: '      . $m['effect']      : '',
                isset( $m['feasibility'] ) && $m['feasibility'] !== '' ? 'haalbaarheid: ' . $m['feasibility'] : '',
            ] );
            $h .= '<div class="wkov-mblok"><strong>' . esc_html( $m['desc'] ?? '' ) . '</strong>';
            if ( $meta ) {
                $h .= '<br><span style="color:#666;font-size:.85em">' . esc_html( implode( ' · ', $meta ) ) . '</span>';
            }
            $h .= '</div>';
        }
        return $h;
    }
}
