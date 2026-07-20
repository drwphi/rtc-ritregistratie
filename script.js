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
                var $msg = $('<p></p>');
                if (response.success) {
                    // Server message via .text() (XSS-safe); the link is a fixed,
                    // hardcoded element so no untrusted data is ever inserted as HTML.
                    $msg.css('color', 'green').text(response.data + ' ');
                    var $link = $('<a></a>')
                        .attr('href', 'https://www.veluwerijders.nl/ritten-overzicht/')
                        .text('Bekijk jouw ritten hier');
                    $msg.append($link);
                    $('#form-message').empty().append($msg);
                    $('#rit-registratie')[0].reset();
                } else {
                    $msg.css('color', 'red').text(response.data || 'Er is een onbekende fout opgetreden.');
                    $('#form-message').empty().append($msg);
                }
            },
            error: function() {
                var $msg = $('<p></p>').css('color', 'red').text('Er is een fout opgetreden bij het verzenden.');
                $('#form-message').empty().append($msg);
            }
        });
    });
});
