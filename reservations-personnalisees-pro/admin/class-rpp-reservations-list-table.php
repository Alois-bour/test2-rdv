<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class RPP_Reservations_List_Table extends WP_List_Table {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Réservation', 'reservations-personnalisees-pro' ),
            'plural'   => __( 'Réservations', 'reservations-personnalisees-pro' ),
            'ajax'     => false
        ] );
    }

    /**
     * Get the table data.
     *
     * @param int $per_page
     * @param int $page_number
     * @return array
     */
    public static function get_reservations( $per_page = 20, $page_number = 1 ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservations_personnalisees';

        $sql = "SELECT * FROM {$table_name}";

        if ( ! empty( $_REQUEST['s'] ) ) {
            $search = esc_sql( $wpdb->esc_like( $_REQUEST['s'] ) );
            $sql .= $wpdb->prepare( " WHERE nom LIKE %s OR prenom LIKE %s OR email LIKE %s", "%{$search}%", "%{$search}%", "%{$search}%" );
        }

        if ( ! empty( $_REQUEST['orderby'] ) ) {
            $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
            $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
        } else {
            $sql .= ' ORDER BY created_at DESC';
        }

        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

        $result = $wpdb->get_results( $sql, 'ARRAY_A' );
        return $result;
    }

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservations_personnalisees';

        $sql = "SELECT COUNT(*) FROM {$table_name}";

        if ( ! empty( $_REQUEST['s'] ) ) {
            $search = esc_sql( $wpdb->esc_like( $_REQUEST['s'] ) );
            $sql .= $wpdb->prepare( " WHERE nom LIKE %s OR prenom LIKE %s OR email LIKE %s", "%{$search}%", "%{$search}%", "%{$search}%" );
        }

        return $wpdb->get_var( $sql );
    }

    /**
     * Text to display when no reservation data is available.
     */
    public function no_items() {
        _e( 'Aucune réservation trouvée.', 'reservations-personnalisees-pro' );
    }

    /**
     * Render a column when no custom renderer is available.
     *
     * @param array $item
     * @param string $column_name
     * @return mixed
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'date_reservation':
                return date_i18n( get_option('date_format'), strtotime($item[$column_name]));
            case 'heure_reservation':
            case 'nom':
            case 'prenom':
            case 'entite':
            case 'email':
            case 'sujets':
                return esc_html($item[ $column_name ]);
            case 'created_at':
                return date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime($item[$column_name]));
            default:
                return print_r( $item, true ); //Show the whole array for troubleshooting purposes
        }
    }

    /**
     * Render the bulk edit checkbox.
     *
     * @param array $item
     * @return string
     */
    function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
        );
    }

    /**
     * Method for name column
     *
     * @param array $item an array of DB data
     * @return string
     */
    function column_nom( $item ) {
        $delete_nonce = wp_create_nonce( 'rpp_delete_reservation' );
        $title = '<strong>' . $item['nom'] . ' ' . $item['prenom'] . '</strong>';
        $actions = [
            'delete' => sprintf( '<a href="?page=%s&action=%s&reservation=%s&_wpnonce=%s">Supprimer</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce )
        ];
        return $title . $this->row_actions( $actions );
    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    public function get_columns() {
        $columns = [
            'cb'      => '<input type="checkbox" />',
            'nom'     => __( 'Nom & Prénom', 'reservations-personnalisees-pro' ),
            'email'   => __( 'Email', 'reservations-personnalisees-pro' ),
            'date_reservation' => __( 'Date', 'reservations-personnalisees-pro' ),
            'heure_reservation' => __( 'Heure', 'reservations-personnalisees-pro' ),
            'sujets' => __( 'Sujets', 'reservations-personnalisees-pro' ),
            'created_at' => __( 'Créé le', 'reservations-personnalisees-pro' )
        ];
        return $columns;
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'nom' => array( 'nom', true ),
            'email' => array( 'email', false ),
            'date_reservation' => array( 'date_reservation', false ),
            'created_at' => array( 'created_at', true )
        );
        return $sortable_columns;
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions() {
        $actions = [
            'bulk-delete' => 'Supprimer'
        ];
        return $actions;
    }

    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items() {
        $this->_column_headers = $this->get_column_info();

        $this->process_bulk_action();

        $per_page     = $this->get_items_per_page( 'reservations_per_page', 20 );
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count();

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page
        ] );

        $this->items = self::get_reservations( $per_page, $current_page );
    }

    /**
     * Delete a reservation record.
     *
     * @param int $id reservation ID
     */
    public static function delete_reservation( $id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservations_personnalisees';

        $wpdb->delete(
            $table_name,
            [ 'id' => $id ],
            [ '%d' ]
        );
    }

    public function process_bulk_action() {
        //Detect when a bulk action is being triggered...
        if ( 'delete' === $this->current_action() ) {
            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );
            if ( ! wp_verify_nonce( $nonce, 'rpp_delete_reservation' ) ) {
                die( 'Go get a life script kiddies' );
            }
            else {
                self::delete_reservation( absint( $_GET['reservation'] ) );
                // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
                wp_redirect( esc_url_raw( add_query_arg( array( 'page' => $_REQUEST['page'] ), admin_url( 'admin.php' ) ) ) );
                exit;
            }
        }
        // If the delete bulk action is triggered
        if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
             || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
        ) {
            $delete_ids = esc_sql( $_POST['bulk-delete'] );
            // loop over the array of record IDs and delete them
            foreach ( $delete_ids as $id ) {
                self::delete_reservation( $id );
            }
            wp_redirect( esc_url_raw( add_query_arg( array( 'page' => $_REQUEST['page'] ), admin_url( 'admin.php' ) ) ) );
            exit;
        }
    }
}