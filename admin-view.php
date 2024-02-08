<?php
if (!defined('ABSPATH')) {
    exit;
}

function rtc_ritregistratie_admin_menu() {
    add_menu_page(
        'RTC Ritregistraties',    // Page title
        'Ritregistraties',             // Menu title
        'view_ritregistratie',         // Capability required to see this menu item
        'rtc-ritregistratie-admin',    // Menu slug, used to refer to this menu in URL
        'rtc_ritregistratie_admin_page', // Function that outputs the content for this page.
        'dashicons-admin-site',        // Icon URL
        6                              // Position where the menu should appear
    );
}

add_action('admin_menu', 'rtc_ritregistratie_admin_menu');

function rtc_ritregistratie_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rtc_ritregistratie';

    // Handle delete request
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['entry_id']) && isset($_GET['nonce'])) {
        $entry_id = intval($_GET['entry_id']);
        $nonce = sanitize_text_field($_GET['nonce']);
        if (wp_verify_nonce($nonce, 'delete_ride_' . $entry_id)) {
            $wpdb->delete($table_name, ['id' => $entry_id]);
        }
    }

    // Month-Year filter logic
    $month_year_filter = isset($_GET['month_year_filter']) ? sanitize_text_field($_GET['month_year_filter']) : '';

    // Fetch distinct months and years for the filter
    $distinct_months_years = $wpdb->get_results("SELECT DISTINCT MONTH(ride_date) as month, YEAR(ride_date) as year FROM $table_name ORDER BY YEAR(ride_date) DESC, MONTH(ride_date) DESC");

    // Pagination logic
    $per_page = 100;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;

    // SQL to fetch data with filters
    $sql = "SELECT * FROM $table_name";
    if (!empty($month_year_filter)) {
        [$year, $month] = explode('-', $month_year_filter);
        $sql .= $wpdb->prepare(" WHERE YEAR(ride_date) = %d AND MONTH(ride_date) = %d", $year, $month);
    }
    $sql .= " ORDER BY ride_date ASC"; // Add this line to sort by date ascending
    $sql .= " LIMIT ${offset}, ${per_page}";
    $registrations = $wpdb->get_results($sql);

    // Total count for pagination
    $total_query = "SELECT COUNT(1) FROM $table_name";
    if (!empty($month_year_filter)) {
        $total_query .= $wpdb->prepare(" WHERE YEAR(ride_date) = %d AND MONTH(ride_date) = %d", $year, $month);
    }
    $total = $wpdb->get_var($total_query);

    // Display month-year filter form
    echo '<div class="wrap"><h1>Alle registraties</h1>';
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="rtc-ritregistratie-admin" />';
    echo '<select name="month_year_filter">';
    echo '<option value="">Selecteer maand en jaar</option>';
    foreach ($distinct_months_years as $my) {
        $month_year_value = $my->year . '-' . str_pad($my->month, 2, '0', STR_PAD_LEFT);
        $month_year_text = date('F Y', mktime(0, 0, 0, $my->month, 1, $my->year));
        echo '<option value="' . esc_attr($month_year_value) . '"' . selected($month_year_value, $month_year_filter, false) . '>' . esc_html($month_year_text) . '</option>';
    }
    echo '</select>';
    echo '<input type="submit" value="Filter" />';
    echo '</form>';

    // Table display
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Lid</th><th>Datum</th><th>Type</th><th>Omschrijving</th><th>Kilometers</th><th>Duur</th><th>Verwijderen</th></tr></thead>';
    echo '<tbody>';
    foreach ($registrations as $registration) {
        $user_info = get_userdata($registration->user_id);
        $user_name = $user_info ? $user_info->first_name . ' ' . $user_info->last_name : 'Unknown User';
        $user_edit_link = esc_url(admin_url('user-edit.php?user_id=' . $registration->user_id));
        $user_name_link = "<a href='{$user_edit_link}'>" . esc_html($user_name) . "</a>";
        $formatted_date = date('d-m-Y', strtotime($registration->ride_date));
        $ride_type_label = rtc_ritregistratie_get_pretty_ride_type($registration->ride_type);

        // Add delete link
        $delete_nonce = wp_create_nonce('delete_ride_' . $registration->id);
        $delete_link = esc_url(add_query_arg([
            'action' => 'delete', 
            'entry_id' => $registration->id, 
            'nonce' => $delete_nonce
        ], admin_url('admin.php?page=rtc-ritregistratie-admin')));

        echo sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td><a href="%s" onclick="return confirm(\'Deze rit verwijderen?\');">Rit verwijderen</a></td></tr>',
            $user_name_link,
            esc_html($formatted_date),
            esc_html($ride_type_label),
            esc_html($registration->ride_description),
            esc_html($registration->kilometers),
            esc_html($registration->duration),
            $delete_link
        );
    }
    
    echo '</tbody></table>';

    // Pagination logic
    $per_page = 100;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;

    // SQL to fetch data with filters
    $sql = "SELECT * FROM $table_name";
    if (!empty($month_year_filter)) {
        [$year, $month] = explode('-', $month_year_filter);
        $sql .= $wpdb->prepare(" WHERE YEAR(ride_date) = %d AND MONTH(ride_date) = %d", $year, $month);
    }
    $sql .= " LIMIT ${offset}, ${per_page}";
    $registrations = $wpdb->get_results($sql);

    // Total count for pagination
    $total_query = "SELECT COUNT(1) FROM $table_name";
    if (!empty($month_year_filter)) {
        $total_query .= $wpdb->prepare(" WHERE YEAR(ride_date) = %d AND MONTH(ride_date) = %d", $year, $month);
    }
    $total = $wpdb->get_var($total_query);

   // Pagination display
   $total_pages = ceil($total / $per_page);
   if ($total_pages > 1) {
       $page_links = paginate_links(array(
           'base' => add_query_arg('paged', '%#%'),
           'format' => '',
           'prev_text' => __('&laquo;'),
           'next_text' => __('&raquo;'),
           'total' => $total_pages,
           'current' => $current_page
       ));
       echo '<div id="pagination" style="margin-top: 10px;">' . $page_links . '</div>';
   }

    // Download CSV button
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="download_csv">';
    if (!empty($month_year_filter)) {
        echo '<input type="hidden" name="month_year_filter" value="' . esc_attr($month_year_filter) . '">';
    }
    echo '<input type="submit" value="Download CSV">';
    echo '</form>';
    echo '</div>'; // Closing div.wrap
}

add_action('admin_post_download_csv', 'rtc_ritregistratie_download_csv');

function rtc_ritregistratie_get_pretty_ride_type($ride_type_key) {
    $ride_type_mapping = array(
        'persoonlijk' => 'Persoonlijk',
        'rtc-veluwerijders' => 'RTC Veluwerijders',
        // Add more mappings as needed
    );

    return isset($ride_type_mapping[$ride_type_key]) ? $ride_type_mapping[$ride_type_key] : $ride_type_key;
}

function rtc_ritregistratie_download_csv() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rtc_ritregistratie';

    // Check for month-year filter from the request and sanitize it
    $month_year_filter = isset($_REQUEST['month_year_filter']) ? sanitize_text_field($_REQUEST['month_year_filter']) : '';

    // Start the query to select all entries
    $sql = "SELECT * FROM $table_name";
    if (!empty($month_year_filter)) {
        [$year, $month] = explode('-', $month_year_filter);
        $sql .= $wpdb->prepare(" WHERE YEAR(ride_date) = %d AND MONTH(ride_date) = %d", $year, $month);
    }
    $registrations = $wpdb->get_results($sql, ARRAY_A);

    // Check if headers already sent
    if (headers_sent()) {
        wp_die('Unable to download CSV, headers already sent');
    }

    // Format the filename with the current date and a meaningful name
    $filename_date_part = $current_date = date('Y-m-d');
    $filename = $filename_date_part . '-veluwerijders-ritregistraties-leden.csv';

    // Set headers for download
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");

    $output = fopen('php://output', 'w');

    // Add CSV column headers
    if (!empty($registrations)) {
        // Add 'user_name' to the column headers
        $headers = array_keys(current($registrations));
        array_splice($headers, 1, 0, 'user_name'); // Insert 'user_name' after 'user_id'
        fputcsv($output, $headers);
    }

    // Add data rows
    foreach ($registrations as $row) {
        // Fetch user data
        $user_info = get_userdata($row['user_id']);
        $user_name = $user_info ? $user_info->first_name . ' ' . $user_info->last_name : 'Unknown User';
        array_splice($row, 1, 0, $user_name); // Insert 'user_name' after 'user_id'
        $row['ride_date'] = date('d-m-Y', strtotime($row['ride_date'])); // Format date for CSV
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}
