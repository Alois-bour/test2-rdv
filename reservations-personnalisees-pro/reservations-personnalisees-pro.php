<?php
/**
 * Plugin Name:       Réservations Personnalisées Pro
 * Plugin URI:        https://example.com/
 * Description:       Un plugin complet pour gérer les réservations avec un formulaire frontend et un panneau d'administration.
 * Version:           1.0.0
 * Author:            Jules
 * Author URI:        https://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       reservations-personnalisees-pro
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'RPP_VERSION', '1.0.0' );
define( 'RPP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RPP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Activation hook for creating the database table.
require_once RPP_PLUGIN_DIR . 'includes/class-rpp-activator.php';
register_activation_hook( __FILE__, array( 'RPP_Activator', 'activate' ) );

// Include the main plugin class.
require_once RPP_PLUGIN_DIR . 'includes/class-reservations-personnalisees-pro.php';

// Begins execution of the plugin.
function run_reservations_personnalisees_pro() {
    $plugin = new Reservations_Personnalisees_Pro();
    $plugin->run();
}
run_reservations_personnalisees_pro();