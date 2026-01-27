jQuery(document).ready(function ($) {
    var currentStep = 1;
    var isMultiStep = $('.multistep-form').length > 0;

    // Multi-step navigation
    $('.btn-next').on('click', function () {
        var nextStep = $(this).data('next');

        // Validate current step
        var currentStepContent = $('[data-step-content="' + currentStep + '"]');
        var isValid = validateStep(currentStepContent);

        if (isValid) {
            // Hide current step
            currentStepContent.fadeOut(300, function () {
                // Show next step
                $('[data-step-content="' + nextStep + '"]').fadeIn(300);

                // Update progress
                updateProgress(nextStep);
                currentStep = nextStep;

                // Scroll to top
                $('html, body').animate({ scrollTop: $('.approval-form-container').offset().top - 20 }, 400);
            });
        }
    });

    $('.btn-prev').on('click', function () {
        var prevStep = $(this).data('prev');

        // Hide current step
        $('[data-step-content="' + currentStep + '"]').fadeOut(300, function () {
            // Show previous step
            $('[data-step-content="' + prevStep + '"]').fadeIn(300);

            // Update progress
            updateProgress(prevStep);
            currentStep = prevStep;

            // Scroll to top
            $('html, body').animate({ scrollTop: $('.approval-form-container').offset().top - 20 }, 400);
        });
    });

    function updateProgress(step) {
        $('.form-step').removeClass('active completed');

        $('.form-step').each(function () {
            var stepNum = parseInt($(this).data('step'));
            if (stepNum < step) {
                $(this).addClass('completed');
            } else if (stepNum === step) {
                $(this).addClass('active');
            }
        });
    }

    function validateStep(stepContent) {
        var isValid = true;
        var requiredFields = stepContent.find('input[required], textarea[required], select[required]');

        requiredFields.each(function () {
            if (!$(this).val()) {
                isValid = false;
                $(this).css('border-color', '#ef4444');

                $(this).one('input change', function () {
                    $(this).css('border-color', '');
                });
            }
        });

        if (!isValid) {
            var strings = (typeof approval_ajax !== 'undefined' && approval_ajax.strings) ? approval_ajax.strings : {};
            showMessage(strings.fill_required || 'Please fill in all required fields.', 'error');
        }

        return isValid;
    }

    function showMessage(message, type) {
        var $messages = $('#approval-form-messages');
        $messages.html('<div class="approval-message ' + type + '">' + message + '</div>');

        setTimeout(function () {
            $messages.find('.approval-message').fadeOut(300, function () {
                $(this).remove();
            });
        }, 4000);
    }

    // Form submission
    $('#approval-form').on('submit', function (e) {
        e.preventDefault();

        var $form = $(this);
        var $submitBtn = $form.find('.btn-submit');
        var $messages = $('#approval-form-messages');
        var strings = (typeof approval_ajax !== 'undefined' && approval_ajax.strings) ? approval_ajax.strings : {};

        // Disable submit button
        var originalText = $submitBtn.html();
        var submittingText = strings.submitting || 'Submitting...';
        $submitBtn.prop('disabled', true).html('<span class="spinning">⏳</span> ' + submittingText);

        // Clear previous messages
        $messages.empty();

        $.ajax({
            url: approval_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'submit_approval_request',
                nonce: approval_ajax.nonce,
                title: $form.find('[name="title"]').val(),
                description: $form.find('[name="description"]').val(),
                submitter_name: $form.find('[name="submitter_name"]').val(),
                submitter_email: $form.find('[name="submitter_email"]').val(),
                priority: $form.find('[name="priority"]').val(),
                category: $form.find('[name="category"]').val()
            },
            success: function (response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    $form[0].reset();

                    // Reset to first step if multi-step
                    if (isMultiStep) {
                        setTimeout(function () {
                            $('[data-step-content]').hide();
                            $('[data-step-content="1"]').show();
                            updateProgress(1);
                            currentStep = 1;
                        }, 2000);
                    }
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function () {
                showMessage(strings.error || 'An error occurred. Please try again.', 'error');
            },
            complete: function () {
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Real-time validation
    $('.approval-form input, .approval-form textarea, .approval-form select').on('blur', function () {
        if ($(this).attr('required') && !$(this).val()) {
            $(this).css('border-color', '#fbbf24');
        } else {
            $(this).css('border-color', '');
        }
    });

    // Entrance animation
    $('.approval-form-container').css({
        'opacity': '0',
        'transform': 'translateY(30px)'
    }).animate({ 'opacity': '1' }, {
        duration: 600,
        step: function (now) {
            $(this).css('transform', 'translateY(' + (30 - (now * 30)) + 'px)');
        }
    });
});
