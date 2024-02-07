<?php
/**
 * Plugin Name: RTC Ritregistratie
 * Plugin URI: https://strila.nl/wordpress-website-laten-maken-groningen/
 * Description: Ritregistratie voor leden van RTC Veluwerijders.
 * Version: 0.6
 * Author: Daniel Philipsen
 * Author URI: https://strila.nl/
 */

if (!defined('ABSPATH')) {
    exit;
}

// Function to create the database table
function rtc_ritregistratie_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rtc_ritregistratie';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        ride_date date DEFAULT '0000-00-00' NOT NULL,
        ride_type text NOT NULL,
        ride_description text NOT NULL,
        kilometers decimal(5,2) NOT NULL,
        duration VARCHAR(5) DEFAULT '00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'rtc_ritregistratie_create_table');

// Function to display the registration form via shortcode
function rtc_ritregistratie_form_shortcode() {
    ob_start();
    include_once plugin_dir_path(__FILE__) . 'form-ride-entry.php';
    return ob_get_clean();
}

add_shortcode('rtc_ritregistratie_form', 'rtc_ritregistratie_form_shortcode');

// Function to enqueue scripts and styles
function rtc_ritregistratie_enqueue_scripts() {
    wp_enqueue_script('rtc-ritregistratie-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), null, true);
    wp_enqueue_style('rtc-ritregistratie-style', plugin_dir_url(__FILE__) . 'styles.css');
    wp_localize_script('rtc-ritregistratie-script', 'rtcRitregistratieAjax', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}

add_action('wp_enqueue_scripts', 'rtc_ritregistratie_enqueue_scripts');

include_once plugin_dir_path(__FILE__) . 'form-handler.php';

// Function to show user registrations via shortcode
function rtc_ritregistratie_show_user_registrations() {
    global $wpdb;
    $user_id = get_current_user_id();

    if ($user_id == 0) {
        return "Je moet ingelogd zijn om ritten te kunnen registreren en zien.";
    }

    // Handle delete request
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['entry_id']) && isset($_GET['nonce'])) {
        $entry_id = intval($_GET['entry_id']);
        $nonce = sanitize_text_field($_GET['nonce']);

        if (wp_verify_nonce($nonce, 'delete_ride_' . $entry_id)) {
            $wpdb->delete($wpdb->prefix . 'rtc_ritregistratie', ['id' => $entry_id, 'user_id' => $user_id]);
        }
    }

    $table_name = $wpdb->prefix . 'rtc_ritregistratie';
    $registrations = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d",
        $user_id
    ));

    if (!$registrations) {
        return "Nog geen geregistreerde ritten.";
    }

    // Mapping for ride_type values
    $ride_type_mapping = array(
        'persoonlijk' => 'Persoonlijk',
        'rtc-veluwerijders' => 'RTC (Veluwerijders)'
    );

    $output = '<div class="table-responsive">';
    $output .= '<table class="rtc-user-registrations"><tr><th>Datum</th><th>Type</th><th>Omschrijving</th><th>Kilometers</th><th>Duur</th><th>Acties</th></tr>';
    foreach ($registrations as $registration) {
        $formatted_date = date('d-m-Y', strtotime($registration->ride_date));
        $ride_type_label = isset($ride_type_mapping[$registration->ride_type]) ? $ride_type_mapping[$registration->ride_type] : 'Onbekend';
        $formatted_duration = date('H:i', strtotime($registration->duration));

        // Add delete link
        $delete_nonce = wp_create_nonce('delete_ride_' . $registration->id);
        $delete_link = esc_url(add_query_arg([
            'action' => 'delete', 
            'entry_id' => $registration->id, 
            'nonce' => $delete_nonce
        ], $_SERVER['REQUEST_URI']));

        $output .= sprintf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td><a href="%s" onclick="return confirm(\'Weet je zeker dat je deze rit wilt verwijderen?\');">Verwijderen</a></td></tr>',
            esc_html($formatted_date),
            esc_html($ride_type_label),
            esc_html($registration->ride_description),
            esc_html($registration->kilometers),
            esc_html($formatted_duration),
            $delete_link
        );// Function to show user registrations via shortcode
        function rtc_ritregistratie_show_user_registrations() {
            global $wpdb;
            $user_id = get_current_user_id();
        
            if ($user_id == 0) {
                return "Je moet ingelogd zijn om ritten te kunnen registreren en zien.";
            }
        
            // Handle delete request
            if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['entry_id']) && isset($_GET['nonce'])) {
                $entry_id = intval($_GET['entry_id']);
                $nonce = sanitize_text_field($_GET['nonce']);
        
                if (wp_verify_nonce($nonce, 'delete_ride_' . $entry_id)) {
                    $wpdb->delete($wpdb->prefix . 'rtc_ritregistratie', ['id' => $entry_id, 'user_id' => $user_id]);
                }
            }
        
            $table_name = $wpdb->prefix . 'rtc_ritregistratie';
            $registrations = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d ORDER BY ride_date DESC",
                $user_id
            ));
        
            if (!$registrations) {
                return "Nog geen geregistreerde ritten.";
            }
        
            // Calculate total kilometers
            $total_kilometers = 0;
            foreach ($registrations as $registration) {
                $total_kilometers += $registration->kilometers;
            }
        
            // Mapping for ride_type values
            $ride_type_mapping = array(
                'persoonlijk' => 'Persoonlijk',
                'rtc-veluwerijders' => 'RTC (Veluwerijders)'
            );
        
            $output = '<div class="table-responsive">';
            $output .= '<table class="rtc-user-registrations"><tr><th>Datum</th><th>Type</th><th>Omschrijving</th><th>Kilometers</th><th>Duur</th><th>Acties</th></tr>';
            foreach ($registrations as $registration) {
                $formatted_date = date('d-m-Y', strtotime($registration->ride_date));
                $ride_type_label = isset($ride_type_mapping[$registration->ride_type]) ? $ride_type_mapping[$registration->ride_type] : 'Onbekend';
                $formatted_duration = date('H:i', strtotime($registration->duration));
        
                // Add delete link
                $delete_nonce = wp_create_nonce('delete_ride_' . $registration->id);
                $delete_link = esc_url(add_query_arg([
                    'action' => 'delete', 
                    'entry_id' => $registration->id, 
                    'nonce' => $delete_nonce
                ], $_SERVER['REQUEST_URI']));
        
                $output .= sprintf(
                    '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td><a href="%s" onclick="return confirm(\'Weet je zeker dat je deze rit wilt verwijderen?\');">Verwijderen</a></td></tr>',
                    esc_html($formatted_date),
                    esc_html($ride_type_label),
                    esc_html($registration->ride_description),
                    esc_html($registration->kilometers),
                    esc_html($formatted_duration),
                    $delete_link
                );
            }
            $output .= sprintf('<tr><td colspan="3">Totaal kilometers</td><td>%s</td><td colspan="2"></td></tr>', $total_kilometers);
            $output .= '</table>';
            $output .= '</div>';
        
            return $output;
        }
    }
    $output .= '</table>';
    $output .= '</div>';

    return $output;
}

add_shortcode('user_registrations', 'rtc_ritregistratie_show_user_registrations');

include_once plugin_dir_path(__FILE__) . 'admin-view.php';

// Additional function to enqueue custom styles
function rtc_ritregistratie_enqueue_custom_styles() {
    wp_enqueue_style('rtc-custom-styles', plugin_dir_url(__FILE__) . 'styles.css');
}
add_action('wp_enqueue_scripts', 'rtc_ritregistratie_enqueue_custom_styles');
