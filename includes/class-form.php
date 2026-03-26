<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Werkdruk_Form {

    private static $oorzaken = array(
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
    );

    private static $maatregelen = array(
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
    );

    public static function render( $team_preset, $status, $errors ) {
        self::render_styles();
        self::render_notices( $status, $errors );
        self::render_formulier( $team_preset );
    }

    private static function render_styles() {
        static $printed = false;
        if ( $printed ) return;
        $printed = true;
        echo '<style>
        .wk-wrap{max-width:780px;margin:0 auto;font-family:inherit;color:#1a1a1a;}
        .wk-notice{padding:14px 18px;border-radius:4px;margin-bottom:24px;font-size:.97em;line-height:1.5;}
        .wk-notice--ok{background:#eaf7ea;border-left:4px solid #2e8b2e;color:#1a4d1a;}
        .wk-notice--error{background:#fdf2f2;border-left:4px solid #c0392b;color:#6b1a1a;}
        .wk-notice--error ul{margin:8px 0 0 18px;padding:0;}
        .wk-form{width:100%;}
        .wk-stap{background:#fff;border:1px solid #dde3ea;border-radius:6px;padding:28px 32px;margin-bottom:28px;}
        .wk-stap__kop{display:flex;align-items:center;gap:12px;margin-bottom:12px;}
        .wk-stap__nr{background:#004080;color:#fff;font-size:.78em;font-weight:700;padding:3px 10px;border-radius:20px;white-space:nowrap;}
        .wk-stap__titel{margin:0;font-size:1.18em;font-weight:700;color:#004080;flex:1;}
        .wk-stap__intro{margin:0 0 20px 0;color:#444;line-height:1.6;}
        .wk-label{display:block;font-weight:600;margin:16px 0 5px 0;color:#1a1a1a;}
        .wk-hint{font-weight:400;color:#666;font-size:.9em;}
        .wk-req{color:#c0392b;}
        .wk-input{display:block;width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid #b0bec5;border-radius:4px;font-size:1em;font-family:inherit;color:#1a1a1a;background:#fafafa;transition:border-color .15s;}
        .wk-input:focus{outline:none;border-color:#004080;background:#fff;}
        textarea.wk-input{resize:vertical;min-height:72px;}
        .wk-fieldset{border:none;padding:0;margin:8px 0 0 0;}
        .wk-radio{display:flex;align-items:flex-start;gap:8px;margin-bottom:8px;cursor:pointer;line-height:1.5;}
        .wk-radio input[type="radio"]{margin-top:3px;flex-shrink:0;}
        .wk-info-btn{background:none;border:2px solid #004080;color:#004080;border-radius:50%;width:28px;height:28px;font-size:1em;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;padding:0;line-height:1;}
        .wk-info-btn:hover{background:#004080;color:#fff;}
        .wk-popup{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;}
        .wk-popup[hidden]{display:none;}
        .wk-popup__kader{background:#fff;border-radius:8px;padding:32px 36px;max-width:560px;width:100%;position:relative;box-shadow:0 8px 32px rgba(0,0,0,.18);max-height:85vh;overflow-y:auto;}
        .wk-popup__kader h3{margin:0 0 14px 0;color:#004080;font-size:1.1em;}
        .wk-popup__kader p{margin:0 0 12px 0;line-height:1.65;color:#333;}
        .wk-popup__sluit{position:absolute;top:14px;right:16px;background:none;border:none;font-size:1.5em;cursor:pointer;color:#666;line-height:1;padding:0;}
        .wk-popup__sluit:hover{color:#c0392b;}
        .wk-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;}
        .wk-chip{background:#eef3fa;border:1px solid #b0c4de;color:#004080;border-radius:20px;padding:5px 14px;font-size:.88em;cursor:pointer;transition:background .15s;font-family:inherit;}
        .wk-chip:hover{background:#004080;color:#fff;border-color:#004080;}
        .wk-dynamic-lijst{display:flex;flex-direction:column;gap:10px;margin-bottom:10px;}
        .wk-dynamic-item{display:flex;gap:8px;align-items:flex-start;}
        .wk-dynamic-item .wk-input{flex:1;}
        .wk-verwijder-btn{background:none;border:1px solid #b0bec5;border-radius:4px;color:#666;cursor:pointer;padding:6px 10px;font-size:.9em;margin-top:2px;flex-shrink:0;font-family:inherit;}
        .wk-verwijder-btn:hover{background:#fdf2f2;border-color:#c0392b;color:#c0392b;}
        .wk-maatregel-blok{border:1px solid #dde3ea;border-radius:5px;padding:16px;margin-bottom:12px;background:#fafbfc;}
        .wk-maatregel-kop{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;}
        .wk-maatregel-kop span{font-weight:700;color:#004080;font-size:.95em;}
        .wk-select{display:block;width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid #b0bec5;border-radius:4px;font-size:1em;font-family:inherit;background:#fafafa;color:#1a1a1a;}
        .wk-select:focus{outline:none;border-color:#004080;}
        .wk-btn{display:inline-block;padding:10px 20px;border-radius:4px;font-size:.95em;font-family:inherit;cursor:pointer;border:none;font-weight:600;}
        .wk-btn--add{background:#eef3fa;color:#004080;border:1px dashed #004080;margin-bottom:6px;}
        .wk-btn--add:hover{background:#d6e4f7;}
        .wk-btn--primary{background:#004080;color:#fff;font-size:1.05em;padding:13px 28px;}
        .wk-btn--primary:hover{background:#00306a;}
        .wk-submit-rij{margin-top:10px;margin-bottom:40px;}
        @media(max-width:600px){.wk-stap{padding:18px 14px;}.wk-popup__kader{padding:22px 16px;}}
        </style>';
    }

    private static function render_notices( $status, $errors ) {
        if ( $status === 'ok' ) {
                        
                        
        }
        if ( $status === 'error' && ! empty( $errors ) ) {
            echo '<div class="wk-notice wk-notice--error"><strong>Let op:</strong><ul>';
            foreach ( $errors as $e ) {
                echo '>' . esc_html( $e ) . '</li>';
            }
            echo '</ul></div>';
        }
    }

    private static function render_formulier( $team_preset ) {
        $nonce = wp_nonce_field( 'werkdruk_submit', 'werkdruk_nonce', true, false );
        $html  = '<form class="wk-form" method="post" novalidate>';
        $html .= $nonce;
        $html .= '<input type="hidden" name="werkdruk_submit" value="1">';
        $html .= self::stap1( $team_preset );
        $html .= self::stap2();
        $html .= self::stap3();
        $html .= self::stap4();
        $html .= self::stap6();
        $html .= '<div class="wk-submit-rij">';
        $html .= '<button type="submit" class="wk-btn wk-btn--primary">Verstuur mijn bijdrage aan het werkdrukplan</button>';
        $html .= '</div>';
        $html .= '</form>';
        echo $html;
        self::render_scripts();
    }

    private static function stap1( $team_preset ) {
        $team = esc_attr( $team_preset );
        $h  = '<section class="wk-stap">';
        $h .= '<div class="wk-stap__kop">';
        $h .= '<span class="wk-stap__nr">Stap 1</span>';
        $h .= '<h2 class="wk-stap__titel">Startpunt</h2>';
        $h .= '<button type="button" class="wk-info-btn" data-popup="wk-pop-1" aria-label="Meer informatie stap 1">&#9432;</button>';
        $h .= '</div>';
        $h .= '<div class="wk-popup" id="wk-pop-1" hidden>';
        $h .= '<div class="wk-popup__kader">';
        $h .= '<button type="button" class="wk-popup__sluit" aria-label="Sluiten">&times;</button>';
        $h .= '<h3>Stap 1 - Startpunt</h3>';
        $h .= '<p>In de CAO VO (hoofdstuk 8.7) is afgesproken dat er structureel <strong>300 miljoen euro per jaar</strong> beschikbaar is voor werkdrukverlichting.</p>';
        $h .= '<p>Werknemers bepalen <strong>gezamenlijk</strong> waaraan de collectieve middelen worden besteed. Dit formulier is stap een in dat proces.</p>';
        $h .= '<p>Iedereen vult dit formulier individueel in. De uitkomsten zijn direct voor het hele team zichtbaar.</p>';
        $h .= '</div></div>';
        $h .= '<p class="wk-stap__intro">Vul jouw naam en de naam van het team in.</p>';
        $h .= '<label class="wk-label" for="wk_team">Team / sectie <span class="wk-req">*</span></label>';
        $h .= '<input class="wk-input" type="text" id="wk_team" name="team" value="' . $team . '" placeholder="bijv. Economie bovenbouw" required>';
        $h .= '<label class="wk-label" for="wk_name">Jouw naam <span class="wk-req">*</span></label>';
        $h .= '<input class="wk-input" type="text" id="wk_name" name="name" placeholder="bijv. Jan de Vries" required>';
        $h .= '</section>';
        return $h;
    }

    private static function stap2() {
        $h  = '<section class="wk-stap">';
        $h .= '<div class="wk-stap__kop">';
        $h .= '<span class="wk-stap__nr">Stap 2</span>';
        $h .= '<h2 class="wk-stap__titel">Ervaren werkdruk</h2>';
        $h .= '<button type="button" class="wk-info-btn" data-popup="wk-pop-2" aria-label="Meer informatie stap 2">&#9432;</button>';
        $h .= '</div>';
        $h .= '<div class="wk-popup" id="wk-pop-2" hidden>';
        $h .= '<div class="wk-popup__kader">';
        $h .= '<button type="button" class="wk-popup__sluit" aria-label="Sluiten">&times;</button>';
        $h .= '<h3>Stap 2 - Ervaren werkdruk</h3>';
        $h .= '<p><strong>Werkdruk</strong> ontstaat wanneer de taakeisen hoger zijn dan wat jij in de beschikbare tijd kunt verwerken.</p>';
        $h .= '<p><strong>Laag</strong> - Je voert je werk comfortabel uit binnen de beschikbare tijd.<br>';
        $h .= '<strong>Gemiddeld</strong> - Je ervaart regelmatig tijdsdruk, maar het is beheersbaar.<br>';
        $h .= '<strong>Hoog</strong> - Je ervaart structureel te veel taken voor de beschikbare tijd.</p>';
        $h .= '</div></div>';
        $h .= '<p class="wk-stap__intro">Geef aan hoeveel werkdruk jij op dit moment ervaart. Denk aan de afgelopen weken als richtlijn.</p>';
        $h .= '<fieldset class="wk-fieldset">';
        $h .= '<legend class="wk-label">Werkdrukniveau <span class="wk-req">*</span></legend>';
        $h .= '<label class="wk-radio"><input type="radio" name="wp_level" value="laag" required> Laag - ik ervaar de werkdruk als goed te beheren</label>';
        $h .= '<label class="wk-radio"><input type="radio" name="wp_level" value="gemiddeld"> Gemiddeld - ik ervaar regelmatig tijdsdruk</label>';
        $h .= '<label class="wk-radio"><input type="radio" name="wp_level" value="hoog"> Hoog - ik ervaar structureel te veel taken voor de beschikbare tijd</label>';
        $h .= '<label class="wk-radio"><input type="radio" name="wp_level" value="nvt"> n.v.t.</label>';
        $h .= '</fieldset>';
        $h .= '<label class="wk-label" for="wk_note">Korte toelichting <span class="wk-hint">(optioneel)</span></label>';
        $h .= '<textarea class="wk-input" id="wk_note" name="wp_note" rows="3" placeholder="Wat maakt dat jij dit niveau ervaart?"></textarea>';
        $h .= '</section>';
        return $h;
    }

    private static function stap3() {
        $h  = '<section class="wk-stap">';
        $h .= '<div class="wk-stap__kop">';
        $h .= '<span class="wk-stap__nr">Stap 3</span>';
        $h .= '<h2 class="wk-stap__titel">Oorzaken van werkdruk</h2>';
        $h .= '<button type="button" class="wk-info-btn" data-popup="wk-pop-3" aria-label="Meer informatie stap 3">&#9432;</button>';
        $h .= '</div>';
        $h .= '<div class="wk-popup" id="wk-pop-3" hidden>';
        $h .= '<div class="wk-popup__kader">';
        $h .= '<button type="button" class="wk-popup__sluit" aria-label="Sluiten">&times;</button>';
        $h .= '<h3>Stap 3 - Oorzaken</h3>';
        $h .= '<p>Benoem oorzaken zo <strong>concreet</strong> mogelijk. Klik op een suggestie om die over te nemen.</p>';
        $h .= '</div></div>';
        $h .= '<p class="wk-stap__intro">Wat zijn voor jou de belangrijkste oorzaken van werkdruk? Wees zo concreet mogelijk. Klik op een suggestie om die over te nemen, of typ zelf.</p>';
        $h .= '<div class="wk-chips">';
        foreach ( self::$oorzaken as $tip ) {
            $h .= '<button type="button" class="wk-chip" data-target="wk-causes-lijst" data-name="causes[]" data-value="' . esc_attr( $tip ) . '">' . esc_html( $tip ) . '</button>';
        }
        $h .= '</div>';
        $h .= '<div class="wk-dynamic-lijst" id="wk-causes-lijst">';
        $h .= '<div class="wk-dynamic-item">';
        $h .= '<textarea class="wk-input" name="causes[]" rows="2" placeholder="Oorzaak - beschrijf zo concreet mogelijk"></textarea>';
        $h .= '<button type="button" class="wk-verwijder-btn" aria-label="Verwijder">&#10005;</button>';
        $h .= '</div></div>';
        $h .= '<button type="button" class="wk-btn wk-btn--add" data-lijst="wk-causes-lijst" data-name="causes[]" data-placeholder="Oorzaak - beschrijf zo concreet mogelijk">+ Oorzaak toevoegen</button>';
        $h .= '</section>';
        return $h;
    }

    private static function stap4() {
        $h  = '<section class="wk-stap">';
        $h .= '<div class="wk-stap__kop">';
        $h .= '<span class="wk-stap__nr">Stap 4</span>';
        $h .= '<h2 class="wk-stap__titel">Oplossingsrichtingen</h2>';
        $h .= '<button type="button" class="wk-info-btn" data-popup="wk-pop-4" aria-label="Meer informatie stap 4">&#9432;</button>';
        $h .= '</div>';
        $h .= '<div class="wk-popup" id="wk-pop-4" hidden>';
        $h .= '<div class="wk-popup__kader">';
        $h .= '<button type="button" class="wk-popup__sluit" aria-label="Sluiten">&times;</button>';
        $h .= '<h3>Stap 4 - Oplossingsrichtingen</h3>';
        $h .= '<p>Denk breed en vrij. Het gaat om de <strong>richting</strong> van mogelijke oplossingen. In stap 6 werk je deze uit tot concrete maatregelen.</p>';
        $h .= '</div></div>';
        $h .= '<p class="wk-stap__intro">Bedenk oplossingsrichtingen om de werkdruk te verlagen. Denk breed en vrij; je werkt ze in stap 6 uit tot concrete maatregelen.</p>';
        $h .= '<div class="wk-dynamic-lijst" id="wk-solutions-lijst">';
        $h .= '<div class="wk-dynamic-item">';
        $h .= '<textarea class="wk-input" name="solutions[]" rows="2" placeholder="Oplossingsrichting - bijv. minder administratieve taken"></textarea>';
        $h .= '<button type="button" class="wk-verwijder-btn" aria-label="Verwijder">&#10005;</button>';
        $h .= '</div></div>';
        $h .= '<button type="button" class="wk-btn wk-btn--add" data-lijst="wk-solutions-lijst" data-name="solutions[]" data-placeholder="Oplossingsrichting - bijv. minder administratieve taken">+ Oplossingsrichting toevoegen</button>';
        $h .= '</section>';
        return $h;
    }

    private static function stap6() {
        $h  = '<section class="wk-stap">';
        $h .= '<div class="wk-stap__kop">';
        $h .= '<span class="wk-stap__nr">Stap 6</span>';
        $h .= '<h2 class="wk-stap__titel">Maatregelen</h2>';
        $h .= '<button type="button" class="wk-info-btn" data-popup="wk-pop-6" aria-label="Meer informatie stap 6">&#9432;</button>';
        $h .= '</div>';
        $h .= '<div class="wk-popup" id="wk-pop-6" hidden>';
        $h .= '<div class="wk-popup__kader">';
        $h .= '<button type="button" class="wk-popup__sluit" aria-label="Sluiten">&times;</button>';
        $h .= '<h3>Stap 6 - Maatregelen</h3>';
        $h .= '<p>Formuleer concrete maatregelen. Gebruik de suggesties als startpunt.</p>';
        $h .= '<p><strong>Categorie</strong> - Individueel (eigen werkdrukbudget) of Collectief (schoolbudget)?</p>';
        $h .= '<p><strong>Effect</strong> - ++ zeer groot, + groot, - beperkt, -- zeer beperkt</p>';
        $h .= '<p><strong>Haalbaarheid</strong> - ++ zeer goed, + goed, - moeilijk, -- nauwelijks</p>';
        $h .= '</div></div>';
        $h .= '<p class="wk-stap__intro">Formuleer concrete maatregelen die de werkdruk kunnen verlagen. Klik op een suggestie of typ zelf.</p>';
        $h .= '<div class="wk-chips">';
        foreach ( self::$maatregelen as $tip ) {
            $h .= '<button type="button" class="wk-chip wk-chip--maatregel" data-value="' . esc_attr( $tip ) . '">' . esc_html( $tip ) . '</button>';
        }
        $h .= '</div>';
        $h .= '<div class="wk-dynamic-lijst" id="wk-measures-lijst">';
        $h .= '<div class="wk-maatregel-blok">';
        $h .= '<div class="wk-maatregel-kop"><span>Maatregel 1</span>';
        $h .= '<button type="button" class="wk-verwijder-btn" aria-label="Verwijder">&#10005;</button></div>';
        $h .= '<label class="wk-label">Omschrijving <span class="wk-req">*</span></label>';
        $h .= '<textarea class="wk-input wk-measure-desc" name="measures[0][desc]" rows="2" placeholder="Beschrijf de maatregel concreet"></textarea>';
        $h .= '<label class="wk-label">Categorie</label>';
        $h .= '<select class="wk-select" name="measures[0][cat]">';
        $h .= '<option value="">-- kies --</option>';
        $h .= '<option value="individueel">Individueel (eigen werkdrukbudget)</option>';
        $h .= '<option value="collectief">Collectief (schoolbudget)</option>';
        $h .= '<option value="nvt">n.v.t.</option>';
        $h .= '</select>';
        $h .= '<label class="wk-label">Effect</label>';
        $h .= '<select class="wk-select" name="measures[0][effect]">';
        $h .= '<option value="">-- kies --</option>';
        $h .= '<option value="++">++ Zeer groot effect</option>';
        $h .= '<option value="+">+ Groot effect</option>';
        $h .= '<option value="-">- Beperkt effect</option>';
        $h .= '<option value="--">-- Zeer beperkt effect</option>';
        $h .= '<option value="nvt">n.v.t.</option>';
        $h .= '</select>';
        $h .= '<label class="wk-label">Haalbaarheid</label>';
        $h .= '<select class="wk-select" name="measures[0][feasibility]">';
        $h .= '<option value="">-- kies --</option>';
        $h .= '<option value="++">++ Zeer goed haalbaar</option>';
        $h .= '<option value="+">+ Goed haalbaar</option>';
        $h .= '<option value="-">- Moeilijk haalbaar</option>';
        $h .= '<option value="--">-- Nauwelijks haalbaar</option>';
        $h .= '<option value="nvt">n.v.t.</option>';
        $h .= '</select>';
        $h .= '</div></div>';
        $h .= '<button type="button" class="wk-btn wk-btn--add" id="wk-add-maatregel">+ Maatregel toevoegen</button>';
        $h .= '</section>';
        return $h;
    }

    private static function render_scripts() {
        echo '<script>
(function(){

    document.querySelectorAll(".wk-info-btn").forEach(function(btn){
        btn.addEventListener("click",function(){
            var p=document.getElementById(btn.getAttribute("data-popup"));
            if(p) p.hidden=false;
        });
    });

    document.addEventListener("click",function(e){
        if(e.target.classList.contains("wk-popup__sluit")){
            var p=e.target.closest(".wk-popup");
            if(p) p.hidden=true;
        }
        if(e.target.classList.contains("wk-popup")){
            e.target.hidden=true;
        }
    });

    document.addEventListener("keydown",function(e){
        if(e.key==="Escape"){
            document.querySelectorAll(".wk-popup:not([hidden])").forEach(function(p){
                p.hidden=true;
            });
        }
    });

    document.querySelectorAll(".wk-btn--add").forEach(function(btn){
        if(btn.id==="wk-add-maatregel") return;
        btn.addEventListener("click",function(){
            var lijst=document.getElementById(btn.getAttribute("data-lijst"));
            if(!lijst) return;
            var name=btn.getAttribute("data-name");
            var ph=btn.getAttribute("data-placeholder")||"";
            var item=document.createElement("div");
            item.className="wk-dynamic-item";
            item.innerHTML="<textarea class=\"wk-input\" name=\""+name+"\" rows=\"2\" placeholder=\""+ph+"\"></textarea>"
                +"<button type=\"button\" class=\"wk-verwijder-btn\" aria-label=\"Verwijder\">&#10005;</button>";
            lijst.appendChild(item);
            item.querySelector("textarea").focus();
        });
    });

    document.addEventListener("click",function(e){
        if(!e.target.classList.contains("wk-verwijder-btn")) return;
        var item=e.target.closest(".wk-dynamic-item");
        var blok=e.target.closest(".wk-maatregel-blok");
        if(item){
            var lijst=item.parentElement;
            if(lijst&&lijst.querySelectorAll(".wk-dynamic-item").length>1){
                item.remove();
            } else {
                var ta=item.querySelector("textarea");
                if(ta) ta.value="";
            }
        }
        if(blok){
            var ml=blok.parentElement;
            if(ml&&ml.querySelectorAll(".wk-maatregel-blok").length>1){
                blok.remove();
                wkHernummer();
            } else {
                blok.querySelectorAll("textarea,select").forEach(function(el){ el.value=""; });
            }
        }
    });

    document.querySelectorAll(".wk-chip:not(.wk-chip--maatregel)").forEach(function(chip){
        chip.addEventListener("click",function(){
            var lijst=document.getElementById(chip.getAttribute("data-target"));
            var name=chip.getAttribute("data-name");
            var value=chip.getAttribute("data-value");
            if(!lijst) return;
            var leeg=null;
            lijst.querySelectorAll("textarea").forEach(function(ta){
                if(!leeg&&ta.value.trim()==="") leeg=ta;
            });
            if(leeg){
                leeg.value=value;
            } else {
                var item=document.createElement("div");
                item.className="wk-dynamic-item";
                item.innerHTML="<textarea class=\"wk-input\" name=\""+name+"\" rows=\"2\"></textarea>"
                    +"<button type=\"button\" class=\"wk-verwijder-btn\" aria-label=\"Verwijder\">&#10005;</button>";
                lijst.appendChild(item);
                item.querySelector("textarea").value=value;
            }
        });
    });

    document.querySelectorAll(".wk-chip--maatregel").forEach(function(chip){
        chip.addEventListener("click",function(){
            var value=chip.getAttribute("data-value");
            var lijst=document.getElementById("wk-measures-lijst");
            if(!lijst) return;
            var leeg=null;
            lijst.querySelectorAll(".wk-measure-desc").forEach(function(ta){
                if(!leeg&&ta.value.trim()==="") leeg=ta;
            });
            if(leeg){
                leeg.value=value;
            } else {
                wkNieuweMaatregel(value);
            }
        });
    });

    var addBtn=document.getElementById("wk-add-maatregel");
    if(addBtn) addBtn.addEventListener("click",function(){ wkNieuweMaatregel(""); });

    function wkNieuweMaatregel(desc){
        var lijst=document.getElementById("wk-measures-lijst");
        if(!lijst) return;
        var i=lijst.querySelectorAll(".wk-maatregel-blok").length;
        var blok=document.createElement("div");
        blok.className="wk-maatregel-blok";
        blok.innerHTML=
            "<div class=\"wk-maatregel-kop\"><span>Maatregel "+(i+1)+"</span>"
            +"<button type=\"button\" class=\"wk-verwijder-btn\" aria-label=\"Verwijder\">&#10005;</button></div>"
            +"<label class=\"wk-label\">Omschrijving <span class=\"wk-req\">*</span></label>"
            +"<textarea class=\"wk-input wk-measure-desc\" name=\"measures["+i+"][desc]\" rows=\"2\" placeholder=\"Beschrijf de maatregel concreet\"></textarea>"
            +"<label class=\"wk-label\">Categorie</label>"
            +"<select class=\"wk-select\" name=\"measures["+i+"][cat]\">"
            +"<option value=\"\">-- kies --</option>"
            +"<option value=\"individueel\">Individueel (eigen werkdrukbudget)</option>"
            +"<option value=\"collectief\">Collectief (schoolbudget)</option>"
            +"<option value=\"nvt\">n.v.t.</option>"
            +"</select>"
            +"<label class=\"wk-label\">Effect</label>"
            +"<select class=\"wk-select\" name=\"measures["+i+"][effect]\">"
            +"<option value=\"\">-- kies --</option>"
            +"<option value=\"++\">++ Zeer groot effect</option>"
            +"<option value=\"+\">+ Groot effect</option>"
            +"<option value=\"-\">- Beperkt effect</option>"
            +"<option value=\"--\">-- Zeer beperkt effect</option>"
            +"<option value=\"nvt\">n.v.t.</option>"
            +"</select>"
            +"<label class=\"wk-label\">Haalbaarheid</label>"
            +"<select class=\"wk-select\" name=\"measures["+i+"][feasibility]\">"
            +"<option value=\"\">-- kies --</option>"
            +"<option value=\"++\">++ Zeer goed haalbaar</option>"
            +"<option value=\"+\">+ Goed haalbaar</option>"
            +"<option value=\"-\">- Moeilijk haalbaar</option>"
            +"<option value=\"--\">-- Nauwelijks haalbaar</option>"
            +"<option value=\"nvt\">n.v.t.</option>"
            +"</select>";
        lijst.appendChild(blok);
        if(desc) blok.querySelector(".wk-measure-desc").value=desc;
        blok.querySelector(".wk-measure-desc").focus();
    }

    function wkHernummer(){
        var lijst=document.getElementById("wk-measures-lijst");
        if(!lijst) return;
        lijst.querySelectorAll(".wk-maatregel-blok").forEach(function(blok,i){
            var kop=blok.querySelector(".wk-maatregel-kop span");
            if(kop) kop.textContent="Maatregel "+(i+1);
            blok.querySelectorAll("[name]").forEach(function(el){
                el.name=el.name.replace(/measures\[\d+\]/,"measures["+i+"]");
            });
        });
    }

})();
        </script>';
    }

} /* === EINDE CLASS Werkdruk_Form === */
/* === EINDE BESTAND class-form.php VOLLEDIG === */


