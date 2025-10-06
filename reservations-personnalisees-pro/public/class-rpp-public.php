<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Reservations_Personnalisees_Pro
 * @subpackage Reservations_Personnalisees_Pro/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for enqueuing
 * the public-facing stylesheet and JavaScript.
 *
 * @package    Reservations_Personnalisees_Pro
 * @subpackage Reservations_Personnalisees_Pro/public
 * @author     Jules
 */
class RPP_Public {

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
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . '../assets/css/frontend.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . '../assets/js/frontend.js', array( 'jquery' ), $this->version, true );

        $options = get_option('rpp_options');
        $recaptcha_site_key = isset($options['recaptcha_site_key']) ? $options['recaptcha_site_key'] : '';

        // Pass ajax url and recaptcha key to script.js
        wp_localize_script( $this->plugin_name, 'rpp_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'recaptcha_site_key' => $recaptcha_site_key,
            'i18n' => array(
                'fill_all_fields' => __( 'Veuillez remplir tous les champs obligatoires.', 'reservations-personnalisees-pro' ),
                'sending' => __( 'Envoi en cours...', 'reservations-personnalisees-pro' ),
                'reserve' => __( 'Réserver', 'reservations-personnalisees-pro' ),
                'technical_error' => __( 'Une erreur technique est survenue. Veuillez réessayer plus tard.', 'reservations-personnalisees-pro' ),
                'verifying' => __( 'Vérification...', 'reservations-personnalisees-pro' ),
            )
        ) );

        if ( ! empty( $recaptcha_site_key ) ) {
            wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $recaptcha_site_key ), array(), null, true );
        }
    }

    /**
     * Add the shortcode for the reservation form.
     *
     * @since    1.0.0
     */
    public function add_shortcodes() {
        add_shortcode( 'reservation_form', array( $this, 'render_reservation_form' ) );
    }

    /**
     * Render the reservation form.
     *
     * @since    1.0.0
     * @return   string    The HTML for the form.
     */
    public function render_reservation_form() {
        ob_start();
        ?>
        <form id="reservation-form" class="reservation-container">
            <h2><?php _e( 'Réserver un créneau', 'reservations-personnalisees-pro' ); ?></h2>

            <!-- Honeypot field for security -->
            <div class="honeypot" style="display:none;">
                <label for="website"><?php _e( 'Website', 'reservations-personnalisees-pro' ); ?></label>
                <input type="text" id="website" name="website">
            </div>

            <?php wp_nonce_field( 'rpp_reservation_nonce', 'rpp_nonce' ); ?>

            <div class="form-group">
                <label for="rpp-nom"><?php _e( 'Nom', 'reservations-personnalisees-pro' ); ?></label>
                <input type="text" id="rpp-nom" name="nom" required>
            </div>
            <div class="form-group">
                <label for="rpp-prenom"><?php _e( 'Prénom', 'reservations-personnalisees-pro' ); ?></label>
                <input type="text" id="rpp-prenom" name="prenom" required>
            </div>
            <div class="form-group">
                <label for="rpp-entite"><?php _e( 'Entité', 'reservations-personnalisees-pro' ); ?></label>
                <input type="text" id="rpp-entite" name="entite">
            </div>
            <div class="form-group">
                <label for="rpp-email"><?php _e( 'Email', 'reservations-personnalisees-pro' ); ?></label>
                <input type="email" id="rpp-email" name="email" required>
            </div>
            <div class="form-group">
                <label for="date-select"><?php _e( 'Date', 'reservations-personnalisees-pro' ); ?></label>
                <select name="date" id="date-select" required></select>
            </div>
            <div class="form-group">
                <label for="heure-select"><?php _e( 'Heure', 'reservations-personnalisees-pro' ); ?></label>
                <select name="heure" id="heure-select" required></select>
            </div>
            <div class="form-group">
                <label for="rpp-sujets"><?php _e( 'Sujet(s)', 'reservations-personnalisees-pro' ); ?></label>
                <textarea id="rpp-sujets" name="sujets"></textarea>
            </div>
            <input type="hidden" name="recaptcha_token" id="recaptcha-token">
            <button type="submit" class="btn-reserver"><?php _e( 'Réserver', 'reservations-personnalisees-pro' ); ?></button>
            <p class="message" id="reservation-message"></p>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler for checking slot availability.
     * @since 1.0.0
     */
    public function ajax_check_availability() {
        check_ajax_referer('rpp_reservation_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'reservations_personnalisees';

        $date = sanitize_text_field($_POST['date']);
        $heure = sanitize_text_field($_POST['heure']);

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE date_reservation = %s AND heure_reservation = %s",
            $date,
            $heure
        ));

        if ($count > 0) {
            wp_send_json_error(array('message' => __('Ce créneau n\'est plus disponible.', 'reservations-personnalisees-pro')));
        } else {
            wp_send_json_success(array('message' => __('Créneau disponible.', 'reservations-personnalisees-pro')));
        }
    }

    /**
     * AJAX handler for form submission.
     * @since 1.0.0
     */
    public function ajax_submit_reservation() {
        // 1. Security checks
        if (!isset($_POST['rpp_nonce']) || !wp_verify_nonce($_POST['rpp_nonce'], 'rpp_reservation_nonce')) {
            wp_send_json_error(array('message' => __('Erreur de sécurité. Veuillez rafraîchir la page.', 'reservations-personnalisees-pro')));
            return;
        }

        if (!empty($_POST['website'])) { // Honeypot check
            $this->log_error('Honeypot field filled.');
            wp_send_json_error(array('message' => __('Erreur anti-spam.', 'reservations-personnalisees-pro')));
            return;
        }

        // 2. reCAPTCHA validation
        $options = get_option('rpp_options');
        $recaptcha_secret_key = isset($options['recaptcha_secret_key']) ? $options['recaptcha_secret_key'] : '';

        if (!empty($recaptcha_secret_key)) {
            if (!isset($_POST['recaptcha_token']) || empty($_POST['recaptcha_token'])) {
                $this->log_error('reCAPTCHA token not found.');
                wp_send_json_error(array('message' => __('Vérification anti-bot échouée.', 'reservations-personnalisees-pro')));
                return;
            }

            $token = sanitize_text_field($_POST['recaptcha_token']);
            $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret'   => $recaptcha_secret_key,
                    'response' => $token,
                    'remoteip' => $_SERVER['REMOTE_ADDR'],
                ],
            ]);

            if (is_wp_error($response)) {
                $this->log_error('reCAPTCHA request failed: ' . $response->get_error_message());
                wp_send_json_error(array('message' => __('Erreur de vérification reCAPTCHA.', 'reservations-personnalisees-pro')));
                return;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (!$body['success'] || $body['score'] < 0.5) { // Threshold can be adjusted
                $this->log_error('reCAPTCHA verification failed. Score: ' . ($body['score'] ?? 'N/A') . '. Errors: ' . implode(', ', $body['error-codes'] ?? []));
                wp_send_json_error(array('message' => __('La vérification anti-spam a échoué.', 'reservations-personnalisees-pro')));
                return;
            }
        }

        // 3. Sanitize and validate data
        $nom = sanitize_text_field($_POST['nom']);
        $prenom = sanitize_text_field($_POST['prenom']);
        $entite = sanitize_text_field($_POST['entite']);
        $email = sanitize_email($_POST['email']);
        $date = sanitize_text_field($_POST['date']);
        $heure = sanitize_text_field($_POST['heure']);
        $sujets = sanitize_textarea_field($_POST['sujets']);

        if (empty($nom) || empty($prenom) || empty($email) || empty($date) || empty($heure)) {
            wp_send_json_error(array('message' => __('Veuillez remplir tous les champs obligatoires.', 'reservations-personnalisees-pro')));
            return;
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Veuillez fournir une adresse e-mail valide.', 'reservations-personnalisees-pro')));
            return;
        }

        // 3. Final availability check
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservations_personnalisees';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE date_reservation = %s AND heure_reservation = %s",
            $date,
            $heure
        ));

        if ($count > 0) {
            wp_send_json_error(array('message' => __('Désolé, ce créneau vient d\'être réservé. Veuillez en choisir un autre.', 'reservations-personnalisees-pro')));
            return;
        }

        // 4. Insert into database
        $result = $wpdb->insert(
            $table_name,
            array(
                'nom' => $nom,
                'prenom' => $prenom,
                'entite' => $entite,
                'email' => $email,
                'date_reservation' => $date,
                'heure_reservation' => $heure,
                'sujets' => $sujets,
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            global $wpdb;
            $this->log_error('Database insert failed: ' . $wpdb->last_error);
            wp_send_json_error(array('message' => __('Une erreur est survenue lors de l\'enregistrement de votre réservation.', 'reservations-personnalisees-pro')));
        } else {
            $this->send_reservation_emails($_POST);
            wp_send_json_success(array('message' => __('✅ Votre réservation a bien été enregistrée.', 'reservations-personnalisees-pro')));
        }
    }

    /**
     * Sends notification emails to admin and client.
     * @param array $data The reservation data from the form.
     * @since 1.0.0
     */
    private function send_reservation_emails($data) {
        $options = get_option('rpp_options');

        // Placeholders
        $placeholders = array(
            '[nom]'    => sanitize_text_field($data['nom']),
            '[prenom]' => sanitize_text_field($data['prenom']),
            '[entite]' => sanitize_text_field($data['entite']),
            '[email]'  => sanitize_email($data['email']),
            '[date]'   => date_i18n(get_option('date_format'), strtotime(sanitize_text_field($data['date']))),
            '[heure]'  => sanitize_text_field($data['heure']),
            '[sujets]' => sanitize_textarea_field($data['sujets']),
        );

        $headers = array('Content-Type: text/html; charset=UTF-8');

        // --- Admin Email ---
        $admin_email = get_option('admin_email');
        $admin_subject_template = isset($options['admin_email_subject']) ? $options['admin_email_subject'] : 'Nouvelle réservation : [nom] [prenom]';
        $admin_body_template = isset($options['admin_email_body']) ? $options['admin_email_body'] : "Une nouvelle réservation a été effectuée.<br><br>Détails :<br>[nom] [prenom]<br>[email]<br>[date] à [heure]";

        $admin_subject = str_replace(array_keys($placeholders), array_values($placeholders), $admin_subject_template);
        $admin_body = wpautop(str_replace(array_keys($placeholders), array_values($placeholders), $admin_body_template));

        wp_mail($admin_email, $admin_subject, $admin_body, $headers);

        // --- Client Email ---
        $client_email = sanitize_email($data['email']);
        $client_subject_template = isset($options['client_email_subject']) ? $options['client_email_subject'] : 'Confirmation de votre réservation';
        $client_body_template = isset($options['client_email_body']) ? $options['client_email_body'] : "Bonjour [prenom],<br><br>Votre réservation pour le [date] à [heure] a bien été enregistrée.";

        $client_subject = str_replace(array_keys($placeholders), array_values($placeholders), $client_subject_template);
        $client_body = wpautop(str_replace(array_keys($placeholders), array_values($placeholders), $client_body_template));

        wp_mail($client_email, $client_subject, $client_body, $headers);
    }

    /**
     * Logs errors to a file.
     * @param string $message The error message to log.
     * @since 1.0.0
     */
    private function log_error($message) {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/reservations-plugin';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        $log_file = $log_dir . '/reservations.log';
        $timestamp = date("Y-m-d H:i:s");
        $log_message = "[$timestamp] " . $message . "\n";
        error_log($log_message, 3, $log_file);
    }
}