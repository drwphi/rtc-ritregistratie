<?php
// form-handler.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function rtc_ritregistratie_ajax_form_submission() {
    global $wpdb;
    error_log('AJAX handler started');

    // Check if the form is submitted
    if (isset($_POST['rtc_ritregistratie_submit'])) {
        error_log('Form submission detected');

        // Sanitize and validate mandatory fields
        $ride_date = isset($_POST['ride_date']) ? sanitize_text_field($_POST['ride_date']) : null;
        $ride_type = isset($_POST['ride_type']) ? sanitize_text_field($_POST['ride_type']) : null;
        $kilometers = isset($_POST['kilometers']) ? filter_var($_POST['kilometers'], FILTER_VALIDATE_FLOAT) : null;

        // Optional fields
        $ride = isset($_POST['ride']) ? sanitize_textarea_field($_POST['ride']) : '';
        $duration_hours = isset($_POST['duration_hours']) ? intval($_POST['duration_hours']) : 0;
        $duration_minutes = isset($_POST['duration_minutes']) ? intval($_POST['duration_minutes']) : 0;

        error_log("Ride Date: $ride_date, Ride Type: $ride_type, Kilometers: $kilometers");

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
                    'user_id' => get_current_user_id(),
                    'ride_date' => $ride_date,
                    'ride_type' => $ride_type,
                    'ride_description' => $ride,
                    'kilometers' => $kilometers,
                    'duration' => $duration,
                ),
                array('%d', '%s', '%s', '%s', '%f', '%s')
            );

            if ($inserted === false) {
                error_log('Database insertion failed: ' . $wpdb->last_error);
                wp_send_json_error('Database insertion failed');
            } else {
                wp_send_json_success('Gelukt! De rit is opgeslagen. Bekijk jouw ritten <a href="https://www.veluwerijders.nl/ritten-overzicht/">hier</a>.');
            }
        } else {
            error_log('Validation errors: ' . json_encode($errors));
            wp_send_json_error('Form validation errors');
        }
    } else {
        error_log('Form not submitted or unrecognized submission');
    }

    wp_die();
}


add_action('wp_ajax_rtc_ritregistratie_handle_form', 'rtc_ritregistratie_ajax_form_submission');
add_action('wp_ajax_nopriv_rtc_ritregistratie_handle_form', 'rtc_ritregistratie_ajax_form_submission');
