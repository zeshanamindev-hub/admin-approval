<?php
/**
 * Shortcodes for the Approval Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class ApprovalShortcodes {
    
    public function __construct() {
        add_shortcode('approval_form', array($this, 'approval_form_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_ajax_submit_approval_request', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_submit_approval_request', array($this, 'handle_form_submission'));
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_script('jquery');
        // Frontend script is embedded in shortcode for now

        wp_localize_script('jquery', 'approval_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('approval_form_nonce')
        ));
    }
    
    /**
     * Approval form shortcode
     */
    public function approval_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Submit Request for Approval', 'approval-plugin'),
            'success_message' => __('Your request has been submitted successfully!', 'approval-plugin'),
            'multistep' => 'no'
        ), $atts);

        $is_multistep = ($atts['multistep'] === 'yes');

        ob_start();
        ?>
        <div class="approval-form-container modern-form">
            <div class="form-header">
                <h2 class="form-title"><?php echo esc_html($atts['title']); ?></h2>
                <p class="form-subtitle"><?php _e('Fill out the form below to submit your approval request', 'approval-plugin'); ?></p>
            </div>

            <?php if ($is_multistep): ?>
            <!-- Multi-step Progress Bar -->
            <div class="form-steps">
                <div class="form-step active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-label"><?php _e('Basic Info', 'approval-plugin'); ?></div>
                </div>
                <div class="form-step" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-label"><?php _e('Details', 'approval-plugin'); ?></div>
                </div>
                <div class="form-step" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-label"><?php _e('Contact', 'approval-plugin'); ?></div>
                </div>
            </div>
            <?php endif; ?>

            <div id="approval-form-messages"></div>

            <form id="approval-form" class="approval-form <?php echo $is_multistep ? 'multistep-form' : ''; ?>">
                <?php if ($is_multistep): ?>
                <!-- Step 1: Basic Information -->
                <div class="form-step-content" data-step-content="1">
                <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="approval-title">
                                <span class="label-icon">📝</span>
                                <?php _e('Request Title', 'approval-plugin'); ?> <span class="required">*</span>
                            </label>
                            <input type="text" id="approval-title" name="title" placeholder="<?php _e('Enter a descriptive title', 'approval-plugin'); ?>" required>
                            <small class="field-hint"><?php _e('Provide a clear and concise title for your request', 'approval-plugin'); ?></small>
                        </div>
                    </div>

                    <div class="form-row two-column">
                        <div class="form-group">
                            <label for="approval-priority">
                                <span class="label-icon">⚡</span>
                                <?php _e('Priority Level', 'approval-plugin'); ?>
                            </label>
                            <select id="approval-priority" name="priority">
                                <option value="low"><?php _e('Low', 'approval-plugin'); ?></option>
                                <option value="medium" selected><?php _e('Medium', 'approval-plugin'); ?></option>
                                <option value="high"><?php _e('High', 'approval-plugin'); ?></option>
                                <option value="urgent"><?php _e('Urgent', 'approval-plugin'); ?></option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="approval-category">
                                <span class="label-icon">📁</span>
                                <?php _e('Category', 'approval-plugin'); ?>
                            </label>
                            <select id="approval-category" name="category">
                                <option value="general"><?php _e('General', 'approval-plugin'); ?></option>
                                <option value="budget"><?php _e('Budget', 'approval-plugin'); ?></option>
                                <option value="purchase"><?php _e('Purchase', 'approval-plugin'); ?></option>
                                <option value="vacation"><?php _e('Vacation', 'approval-plugin'); ?></option>
                                <option value="project"><?php _e('Project', 'approval-plugin'); ?></option>
                                <option value="other"><?php _e('Other', 'approval-plugin'); ?></option>
                            </select>
                        </div>
                    </div>

                <?php if ($is_multistep): ?>
                    <div class="form-navigation">
                        <button type="button" class="btn btn-next" data-next="2"><?php _e('Next', 'approval-plugin'); ?> →</button>
                    </div>
                </div>

                <!-- Step 2: Description -->
                <div class="form-step-content" data-step-content="2" style="display: none;">
                <?php endif; ?>

                    <div class="form-group">
                        <label for="approval-description">
                            <span class="label-icon">📄</span>
                            <?php _e('Description', 'approval-plugin'); ?> <span class="required">*</span>
                        </label>
                        <textarea id="approval-description" name="description" rows="6" placeholder="<?php _e('Provide detailed information about your request...', 'approval-plugin'); ?>" required></textarea>
                        <small class="field-hint"><?php _e('Include all relevant details to help us process your request', 'approval-plugin'); ?></small>
                    </div>

                <?php if ($is_multistep): ?>
                    <div class="form-navigation">
                        <button type="button" class="btn btn-prev" data-prev="1">← <?php _e('Previous', 'approval-plugin'); ?></button>
                        <button type="button" class="btn btn-next" data-next="3"><?php _e('Next', 'approval-plugin'); ?> →</button>
                    </div>
                </div>

                <!-- Step 3: Contact Information -->
                <div class="form-step-content" data-step-content="3" style="display: none;">
                <?php endif; ?>

                    <div class="form-row two-column">
                        <div class="form-group">
                            <label for="approval-name">
                                <span class="label-icon">👤</span>
                                <?php _e('Your Name', 'approval-plugin'); ?> <span class="required">*</span>
                            </label>
                            <input type="text" id="approval-name" name="submitter_name" placeholder="<?php _e('John Doe', 'approval-plugin'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="approval-email">
                                <span class="label-icon">✉️</span>
                                <?php _e('Your Email', 'approval-plugin'); ?> <span class="required">*</span>
                            </label>
                            <input type="email" id="approval-email" name="submitter_email" placeholder="<?php _e('john@example.com', 'approval-plugin'); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <?php if ($is_multistep): ?>
                        <div class="form-navigation">
                            <button type="button" class="btn btn-prev" data-prev="2">← <?php _e('Previous', 'approval-plugin'); ?></button>
                            <button type="submit" class="btn btn-submit"><?php _e('Submit Request', 'approval-plugin'); ?> ✓</button>
                        </div>
                        <?php else: ?>
                        <button type="submit" class="btn btn-submit"><?php _e('Submit Request', 'approval-plugin'); ?></button>
                        <?php endif; ?>
                    </div>

                <?php if ($is_multistep): ?>
                </div>
                <?php endif; ?>
            </form>
        </div>
        
        <style>
        /* Modern Form Container */
        .approval-form-container.modern-form {
            max-width: 700px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 10px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .form-title {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 600;
        }

        .form-subtitle {
            margin: 0;
            opacity: 0.9;
            font-size: 15px;
        }

        /* Multi-step Progress */
        .form-steps {
            display: flex;
            justify-content: space-between;
            padding: 30px;
            background: #f8f9fa;
            position: relative;
        }

        .form-steps::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 15%;
            right: 15%;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }

        .form-step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .form-step.active .step-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: scale(1.1);
        }

        .form-step.completed .step-number {
            background: #10b981;
            color: white;
        }

        .step-label {
            font-size: 13px;
            color: #666;
            font-weight: 500;
        }

        .form-step.active .step-label {
            color: #667eea;
            font-weight: 600;
        }

        /* Form Content */
        .approval-form {
            padding: 30px;
        }

        .form-row {
            margin-bottom: 20px;
        }

        .form-row.two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .label-icon {
            font-size: 18px;
        }

        .required {
            color: #ef4444;
        }

        .field-hint {
            display: block;
            margin-top: 5px;
            color: #6b7280;
            font-size: 12px;
            font-style: italic;
        }

        .approval-form input[type="text"],
        .approval-form input[type="email"],
        .approval-form select,
        .approval-form textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: #ffffff;
        }

        .approval-form input:focus,
        .approval-form select:focus,
        .approval-form textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .approval-form textarea {
            resize: vertical;
            min-height: 120px;
        }

        /* Buttons */
        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            width: 100%;
            padding: 15px;
            font-size: 16px;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .form-navigation {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .btn-next, .btn-prev {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-prev {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-next:hover, .btn-prev:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        /* Messages */
        #approval-form-messages {
            margin: 0 30px 20px;
        }

        .approval-message {
            padding: 15px 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .approval-message.success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 2px solid #10b981;
        }

        .approval-message.error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 2px solid #ef4444;
        }

        .approval-message::before {
            content: '✓';
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #10b981;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .approval-message.error::before {
            content: '✕';
            background: #ef4444;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .approval-form-container.modern-form {
                margin: 15px;
                border-radius: 12px;
            }

            .form-header {
                padding: 25px 20px;
            }

            .form-title {
                font-size: 24px;
            }

            .approval-form {
                padding: 20px;
            }

            .form-row.two-column {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .form-steps {
                padding: 20px;
            }

            .step-label {
                font-size: 11px;
            }

            .form-navigation {
                flex-direction: column;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var currentStep = 1;
            var isMultiStep = $('.multistep-form').length > 0;

            // Multi-step navigation
            $('.btn-next').on('click', function() {
                var nextStep = $(this).data('next');

                // Validate current step
                var currentStepContent = $('[data-step-content="' + currentStep + '"]');
                var isValid = validateStep(currentStepContent);

                if (isValid) {
                    // Hide current step
                    currentStepContent.fadeOut(300, function() {
                        // Show next step
                        $('[data-step-content="' + nextStep + '"]').fadeIn(300);

                        // Update progress
                        updateProgress(nextStep);
                        currentStep = nextStep;

                        // Scroll to top
                        $('html, body').animate({scrollTop: $('.approval-form-container').offset().top - 20}, 400);
                    });
                }
            });

            $('.btn-prev').on('click', function() {
                var prevStep = $(this).data('prev');

                // Hide current step
                $('[data-step-content="' + currentStep + '"]').fadeOut(300, function() {
                    // Show previous step
                    $('[data-step-content="' + prevStep + '"]').fadeIn(300);

                    // Update progress
                    updateProgress(prevStep);
                    currentStep = prevStep;

                    // Scroll to top
                    $('html, body').animate({scrollTop: $('.approval-form-container').offset().top - 20}, 400);
                });
            });

            function updateProgress(step) {
                $('.form-step').removeClass('active completed');

                $('.form-step').each(function() {
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

                requiredFields.each(function() {
                    if (!$(this).val()) {
                        isValid = false;
                        $(this).css('border-color', '#ef4444');

                        $(this).one('input change', function() {
                            $(this).css('border-color', '');
                        });
                    }
                });

                if (!isValid) {
                    showMessage('<?php _e('Please fill in all required fields.', 'approval-plugin'); ?>', 'error');
                }

                return isValid;
            }

            function showMessage(message, type) {
                var $messages = $('#approval-form-messages');
                $messages.html('<div class="approval-message ' + type + '">' + message + '</div>');

                setTimeout(function() {
                    $messages.find('.approval-message').fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 4000);
            }

            // Form submission
            $('#approval-form').on('submit', function(e) {
                e.preventDefault();

                var $form = $(this);
                var $submitBtn = $form.find('.btn-submit');
                var $messages = $('#approval-form-messages');

                // Disable submit button
                var originalText = $submitBtn.html();
                $submitBtn.prop('disabled', true).html('<span class="spinning">⏳</span> <?php _e('Submitting...', 'approval-plugin'); ?>');

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
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.data.message, 'success');
                            $form[0].reset();

                            // Reset to first step if multi-step
                            if (isMultiStep) {
                                setTimeout(function() {
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
                    error: function() {
                        showMessage('<?php _e('An error occurred. Please try again.', 'approval-plugin'); ?>', 'error');
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Add spinning animation
            $('<style>.spinning { display: inline-block; animation: spin 1s linear infinite; } @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>').appendTo('head');

            // Real-time validation
            $('.approval-form input, .approval-form textarea, .approval-form select').on('blur', function() {
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
            }).animate({'opacity': '1'}, {
                duration: 600,
                step: function(now) {
                    $(this).css('transform', 'translateY(' + (30 - (now * 30)) + 'px)');
                }
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Handle form submission via AJAX
     */
    public function handle_form_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'approval_form_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'approval-plugin')));
        }

        // Validate required fields
        $required_fields = array('title', 'description', 'submitter_name', 'submitter_email');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => sprintf(__('The %s field is required.', 'approval-plugin'), $field)));
            }
        }

        // Validate email
        if (!is_email($_POST['submitter_email'])) {
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'approval-plugin')));
        }

        // Check domain whitelist/blacklist
        $email = sanitize_email($_POST['submitter_email']);
        $domain = substr(strrchr($email, "@"), 1);

        // Check whitelist
        if (get_option('approval_plugin_whitelist_enabled', 0)) {
            $whitelist = get_option('approval_plugin_domain_whitelist', '');
            $whitelisted_domains = array_filter(array_map('trim', explode("\n", $whitelist)));

            if (!empty($whitelisted_domains) && !in_array($domain, $whitelisted_domains)) {
                wp_send_json_error(array('message' => __('Sorry, requests from your email domain are not allowed.', 'approval-plugin')));
            }
        }

        // Check blacklist
        if (get_option('approval_plugin_blacklist_enabled', 0)) {
            $blacklist = get_option('approval_plugin_domain_blacklist', '');
            $blacklisted_domains = array_filter(array_map('trim', explode("\n", $blacklist)));

            if (!empty($blacklisted_domains) && in_array($domain, $blacklisted_domains)) {
                wp_send_json_error(array('message' => __('Sorry, requests from your email domain are blocked.', 'approval-plugin')));
            }
        }

        // Prepare data
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'submitter_name' => sanitize_text_field($_POST['submitter_name']),
            'submitter_email' => $email,
            'priority' => isset($_POST['priority']) ? sanitize_text_field($_POST['priority']) : 'medium',
            'category' => isset($_POST['category']) ? sanitize_text_field($_POST['category']) : 'general'
        );

        // Insert into database
        $request_id = ApprovalDatabase::insert_request($data);

        if ($request_id) {
            // Send notification to admin
            $this->send_admin_notification($request_id);

            // Send pending confirmation to user
            $this->send_pending_notification($request_id);

            $pending_message = get_option('approval_plugin_show_pending_message', __('Your request has been submitted successfully! You will receive an email notification when the status changes.', 'approval-plugin'));

            wp_send_json_success(array('message' => $pending_message));
        } else {
            wp_send_json_error(array('message' => __('Failed to submit your request. Please try again.', 'approval-plugin')));
        }
    }
    
    /**
     * Send notification to admin
     */
    private function send_admin_notification($request_id) {
        $request = ApprovalDatabase::get_request($request_id);
        $admin_email = get_option('approval_plugin_admin_email', get_option('admin_email'));

        if ($request && $admin_email) {
            $from_name = get_option('approval_plugin_email_from_name', get_bloginfo('name'));
            $from_email = get_option('approval_plugin_email_from_email', get_option('admin_email'));

            $headers = array(
                'From: ' . $from_name . ' <' . $from_email . '>',
                'Content-Type: text/html; charset=UTF-8'
            );

            $subject = sprintf(__('New approval request: %s', 'approval-plugin'), $request->title);
            $message = sprintf(
                __("<p>A new approval request has been submitted:</p><p><strong>Title:</strong> %s<br><strong>Priority:</strong> %s<br><strong>Category:</strong> %s<br><strong>Submitter:</strong> %s (%s)<br><strong>Description:</strong><br>%s</p><p><a href='%s'>View and manage this request in your WordPress admin panel</a></p>", 'approval-plugin'),
                $request->title,
                ucfirst($request->priority),
                ucfirst($request->category),
                $request->submitter_name,
                $request->submitter_email,
                nl2br(esc_html($request->description)),
                admin_url('admin.php?page=approval-requests')
            );

            wp_mail($admin_email, $subject, $message, $headers);
        }
    }

    /**
     * Send pending notification to user
     */
    private function send_pending_notification($request_id) {
        if (!get_option('approval_plugin_email_notifications', 1)) {
            return;
        }

        $request = ApprovalDatabase::get_request($request_id);

        if ($request) {
            $from_name = get_option('approval_plugin_email_from_name', get_bloginfo('name'));
            $from_email = get_option('approval_plugin_email_from_email', get_option('admin_email'));

            $headers = array(
                'From: ' . $from_name . ' <' . $from_email . '>',
                'Content-Type: text/html; charset=UTF-8'
            );

            $subject = get_option('approval_plugin_email_pending_subject', __('Your request has been received', 'approval-plugin'));
            $body_template = get_option('approval_plugin_email_pending_body', "Hello {user_name},\n\nThank you for submitting your request \"{request_title}\".\n\nYour request is currently pending review. You will receive an email notification once it has been processed.\n\nThank you for your patience,\n{site_name}");

            // Replace placeholders
            $subject = $this->replace_placeholders($subject, $request);
            $body = $this->replace_placeholders($body_template, $request);
            $body = nl2br($body);

            wp_mail($request->submitter_email, $subject, $body, $headers);
        }
    }

    /**
     * Replace email placeholders
     */
    private function replace_placeholders($text, $request) {
        $placeholders = array(
            '{user_name}' => $request->submitter_name,
            '{user_email}' => $request->submitter_email,
            '{request_title}' => $request->title,
            '{request_description}' => $request->description,
            '{admin_notes}' => isset($request->admin_notes) ? $request->admin_notes : '',
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => get_site_url(),
            '{priority}' => isset($request->priority) ? ucfirst($request->priority) : '',
            '{category}' => isset($request->category) ? ucfirst($request->category) : ''
        );

        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }
}