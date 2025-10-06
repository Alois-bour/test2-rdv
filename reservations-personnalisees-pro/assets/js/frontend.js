(function($) {
    'use strict';

    $(function() {
        const $form = $('#reservation-form');
        const $dateSelect = $('#date-select');
        const $heureSelect = $('#heure-select');
        const $message = $('#reservation-message');

        // --- 1. Populate Date and Time Fields ---

        function populateDates() {
            const today = new Date();
            for (let i = 0; i < 30; i++) { // Show next 30 days
                let currentDate = new Date();
                currentDate.setDate(today.getDate() + i);

                // Skip weekends (Saturday=6, Sunday=0)
                if (currentDate.getDay() === 0 || currentDate.getDay() === 6) {
                    continue;
                }

                let dateString = currentDate.getFullYear() + '-' + ('0' + (currentDate.getMonth() + 1)).slice(-2) + '-' + ('0' + currentDate.getDate()).slice(-2);
                $dateSelect.append($('<option>', {
                    value: dateString,
                    text: currentDate.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
                }));
            }
        }

        function populateHeures() {
            // Example time slots from 9:00 to 17:00, every 30 mins
            for (let hour = 9; hour <= 17; hour++) {
                let time = (hour < 10 ? '0' : '') + hour + ':00';
                $heureSelect.append($('<option>', { value: time, text: time }));
                if (hour < 17) {
                    let time30 = (hour < 10 ? '0' : '') + hour + ':30';
                    $heureSelect.append($('<option>', { value: time30, text: time30 }));
                }
            }
        }

        if ($dateSelect.length) {
            populateDates();
            populateHeures();
            checkAvailability(); // Initial check
        }

        // --- 2. AJAX Availability Check ---

        function checkAvailability() {
            const date = $dateSelect.val();
            const heure = $heureSelect.val();

            if (!date || !heure) return;

            $.ajax({
                url: rpp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rpp_check_availability',
                    date: date,
                    heure: heure,
                    nonce: $('#rpp_nonce').val()
                },
                success: function(response) {
                    if (!response.success) {
                        // Slot is not available, disable submit button
                        $form.find('button[type="submit"]').prop('disabled', true);
                        displayMessage(response.data.message, 'error');
                    } else {
                        $form.find('button[type="submit"]').prop('disabled', false);
                        $message.empty().removeClass('success error').hide();
                    }
                }
            });
        }

        $dateSelect.on('change', checkAvailability);
        $heureSelect.on('change', checkAvailability);

        // --- 3. Form Submission ---

        $form.on('submit', function(e) {
            e.preventDefault();

            const $submitButton = $form.find('button[type="submit"]');
            $submitButton.text(rpp_ajax.i18n.verifying).prop('disabled', true);

            if (rpp_ajax.recaptcha_site_key && typeof grecaptcha !== 'undefined') {
                grecaptcha.ready(function() {
                    grecaptcha.execute(rpp_ajax.recaptcha_site_key, { action: 'submit' }).then(function(token) {
                        $('#recaptcha-token').val(token);
                        submitForm();
                    });
                });
            } else {
                submitForm();
            }
        });

        function submitForm() {
            // Clear previous messages
            $message.empty().removeClass('success error').hide();

            // Basic Validation
            let isValid = true;
            $form.find('[required]').each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).css('border-color', 'red');
                } else {
                    $(this).css('border-color', '#ddd');
                }
            });

            if (!isValid) {
                displayMessage(rpp_ajax.i18n.fill_all_fields, 'error');
                $form.find('button[type="submit"]').text(rpp_ajax.i18n.reserve).prop('disabled', false);
                return;
            }

            const formData = $form.serialize();
            const $submitButton = $form.find('button[type="submit"]');

            $.ajax({
                url: rpp_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=rpp_submit_reservation',
                beforeSend: function() {
                    $submitButton.text(rpp_ajax.i18n.sending).prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        displayMessage(response.data.message, 'success');
                        $form[0].reset();
                        populateDates(); // Repopulate to reset selections
                        populateHeures();
                    } else {
                        displayMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    displayMessage(rpp_ajax.i18n.technical_error, 'error');
                },
                complete: function() {
                    $submitButton.text(rpp_ajax.i18n.reserve).prop('disabled', false);
                }
            });
        }

        function displayMessage(msg, type) {
            $message.text(msg).removeClass('success error').addClass(type).show();
        }
    });

})(jQuery);