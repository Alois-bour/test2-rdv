<?php

/**
 * Fired during plugin activation.
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Reservations_Personnalisees_Pro
 * @subpackage Reservations_Personnalisees_Pro/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Reservations_Personnalisees_Pro
 * @subpackage Reservations_Personnalisees_Pro/includes
 * @author     Jules
 */
class RPP_Activator {

    /**
     * The code that runs during plugin activation.
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservations_personnalisees';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT AUTO_INCREMENT,
            date_reservation DATE NOT NULL,
            heure_reservation VARCHAR(10) NOT NULL,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            entite VARCHAR(150),
            email VARCHAR(200) NOT NULL,
            sujets TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

}