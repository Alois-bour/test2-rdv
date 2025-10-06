<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Reservations_Personnalisees_Pro
 * @subpackage Reservations_Personnalisees_Pro/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Reservations_Personnalisees_Pro
 * @subpackage Reservations_Personnalisees_Pro/includes
 * @author     Jules
 */
class RPP_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'reservations-personnalisees-pro',
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
        );
    }

}