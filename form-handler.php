<?php
// form-handler.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function rtc_ritregistratie_ajax_form_submission() {
    global $wpdb;

    // Verify nonce for CSRF protection
    if (!isset($_POST['rtc_nonce']) || !wp_verify_nonce($_POST['rtc_nonce'], 'rtc_ritregistratie_submit_ride')) {
        wp_send_json_error('Beveiligingscontrole mislukt. Vernieuw de pagina en probeer opnieuw.');
        wp_die();
    }

    // Verify user is logged in
    $user_id = get_current_user_id();
    if ($user_id === 0) {
        wp_send_json_error('Je moet ingelogd zijn om een rit te registreren.');
        wp_die();
    }

    // Check if the form is submitted
    if (isset($_POST['rtc_ritregistratie_submit'])) {

        // Sanitize and validate mandatory fields
        $ride_date = isset($_POST['ride_date']) ? sanitize_text_field($_POST['ride_date']) : null;
        $ride_type = isset($_POST['ride_type']) ? sanitize_text_field($_POST['ride_type']) : null;
        $kilometers = isset($_POST['kilometers']) ? filter_var($_POST['kilometers'], FILTER_VALIDATE_FLOAT) : null;

        // Optional fields
        $ride = isset($_POST['ride']) ? sanitize_textarea_field($_POST['ride']) : '';
        $duration_hours = isset($_POST['duration_hours']) ? intval($_POST['duration_hours']) : 0;
        $duration_minutes = isset($_POST['duration_minutes']) ? intval($_POST['duration_minutes']) : 0;

        // Check for empty mandatory fields
        $errors = array();
        if (!$ride_date) $errors['ride_date'] = 'Ride date is missing';
        if (!$ride_type) $errors['ride_type'] = 'Ride type is missing';
        if ($kilometers === null) $errors['kilometers'] = 'Kilometers is missing';

        // Combine duration hours and minutes
        $duration = sprintf('%02d:%02d', $duration_hours, $duration_minutes);

        if (count($errors) === 0) {
            // Insert data into the database
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'rtc_ritregistratie',
                array(
                    'user_id' => $user_id,
                    'ride_date' => $ride_date,
                    'ride_type' => $ride_type,
                    'ride_description' => $ride,
                    'kilometers' => $kilometers,
                    'duration' => $duration,
                ),
                array('%d', '%s', '%s', '%s', '%f', '%s')
            );

            if ($inserted === false) {
                wp_send_json_error('Er is een fout opgetreden bij het opslaan.');
            } else {
                wp_send_json_success('Gelukt! De rit is opgeslagen.');
            }
        } else {
            wp_send_json_error('Niet alle verplichte velden zijn ingevuld.');
        }
    }

    wp_die();
}


add_action('wp_ajax_rtc_ritregistratie_handle_form', 'rtc_ritregistratie_ajax_form_submission');
