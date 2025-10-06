<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Reservations_Personnalisees_Pro
 * @subpackage Reservations_Personnalisees_Pro/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Reservations_Personnalisees_Pro
 * @subpackage Reservations_Personnalisees_Pro/admin
 * @author     Jules
 */
class RPP_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/rpp-admin.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/rpp-admin.js', array( 'jquery' ), $this->version, false );
    }

    /**
     * Add the administration menu for the plugin.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Réservations', 'reservations-personnalisees-pro' ),
            __( 'Réservations', 'reservations-personnalisees-pro' ),
            'manage_options',
            $this->plugin_name,
            array( $this, 'display_reservations_page' ),
            'dashicons-calendar-alt',
            25
        );

        add_submenu_page(
            $this->plugin_name,
            __( 'Paramètres', 'reservations-personnalisees-pro' ),
            __( 'Paramètres', 'reservations-personnalisees-pro' ),
            'manage_options',
            $this->plugin_name . '-settings',
            array( $this, 'display_settings_page' )
        );
    }

    /**
     * Render the main reservations list page.
     *
     * @since    1.0.0
     */
    public function display_reservations_page() {
        require_once 'class-rpp-reservations-list-table.php';
        $list_table = new RPP_Reservations_List_Table();
        $list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Liste des Réservations', 'reservations-personnalisees-pro' ); ?></h1>

            <a href="<?php echo esc_url(add_query_arg('action', 'rpp_export_csv')); ?>" class="page-title-action">
                <?php _e( 'Exporter en CSV', 'reservations-personnalisees-pro' ); ?>
            </a>

            <hr class="wp-header-end">

            <form method="post">
                <?php
                $list_table->search_box('Rechercher', 'search_id');
                $list_table->display();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Paramètres de Réservations Personnalisées Pro', 'reservations-personnalisees-pro' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'rpp_settings_group' );
                do_settings_sections( $this->plugin_name . '-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register the settings for the plugin.
     * @since 1.0.0
     */
    public function register_settings() {
        // Register the main setting group
        register_setting(
            'rpp_settings_group', // Option group
            'rpp_options', // Option name
            array( $this, 'sanitize_settings' ) // Sanitize callback
        );

        // --- Email Section ---
        add_settings_section(
            'rpp_email_section', // ID
            __('Notifications par E-mail', 'reservations-personnalisees-pro'), // Title
            array( $this, 'print_email_section_info' ), // Callback
            $this->plugin_name . '-settings' // Page
        );

        add_settings_field('admin_email_subject', __('Sujet e-mail admin', 'reservations-personnalisees-pro'), array( $this, 'render_admin_email_subject_field' ), $this->plugin_name . '-settings', 'rpp_email_section');
        add_settings_field('admin_email_body', __('Corps e-mail admin', 'reservations-personnalisees-pro'), array( $this, 'render_admin_email_body_field' ), $this->plugin_name . '-settings', 'rpp_email_section');
        add_settings_field('client_email_subject', __('Sujet e-mail client', 'reservations-personnalisees-pro'), array( $this, 'render_client_email_subject_field' ), $this->plugin_name . '-settings', 'rpp_email_section');
        add_settings_field('client_email_body', __('Corps e-mail client', 'reservations-personnalisees-pro'), array( $this, 'render_client_email_body_field' ), $this->plugin_name . '-settings', 'rpp_email_section');

        // --- reCAPTCHA Section ---
        add_settings_section('rpp_recaptcha_section', __('reCAPTCHA v3', 'reservations-personnalisees-pro'), array( $this, 'print_recaptcha_section_info' ), $this->plugin_name . '-settings');
        add_settings_field('recaptcha_site_key', __('Clé du site', 'reservations-personnalisees-pro'), array( $this, 'render_recaptcha_site_key_field' ), $this->plugin_name . '-settings', 'rpp_recaptcha_section');
        add_settings_field('recaptcha_secret_key', __('Clé secrète', 'reservations-personnalisees-pro'), array( $this, 'render_recaptcha_secret_key_field' ), $this->plugin_name . '-settings', 'rpp_recaptcha_section');
    }

    /**
     * Sanitize each setting field as needed.
     * @param array $input Contains all settings fields as array keys
     * @return array
     */
    public function sanitize_settings( $input ) {
        $new_input = array();
        if( isset( $input['admin_email_subject'] ) ) $new_input['admin_email_subject'] = sanitize_text_field( $input['admin_email_subject'] );
        if( isset( $input['admin_email_body'] ) ) $new_input['admin_email_body'] = wp_kses_post( $input['admin_email_body'] );
        if( isset( $input['client_email_subject'] ) ) $new_input['client_email_subject'] = sanitize_text_field( $input['client_email_subject'] );
        if( isset( $input['client_email_body'] ) ) $new_input['client_email_body'] = wp_kses_post( $input['client_email_body'] );
        if( isset( $input['recaptcha_site_key'] ) ) $new_input['recaptcha_site_key'] = sanitize_text_field( $input['recaptcha_site_key'] );
        if( isset( $input['recaptcha_secret_key'] ) ) $new_input['recaptcha_secret_key'] = sanitize_text_field( $input['recaptcha_secret_key'] );
        return $new_input;
    }

    // --- Section Callbacks ---
    public function print_email_section_info() {
        _e('Personnalisez les e-mails envoyés à l\'administrateur et au client. Placeholders disponibles : `[nom]`, `[prenom]`, `[entite]`, `[email]`, `[date]`, `[heure]`, `[sujets]`', 'reservations-personnalisees-pro');
    }
    public function print_recaptcha_section_info() { _e('Configurez les clés API pour Google reCAPTCHA v3 pour protéger votre formulaire contre les bots.', 'reservations-personnalisees-pro'); }

    // --- Field Renderers ---
    public function render_admin_email_subject_field() {
        $options = get_option('rpp_options');
        printf('<input type="text" id="admin_email_subject" name="rpp_options[admin_email_subject]" value="%s" class="regular-text" />', isset( $options['admin_email_subject'] ) ? esc_attr( $options['admin_email_subject']) : 'Nouvelle réservation : [nom] [prenom]');
    }
    public function render_admin_email_body_field() {
        $options = get_option('rpp_options');
        wp_editor(isset($options['admin_email_body']) ? $options['admin_email_body'] : "Une nouvelle réservation a été effectuée.\n\nDétails :\n[nom] [prenom]\n[email]\n[date] à [heure]", 'admin_email_body', ['textarea_name' => 'rpp_options[admin_email_body]']);
    }
    public function render_client_email_subject_field() {
        $options = get_option('rpp_options');
        printf('<input type="text" id="client_email_subject" name="rpp_options[client_email_subject]" value="%s" class="regular-text" />', isset( $options['client_email_subject'] ) ? esc_attr( $options['client_email_subject']) : 'Confirmation de votre réservation');
    }
    public function render_client_email_body_field() {
        $options = get_option('rpp_options');
        wp_editor(isset($options['client_email_body']) ? $options['client_email_body'] : "Bonjour [prenom],\n\nVotre réservation pour le [date] à [heure] a bien été enregistrée.", 'client_email_body', ['textarea_name' => 'rpp_options[client_email_body]']);
    }
    public function render_recaptcha_site_key_field() {
        $options = get_option('rpp_options');
        printf('<input type="text" id="recaptcha_site_key" name="rpp_options[recaptcha_site_key]" value="%s" class="regular-text" />', isset( $options['recaptcha_site_key'] ) ? esc_attr( $options['recaptcha_site_key']) : '');
    }
    public function render_recaptcha_secret_key_field() {
        $options = get_option('rpp_options');
        printf('<input type="text" id="recaptcha_secret_key" name="rpp_options[recaptcha_secret_key]" value="%s" class="regular-text" />', isset( $options['recaptcha_secret_key'] ) ? esc_attr( $options['recaptcha_secret_key']) : '');
    }

    /**
     * Process the CSV export request.
     * @since 1.0.0
     */
    public function process_csv_export() {
        if (isset($_GET['action']) && $_GET['action'] == 'rpp_export_csv') {
            if (!current_user_can('manage_options')) {
                return;
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'reservations_personnalisees';
            $reservations = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);

            if ($reservations) {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=reservations-' . date('Y-m-d') . '.csv');

                $output = fopen('php://output', 'w');

                // Add BOM to fix UTF-8 in Excel
                fputs($output, "\xEF\xBB\xBF");

                // Header
                fputcsv($output, array_keys($reservations[0]));

                // Rows
                foreach ($reservations as $row) {
                    fputcsv($output, $row);
                }

                fclose($output);
                exit();
            }
        }
    }
}