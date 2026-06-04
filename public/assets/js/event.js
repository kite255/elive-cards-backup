
// js for event registration form
    $(document).ready(function() {
        const form = $('.event-registration-form');
        if (!form.length) return; // Exit if the form doesn't exist

        // Initialize datepicker for the event date only
        $('#event_date').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true,
            clearBtn: true,
            startDate: new Date(),
            changeMonth: true,
            changeYear: true
        });

        // Clipboard.js logic
        var clipboard = new ClipboardJS('.copy-btn');

        clipboard.on('success', function(e) {
            var originalHTML = e.trigger.innerHTML;
            e.trigger.innerHTML = '<i class="bi bi-check2"></i>';
            e.trigger.title = 'Copied!';
            setTimeout(function() {
                e.trigger.innerHTML = originalHTML;
                e.trigger.title = 'Copy link';
            }, 2000);
            e.clearSelection();
        });

        clipboard.on('error', function(e) {
            alert('Failed to copy text. Please try again.');
        });

        // Add error message and success icon
        form.find('input, select').each(function() {
            $(this).after('<p class="error-message" style="color: red; font-size: 0.8rem; display: none;"></p>');
            $(this).css('padding-right', '30px');
            $(this).parent().css('position', 'relative');
            $(this).parent().append('<span class="success-icon" style="color: #28a745; display: none; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); z-index: 10;"><i class="bi bi-check-circle-fill"></i></span>');
        });

        // Validation functions
        function validateRequired(value, fieldName) {
            return !value.trim() ? `${fieldName} is required` : '';
        }

        function validatePhone(value) {
            if (!value) return '';
            return /^(07|06)\d{8}$/.test(value) ? '' : 'Phone number must start with 07 or 06 followed by 8 digits';
        }

        function validateEmail(value) {
            if (!value) return '';
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ? '' : 'Please enter a valid email address';
        }

        function validateUrl(value) {
            if (!value) return '';
            try {
                const url = new URL(value);
                const lower = value.toLowerCase();
                return (lower.includes('maps.google.com') || lower.includes('goo.gl/maps') || lower.includes('maps.app.goo.gl')) ? '' : 'Please enter a valid Google Maps URL';
            } catch {
                return 'Please enter a valid URL';
            }
        }

        function validateEventDate(value) {
            if (!value) return 'Event date is required';
            const selectedDate = new Date(value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            return selectedDate < today ? 'Event date cannot be in the past' : '';
        }

        function validateField(input) {
            const value = input.val();
            const fieldName = input.prev('label').text().replace(' *', '');
            let error = '';

            const errorElement = input.next('.error-message');
            const successIcon = input.parent().find('.success-icon');

            errorElement.hide();
            successIcon.hide();
            input.removeClass('is-invalid is-valid');

            if (input.prop('required')) {
                error = validateRequired(value, fieldName);
            }

            if (!error) {
                switch (input.attr('id')) {
                    case 'contact_phone':
                        error = validatePhone(value);
                        break;
                    case 'contact_email':
                        error = validateEmail(value);
                        break;
                    case 'venue_map':
                        error = validateUrl(value);
                        break;
                    case 'event_date':
                        error = validateEventDate(value);
                        break;
                }
            }

            if (error) {
                input.addClass('is-invalid');
                errorElement.text(error).show();
                return false;
            } else if (value) {
                input.addClass('is-valid');
                successIcon.show();
                return true;
            }
            return true;
        }

        // Custom CSS for valid/invalid styling
        const styleTag = document.createElement('style');
        styleTag.textContent = `
        .is-invalid { border-color: #dc3545 !important; background-image: none !important; }
        .is-valid { border-color: #28a745 !important; background-image: none !important; }
        .form-control:focus { box-shadow: none !important; }
    `;
        document.head.appendChild(styleTag);

        // Real-time validation
        form.find('input, select').on('input change blur', function() {
            validateField($(this));
        });

        // Restrict phone input to digits only
        $('#contact_phone').on('input', function() {
            this.value = this.value.replace(/[^\d]/g, '').slice(0, 10);
        });

        // Handle form submit
        form.on('submit', function(e) {
            e.preventDefault();
            let isValid = true;
            form.find('input, select').each(function() {
                if (!validateField($(this))) {
                    isValid = false;
                }
            });
            if (isValid) {
                this.submit();
            }
        });
    });


    // js for date picker in event filter only
    $(function() {
        $('.filter-form .datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            clearBtn: true
        });
    });


