<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Werkdruk_Form {

    private static array $oorzaken = [
        'Hoge lesbelasting / veel lesuren',
        'Veel administratieve taken',
        'Veel zorgleerlingen in de klas',
        'Tekort aan collega\'s of invalkrachten',
        'Onduidelijke taakverdeling binnen het team',
        'Te weinig voor- en nawerktijd ingeroosterd',
        'Grote klassen',
        'Veel oudercontacten en tien-minutengesprekken',
        'Veel vergaderingen en overleg',
        'Onvoldoende ICT-middelen of ondersteuning',
        'Mentoraatstaken te zwaar',
        'Te weinig autonomie in eigen werk',
        'Gedragsproblemen van leerlingen',
        'Pauze-surveillance zonder hersteltijd erna',
    ];

    private static array $maatregelen = [
        'Externen inzetten voor surveillance tijdens toetsweken',
        'Leerlingenbalie oprichten voor psychosociale ondersteuning',
        'Structuurklas inrichten voor leerlingen met extra behoeften',
        'Extra OLC-medewerkers inzetten voor begeleiding',
        'Extra onderwijsondersteuning inkopen voor klassen met zorgleerlingen',
        'Investeren in ICT-middelen en thuiswerkapparatuur',
        'Werktelefoons aanschaffen voor betere werk-privébalans',
        'Tijdelijk kleinere klassen organiseren',
        'Aantal lessen per FTE verminderen',
        'Extra studiedagen of teamtweedaagse plannen',
        'Mentoruren uitbreiden',
        'Vrij roosteren na pauze-surveillance',
        'Wellness- of fitnessruimte inrichten voor personeel',
        'Startende docenten ontzien van mentoraat',
        'Extra verzuimcoördinator of conciërge aanstellen',
        'Collegiale lesbezoeken plannen als ontwikkeltijd',
        'Faciliteiten verbeteren (kantine, kopieerapparaten, meubilair)',
    ];

    /* ------------------------------------------------------------------ */
    /*  Publieke ingang                                                     */
    /* ------------------------------------------------------------------ */

    public static function render( string $team_preset, string $status, array $errors ): void {
        self::styles();
        self::notices( $status, $errors );
        self::form( $team_preset );
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
.wk{max-width:780px;margin:0 auto;font-family:inherit;color:#1a1a1a}
.wk-notice{padding:14px 18px;border-radius:4px;margin-bottom:24px;font-size:.97em;line-height:1.5}
.wk-ok{background:#eaf7ea;border-left:4px solid #2e8b2e;color:#1a4d1a}
.wk-err{background:#fdf2f2;border-left:4px solid #c0392b;color:#6b1a1a}
.wk-err ul{margin:8px 0 0 18px;padding:0}
.wk-stap{background:#fff;border:1px solid #dde3ea;border-radius:6px;padding:28px 32px;margin-bottom:28px}
.wk-kop{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.wk-nr{background:#004080;color:#fff;font-size:.78em;font-weight:700;padding:3px 10px;border-radius:20px;white-space:nowrap}
.wk-titel{margin:0;font-size:1.18em;font-weight:700;color:#004080;flex:1}
.wk-intro{margin:0 0 20px;color:#444;line-height:1.6}
.wk-lbl{display:block;font-weight:600;margin:16px 0 5px;color:#1a1a1a}
.wk-hint{font-weight:400;color:#666;font-size:.9em}
.wk-req{color:#c0392b}
.wk-inp{display:block;width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid #b0bec5;border-radius:4px;font-size:1em;font-family:inherit;color:#1a1a1a;background:#fafafa;transition:border-color .15s}
.wk-inp:focus{outline:none;border-color:#004080;background:#fff}
textarea.wk-inp{resize:vertical;min-height:72px}
.wk-sel{display:block;width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid #b0bec5;border-radius:4px;font-size:1em;font-family:inherit;background:#fafafa;color:#1a1a1a}
.wk-sel:focus{outline:none;border-color:#004080}
.wk-fs{border:none;padding:0;margin:8px 0 0}
.wk-radio{display:flex;align-items:flex-start;gap:8px;margin-bottom:8px;cursor:pointer;line-height:1.5}
.wk-radio input{margin-top:3px;flex-shrink:0}
.wk-ibtn{background:none;border:2px solid #004080;color:#004080;border-radius:50%;width:28px;height:28px;font-size:1em;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;padding:0;line-height:1}
.wk-ibtn:hover{background:#004080;color:#fff}
.wk-popup{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px}
.wk-popup[hidden]{display:none}
.wk-popup-kader{background:#fff;border-radius:8px;padding:32px 36px;max-width:560px;width:100%;position:relative;box-shadow:0 8px 32px rgba(0,0,0,.18);max-height:85vh;overflow-y:auto}
.wk-popup-kader h3{margin:0 0 14px;color:#004080;font-size:1.1em}
.wk-popup-kader p{margin:0 0 12px;line-height:1.65;color:#333}
.wk-sluit{position:absolute;top:14px;right:16px;background:none;border:none;font-size:1.5em;cursor:pointer;color:#666;line-height:1;padding:0}
.wk-sluit:hover{color:#c0392b}
.wk-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px}
.wk-chip{background:#eef3fa;border:1px solid #b0c4de;color:#004080;border-radius:20px;padding:5px 14px;font-size:.88em;cursor:pointer;transition:background .15s;font-family:inherit}
.wk-chip:hover{background:#004080;color:#fff;border-color:#004080}
.wk-lijst{display:flex;flex-direction:column;gap:10px;margin-bottom:10px}
.wk-item{display:flex;gap:8px;align-items:flex-start}
.wk-item .wk-inp{flex:1}
.wk-del{background:none;border:1px solid #b0bec5;border-radius:4px;color:#666;cursor:pointer;padding:6px 10px;font-size:.9em;margin-top:2px;flex-shrink:0;font-family:inherit}
.wk-del:hover{background:#fdf2f2;border-color:#c0392b;color:#c0392b}
.wk-mblok{border:1px solid #dde3ea;border-radius:5px;padding:16px;margin-bottom:12px;background:#fafbfc}
.wk-mkop{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.wk-mkop span{font-weight:700;color:#004080;font-size:.95em}
.wk-btn{display:inline-block;padding:10px 20px;border-radius:4px;font-size:.95em;font-family:inherit;cursor:pointer;border:none;font-weight:600}
.wk-btn-add{background:#eef3fa;color:#004080;border:1px dashed #004080;margin-bottom:6px}
.wk-btn-add:hover{background:#d6e4f7}
.wk-btn-submit{background:#004080;color:#fff;font-size:1.05em;padding:13px 28px}
.wk-btn-submit:hover{background:#00306a}
.wk-submit{margin-top:10px;margin-bottom:40px}
@media(max-width:600px){.wk-stap{padding:18px 14px}.wk-popup-kader{padding:22px 16px}}
</style>
CSS;
        // phpcs:enable
    }

    /* ------------------------------------------------------------------ */
    /*  Meldingen                                                           */
    /* ------------------------------------------------------------------ */

    private static function notices( string $status, array $errors ): void {
        if ( $status === 'ok' ) {
            echo '<div class="wk-notice wk-ok">✓ Je bijdrage is succesvol opgeslagen. Bedankt!</div>';
        }
        if ( $status === 'error' && ! empty( $errors ) ) {
            echo '<div class="wk-notice wk-err"><strong>Let op:</strong><ul>';
            foreach ( $errors as $e ) {
                echo '<li>' . esc_html( $e ) . '</li>';
            }
            echo '</ul></div>';
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Formulier                                                           */
    /* ------------------------------------------------------------------ */

    private static function form( string $team_preset ): void {
        $nonce = wp_nonce_field( 'werkdruk_submit', 'werkdruk_nonce', true, false );
        echo '<div class="wk">';
        echo '<form method="post" novalidate>' . $nonce;
        echo '<input type="hidden" name="werkdruk_submit" value="1">';
        echo self::stap( 1, 'Startpunt',          self::stap1_body( $team_preset ), self::popup1() );
        echo self::stap( 2, 'Ervaren werkdruk',    self::stap2_body(),               self::popup2() );
        echo self::stap( 3, 'Oorzaken',            self::stap3_body(),               self::popup3() );
        echo self::stap( 4, 'Oplossingsrichtingen', self::stap4_body(),              self::popup4() );
        echo self::stap( 5, 'Maatregelen',          self::stap5_body(),              self::popup5() );
        echo '<div class="wk-submit">';
        echo '<button type="submit" class="wk-btn wk-btn-submit">Verstuur mijn bijdrage aan het werkdrukplan</button>';
        echo '</div></form></div>';
        self::scripts();
    }

    /* ------------------------------------------------------------------ */
    /*  Stap-wrapper                                                        */
    /* ------------------------------------------------------------------ */

    private static function stap( int $n, string $titel, string $body, string $popup ): string {
        $id = 'wk-pop-' . $n;
        return '<section class="wk-stap">'
            . '<div class="wk-kop">'
            . '<span class="wk-nr">Stap ' . $n . '</span>'
            . '<h2 class="wk-titel">' . esc_html( $titel ) . '</h2>'
            . '<button type="button" class="wk-ibtn" data-popup="' . $id . '" aria-label="Meer informatie stap ' . $n . '">&#9432;</button>'
            . '</div>'
            . '<div class="wk-popup" id="' . $id . '" hidden>'
            . '<div class="wk-popup-kader">'
            . '<button type="button" class="wk-sluit" aria-label="Sluiten">&times;</button>'
            . $popup
            . '</div></div>'
            . $body
            . '</section>';
    }

    /* ------------------------------------------------------------------ */
    /*  Popup-teksten                                                       */
    /* ------------------------------------------------------------------ */

    private static function popup1(): string {
        return '<h3>Stap 1 – Startpunt</h3>'
            . '<p>In de CAO VO (hoofdstuk 8.7) is afgesproken dat er structureel <strong>300 miljoen euro per jaar</strong> beschikbaar is voor werkdrukverlichting.</p>'
            . '<p>Werknemers bepalen <strong>gezamenlijk</strong> waaraan de collectieve middelen worden besteed. Dit formulier is stap één in dat proces.</p>'
            . '<p>Iedereen vult dit formulier individueel in. De uitkomsten zijn direct voor het hele team zichtbaar.</p>';
    }

    private static function popup2(): string {
        return '<h3>Stap 2 – Ervaren werkdruk</h3>'
            . '<p><strong>Werkdruk</strong> ontstaat wanneer de taakeisen hoger zijn dan wat jij in de beschikbare tijd kunt verwerken.</p>'
            . '<p><strong>Laag</strong> – Je voert je werk comfortabel uit binnen de beschikbare tijd.<br>'
            . '<strong>Gemiddeld</strong> – Je ervaart regelmatig tijdsdruk, maar het is beheersbaar.<br>'
            . '<strong>Hoog</strong> – Je ervaart structureel te veel taken voor de beschikbare tijd.</p>';
    }

    private static function popup3(): string {
        return '<h3>Stap 3 – Oorzaken</h3>'
            . '<p>Benoem oorzaken zo <strong>concreet</strong> mogelijk. Klik op een suggestie om die over te nemen.</p>';
    }

    private static function popup4(): string {
        return '<h3>Stap 4 – Oplossingsrichtingen</h3>'
            . '<p>Denk breed en vrij. Het gaat om de <strong>richting</strong> van mogelijke oplossingen. In stap 5 werk je deze uit tot concrete maatregelen.</p>';
    }

    private static function popup5(): string {
        return '<h3>Stap 5 – Maatregelen</h3>'
            . '<p>Formuleer concrete maatregelen. Gebruik de suggesties als startpunt.</p>'
            . '<p><strong>Categorie</strong> – Individueel (eigen werkdrukbudget) of Collectief (schoolbudget)?</p>'
            . '<p><strong>Effect</strong> – ++ zeer groot &nbsp;+ groot &nbsp;- beperkt &nbsp;-- zeer beperkt</p>'
            . '<p><strong>Haalbaarheid</strong> – ++ zeer goed &nbsp;+ goed &nbsp;- moeilijk &nbsp;-- nauwelijks</p>';
    }

    /* ------------------------------------------------------------------ */
    /*  Stap-inhoud                                                         */
    /* ------------------------------------------------------------------ */

    private static function stap1_body( string $team_preset ): string {
        $t = esc_attr( $team_preset );
        return '<p class="wk-intro">Vul jouw naam en de naam van het team in.</p>'
            . '<label class="wk-lbl" for="wk_team">Team / sectie <span class="wk-req">*</span></label>'
            . '<input class="wk-inp" type="text" id="wk_team" name="team" value="' . $t . '" placeholder="bijv. Economie bovenbouw" required>'
            . '<label class="wk-lbl" for="wk_name">Jouw naam <span class="wk-req">*</span></label>'
            . '<input class="wk-inp" type="text" id="wk_name" name="name" placeholder="bijv. Jan de Vries" required>';
    }

    private static function stap2_body(): string {
        return '<p class="wk-intro">Geef aan hoeveel werkdruk jij op dit moment ervaart. Denk aan de afgelopen weken als richtlijn.</p>'
            . '<fieldset class="wk-fs"><legend class="wk-lbl">Werkdrukniveau <span class="wk-req">*</span></legend>'
            . self::radio( 'wp_level', 'laag',     'Laag – ik ervaar de werkdruk als goed te beheren' )
            . self::radio( 'wp_level', 'gemiddeld', 'Gemiddeld – ik ervaar regelmatig tijdsdruk' )
            . self::radio( 'wp_level', 'hoog',     'Hoog – ik ervaar structureel te veel taken voor de beschikbare tijd' )
            . self::radio( 'wp_level', 'nvt',      'n.v.t.' )
            . '</fieldset>'
            . '<label class="wk-lbl" for="wk_note">Korte toelichting <span class="wk-hint">(optioneel)</span></label>'
            . '<textarea class="wk-inp" id="wk_note" name="wp_note" rows="3" placeholder="Wat maakt dat jij dit niveau ervaart?"></textarea>';
    }

    private static function stap3_body(): string {
        return '<p class="wk-intro">Wat zijn voor jou de belangrijkste oorzaken van werkdruk? Wees zo concreet mogelijk. Klik op een suggestie of typ zelf.</p>'
            . self::chips( self::$oorzaken, 'wk-causes', 'causes[]', 'Oorzaak – beschrijf zo concreet mogelijk' )
            . '<div class="wk-lijst" id="wk-causes">' . self::dyn_item( 'causes[]', 'Oorzaak – beschrijf zo concreet mogelijk' ) . '</div>'
            . '<button type="button" class="wk-btn wk-btn-add" data-lijst="wk-causes" data-name="causes[]" data-ph="Oorzaak – beschrijf zo concreet mogelijk">+ Oorzaak toevoegen</button>';
    }

    private static function stap4_body(): string {
        return '<p class="wk-intro">Bedenk oplossingsrichtingen om de werkdruk te verlagen. Denk breed en vrij; je werkt ze in stap 5 uit tot concrete maatregelen.</p>'
            . '<div class="wk-lijst" id="wk-solutions">' . self::dyn_item( 'solutions[]', 'Oplossingsrichting – bijv. minder administratieve taken' ) . '</div>'
            . '<button type="button" class="wk-btn wk-btn-add" data-lijst="wk-solutions" data-name="solutions[]" data-ph="Oplossingsrichting – bijv. minder administratieve taken">+ Oplossingsrichting toevoegen</button>';
    }

    private static function stap5_body(): string {
        return '<p class="wk-intro">Formuleer concrete maatregelen die de werkdruk kunnen verlagen. Klik op een suggestie of typ zelf.</p>'
            . self::chips( self::$maatregelen, '', '', '' , true )
            . '<div class="wk-lijst" id="wk-measures">' . self::maatregel_blok( 0 ) . '</div>'
            . '<button type="button" class="wk-btn wk-btn-add" id="wk-add-maatregel">+ Maatregel toevoegen</button>';
    }

    /* ------------------------------------------------------------------ */
    /*  Herbruikbare HTML-bouwstenen                                        */
    /* ------------------------------------------------------------------ */

    private static function radio( string $name, string $val, string $label ): string {
        return '<label class="wk-radio">'
            . '<input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '" required> '
            . esc_html( $label )
            . '</label>';
    }

    private static function chips( array $items, string $target, string $name, string $ph, bool $maatregel = false ): string {
        $h = '<div class="wk-chips">';
        foreach ( $items as $item ) {
            $cls  = $maatregel ? 'wk-chip wk-chip-m' : 'wk-chip';
            $data = $maatregel
                ? 'data-value="' . esc_attr( $item ) . '"'
                : 'data-target="' . esc_attr( $target ) . '" data-name="' . esc_attr( $name ) . '" data-value="' . esc_attr( $item ) . '"';
            $h .= '<button type="button" class="' . $cls . '" ' . $data . '>' . esc_html( $item ) . '</button>';
        }
        return $h . '</div>';
    }

    private static function dyn_item( string $name, string $ph ): string {
        return '<div class="wk-item">'
            . '<textarea class="wk-inp" name="' . esc_attr( $name ) . '" rows="2" placeholder="' . esc_attr( $ph ) . '"></textarea>'
            . '<button type="button" class="wk-del" aria-label="Verwijder">&#10005;</button>'
            . '</div>';
    }

    private static function select( string $name, array $opties ): string {
        $h = '<select class="wk-sel" name="' . esc_attr( $name ) . '"><option value="">-- kies --</option>';
        foreach ( $opties as $val => $label ) {
            $h .= '<option value="' . esc_attr( $val ) . '">' . esc_html( $label ) . '</option>';
        }
        return $h . '</select>';
    }

    public static function maatregel_blok( int $i ): string {
        $effect = [
            '++'  => '++ Zeer groot effect',
            '+'   => '+  Groot effect',
            '-'   => '-  Beperkt effect',
            '--'  => '-- Zeer beperkt effect',
            'nvt' => 'n.v.t.',
        ];
        $haalbaar = [
            '++'  => '++ Zeer goed haalbaar',
            '+'   => '+  Goed haalbaar',
            '-'   => '-  Moeilijk haalbaar',
            '--'  => '-- Nauwelijks haalbaar',
            'nvt' => 'n.v.t.',
        ];
        $cat = [
            'individueel' => 'Individueel (eigen werkdrukbudget)',
            'collectief'  => 'Collectief (schoolbudget)',
            'nvt'         => 'n.v.t.',
        ];
        return '<div class="wk-mblok">'
            . '<div class="wk-mkop"><span>Maatregel ' . ( $i + 1 ) . '</span>'
            . '<button type="button" class="wk-del" aria-label="Verwijder">&#10005;</button></div>'
            . '<label class="wk-lbl">Omschrijving <span class="wk-req">*</span></label>'
            . '<textarea class="wk-inp wk-mdesc" name="measures[' . $i . '][desc]" rows="2" placeholder="Beschrijf de maatregel concreet"></textarea>'
            . '<label class="wk-lbl">Categorie</label>'
            . self::select( 'measures[' . $i . '][cat]', $cat )
            . '<label class="wk-lbl">Effect</label>'
            . self::select( 'measures[' . $i . '][effect]', $effect )
            . '<label class="wk-lbl">Haalbaarheid</label>'
            . self::select( 'measures[' . $i . '][feasibility]', $haalbaar )
            . '</div>';
    }

    /* ------------------------------------------------------------------ */
    /*  JavaScript                                                          */
    /* ------------------------------------------------------------------ */

    private static function scripts(): void {
        // phpcs:disable
        echo <<<'JS'
<script>
(function () {
    'use strict';

    /* --- Popups --- */
    document.querySelectorAll('.wk-ibtn').forEach(btn => {
        btn.addEventListener('click', () => {
            const p = document.getElementById(btn.dataset.popup);
            if (p) p.hidden = false;
        });
    });
    document.addEventListener('click', e => {
        if (e.target.classList.contains('wk-sluit') || e.target.classList.contains('wk-popup'))
            e.target.closest('.wk-popup').hidden = true;
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape')
            document.querySelectorAll('.wk-popup:not([hidden])').forEach(p => p.hidden = true);
    });

    /* --- Dynamische rijen (oorzaken / oplossingen) --- */
    document.querySelectorAll('.wk-btn-add:not(#wk-add-maatregel)').forEach(btn => {
        btn.addEventListener('click', () => addItem(btn.dataset.lijst, btn.dataset.name, btn.dataset.ph));
    });

    function addItem(lijstId, name, ph) {
        const lijst = document.getElementById(lijstId);
        if (!lijst) return;
        const div = document.createElement('div');
        div.className = 'wk-item';
        div.innerHTML = `<textarea class="wk-inp" name="${name}" rows="2" placeholder="${ph}"></textarea>`
            + `<button type="button" class="wk-del" aria-label="Verwijder">&#10005;</button>`;
        lijst.appendChild(div);
        div.querySelector('textarea').focus();
    }

    /* --- Verwijder-knoppen (delegatie) --- */
    document.addEventListener('click', e => {
        if (!e.target.classList.contains('wk-del')) return;
        const item = e.target.closest('.wk-item');
        const blok = e.target.closest('.wk-mblok');
        if (item) {
            const lijst = item.parentElement;
            if (lijst.querySelectorAll('.wk-item').length > 1) item.remove();
            else item.querySelector('textarea').value = '';
        }
        if (blok) {
            const lijst = blok.parentElement;
            if (lijst.querySelectorAll('.wk-mblok').length > 1) { blok.remove(); hernummer(); }
            else blok.querySelectorAll('textarea, select').forEach(el => el.value = '');
        }
    });

    /* --- Chips (oorzaken) --- */
    document.querySelectorAll('.wk-chip:not(.wk-chip-m)').forEach(chip => {
        chip.addEventListener('click', () => {
            const lijst  = document.getElementById(chip.dataset.target);
            const value  = chip.dataset.value;
            if (!lijst) return;
            const leeg = [...lijst.querySelectorAll('textarea')].find(ta => !ta.value.trim());
            if (leeg) leeg.value = value;
            else {
                const div = document.createElement('div');
                div.className = 'wk-item';
                div.innerHTML = `<textarea class="wk-inp" name="${chip.dataset.name}" rows="2"></textarea>`
                    + `<button type="button" class="wk-del" aria-label="Verwijder">&#10005;</button>`;
                lijst.appendChild(div);
                div.querySelector('textarea').value = value;
            }
        });
    });

    /* --- Chips (maatregelen) --- */
    document.querySelectorAll('.wk-chip-m').forEach(chip => {
        chip.addEventListener('click', () => {
            const lijst = document.getElementById('wk-measures');
            if (!lijst) return;
            const leeg = [...lijst.querySelectorAll('.wk-mdesc')].find(ta => !ta.value.trim());
            if (leeg) leeg.value = chip.dataset.value;
            else addMaatregel(chip.dataset.value);
        });
    });

    /* --- Maatregel toevoegen --- */
    document.getElementById('wk-add-maatregel')
        ?.addEventListener('click', () => addMaatregel(''));

    function addMaatregel(desc) {
        const lijst = document.getElementById('wk-measures');
        if (!lijst) return;
        const i = lijst.querySelectorAll('.wk-mblok').length;
        const div = document.createElement('div');
        // Haal HTML-template op via PHP-echo in data-attribuut is niet nodig:
        // we bouwen het blok identiek aan PHP's maatregel_blok().
        div.innerHTML = maatregelHTML(i);
        lijst.appendChild(div);
        if (desc) div.querySelector('.wk-mdesc').value = desc;
        div.querySelector('.wk-mdesc').focus();
    }

    function maatregelHTML(i) {
        const effectOpts = [['++','++ Zeer groot effect'],['+',' +  Groot effect'],['-','- Beperkt effect'],['--','-- Zeer beperkt effect'],['nvt','n.v.t.']];
        const haalOpts   = [['++','++ Zeer goed haalbaar'],['+',' +  Goed haalbaar'],['-','- Moeilijk haalbaar'],['--','-- Nauwelijks haalbaar'],['nvt','n.v.t.']];
        const catOpts    = [['individueel','Individueel (eigen werkdrukbudget)'],['collectief','Collectief (schoolbudget)'],['nvt','n.v.t.']];
        const sel = (name, opts) => `<select class="wk-sel" name="${name}"><option value="">-- kies --</option>`
            + opts.map(([v,l]) => `<option value="${v}">${l}</option>`).join('') + '</select>';
        return `<div class="wk-mblok">
            <div class="wk-mkop"><span>Maatregel ${i+1}</span>
            <button type="button" class="wk-del" aria-label="Verwijder">&#10005;</button></div>
            <label class="wk-lbl">Omschrijving <span class="wk-req">*</span></label>
            <textarea class="wk-inp wk-mdesc" name="measures[${i}][desc]" rows="2" placeholder="Beschrijf de maatregel concreet"></textarea>
            <label class="wk-lbl">Categorie</label>${sel(`measures[${i}][cat]`,catOpts)}
            <label class="wk-lbl">Effect</label>${sel(`measures[${i}][effect]`,effectOpts)}
            <label class="wk-lbl">Haalbaarheid</label>${sel(`measures[${i}][feasibility]`,haalOpts)}
            </div>`;
    }

    function hernummer() {
        const lijst = document.getElementById('wk-measures');
        if (!lijst) return;
        lijst.querySelectorAll('.wk-mblok').forEach((blok, i) => {
            const kop = blok.querySelector('.wk-mkop span');
            if (kop) kop.textContent = `Maatregel ${i + 1}`;
            blok.querySelectorAll('[name]').forEach(el => {
                el.name = el.name.replace(/measures\[\d+\]/g, `measures[${i}]`);
            });
        });
    }
})();
</script>
JS;
        // phpcs:enable
    }
}
