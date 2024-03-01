jQuery(document).ready(function($) {
    $('#rit-registratie').submit(function(e) {
        e.preventDefault();

        var formData = $(this).serialize();
        formData += '&rtc_ritregistratie_submit=' + encodeURIComponent($('input[type=submit]', this).val());

        $.ajax({
            url: rtcRitregistratieAjax.ajax_url,
            type: 'POST',
            data: formData + '&action=rtc_ritregistratie_handle_form',
            success: function(response) {
                if (response.success) {
                    // Update the message container with the success message
                    $('#form-message').html('<p style="color: green;">' + response.data + '</p>');
                } else {
                    // Handle errors
                    if (response.data && response.data.errors) {
                        var errorMessages = Object.values(response.data.errors).join('<br>');
                        $('#form-message').html('<p style="color: red;">Fouten: ' + errorMessages + '</p>');
                    } else {
                        $('#form-message').html('<p style="color: red;">Er is een onbekende fout opgetreden.</p>');
                    }
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Handle AJAX errors
                $('#form-message').html('<p style="color: red;">AJAX error: ' + textStatus + ', ' + errorThrown + '</p>');
            }
        });
    });
});

    // New removal functionality
    $(document).on('click', '.remove-registration', function() {
        var row = $(this).closest('tr');
        var registrationId = $(this).data('id');
        
        $.ajax({
            url: rtcRitregistratieAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'remove_registration',
                registration_id: registrationId
            },
            success: function(response) {
                if (response.success) {
                    row.remove();
                    // Optionally, update the total kilometers displayed
                    // This part needs to be implemented based on your specific requirements
                } else {
                    alert('Could not remove the registration. Please try again.');
                }
            }
        });
    });