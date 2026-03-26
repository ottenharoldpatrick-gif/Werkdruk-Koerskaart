<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Werkdruk_Admin {

    public static function register() {
        add_menu_page(
            'Werkdruk KoersKaart',
            'Werkdruk KoersKaart',
            'manage_options',
            'werkdruk-koerskaart',
            array( 'Werkdruk_Admin', 'render_pagina' ),
            'dashicons-feedback',
            26
        );
    }

    public static function render_pagina() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        Werkdruk_Overview::render();
    }
}