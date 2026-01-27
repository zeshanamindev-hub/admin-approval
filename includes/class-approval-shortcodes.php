<?php
/**
 * Shortcodes for the Approval Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class ApprovalShortcodes
{

    public function __construct()
    {
        add_shortcode('approval_form', array($this, 'approval_form_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_ajax_submit_approval_request', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_submit_approval_request', array($this, 'handle_form_submission'));
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts()
    {
        wp_enqueue_style('approval-public-css', APPROVAL_PLUGIN_URL . 'css/public-style.css', array(), APPROVAL_PLUGIN_VERSION);
        wp_enqueue_script('approval-public-js', APPROVAL_PLUGIN_URL . 'js/public-script.js', array('jquery'), APPROVAL_PLUGIN_VERSION, true);

        wp_localize_script('approval-public-js', 'approval_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('approval_form_nonce'),
            'strings' => array(
                'fill_required' => __('Please fill in all required fields.', 'request-flow-pro'),
                'submitting' => __('Submitting...', 'request-flow-pro'),
                'error' => __('An error occurred. Please try again.', 'request-flow-pro')
            )
        ));
    }

    /**
     * Approval form shortcode
     */
    public function approval_form_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'title' => __('Submit Request for Approval', 'request-flow-pro'),
            'success_message' => __('Your request has been submitted successfully!', 'request-flow-pro'),
            'multistep' => 'no'
        ), $atts);

        $is_multistep = ($atts['multistep'] === 'yes');

        ob_start();
        ?>
        <div class="approval-form-container modern-form">
            <div class="form-header">
                <h2 class="form-title"><?php echo esc_html($atts['title']); ?></h2>
                <p class="form-subtitle">
                    <?php _e('Fill out the form below to submit your approval request', 'request-flow-pro'); ?>
                </p>
            </div>

            <?php if ($is_multistep): ?>
                <!-- Multi-step Progress Bar -->
                <div class="form-steps">
                    <div class="form-step active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-label"><?php _e('Basic Info', 'request-flow-pro'); ?></div>
                    </div>
                    <div class="form-step" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-label"><?php esc_html_e('Details', 'request-flow-pro'); ?></div>
                    </div>
                    <div class="form-step" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-label"><?php esc_html_e('Contact', 'request-flow-pro'); ?></div>
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
                                <?php esc_html_e('Request Title', 'request-flow-pro'); ?> <span class="required">*</span>
                            </label>
                            <input type="text" id="approval-title" name="title"
                                placeholder="<?php esc_attr_e('Enter a descriptive title', 'request-flow-pro'); ?>" required>
                            <small
                                class="field-hint"><?php esc_html_e('Provide a clear and concise title for your request', 'request-flow-pro'); ?></small>
                        </div>
                    </div>

                    <div class="form-row two-column">
                        <div class="form-group">
                            <label for="approval-priority">
                                <span class="label-icon">⚡</span>
                                <?php esc_html_e('Priority Level', 'request-flow-pro'); ?>
                            </label>
                            <select id="approval-priority" name="priority">
                                <option value="low"><?php esc_html_e('Low', 'request-flow-pro'); ?></option>
                                <option value="medium" selected><?php _e('Medium', 'request-flow-pro'); ?></option>
                                <option value="high"><?php _e('High', 'request-flow-pro'); ?></option>
                                <option value="urgent"><?php _e('Urgent', 'request-flow-pro'); ?></option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="approval-category">
                                <span class="label-icon">📁</span>
                                <?php esc_html_e('Category', 'request-flow-pro'); ?>
                            </label>
                            <select id="approval-category" name="category">
                                <option value="general"><?php esc_html_e('General', 'request-flow-pro'); ?></option>
                                <option value="budget"><?php _e('Budget', 'request-flow-pro'); ?></option>
                                <option value="purchase"><?php _e('Purchase', 'request-flow-pro'); ?></option>
                                <option value="vacation"><?php _e('Vacation', 'request-flow-pro'); ?></option>
                                <option value="project"><?php _e('Project', 'request-flow-pro'); ?></option>
                                <option value="other"><?php _e('Other', 'request-flow-pro'); ?></option>
                            </select>
                        </div>
                    </div>

                    <?php if ($is_multistep): ?>
                        <div class="form-navigation">
                            <button type="button" class="btn btn-next"
                                data-next="2"><?php esc_html_e('Next', 'request-flow-pro'); ?> →</button>
                        </div>
                    </div>

                    <!-- Step 2: Description -->
                    <div class="form-step-content" data-step-content="2" style="display: none;">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="approval-description">
                            <span class="label-icon">📄</span>
                            <?php esc_html_e('Description', 'request-flow-pro'); ?> <span class="required">*</span>
                        </label>
                        <textarea id="approval-description" name="description" rows="6"
                            placeholder="<?php esc_attr_e('Provide detailed information about your request...', 'request-flow-pro'); ?>"
                            required></textarea>
                        <small
                            class="field-hint"><?php esc_html_e('Include all relevant details to help us process your request', 'request-flow-pro'); ?></small>
                    </div>

                    <?php if ($is_multistep): ?>
                        <div class="form-navigation">
                            <button type="button" class="btn btn-prev" data-prev="1">←
                                <?php esc_html_e('Previous', 'request-flow-pro'); ?></button>
                            <button type="button" class="btn btn-next"
                                data-next="3"><?php esc_html_e('Next', 'request-flow-pro'); ?> →</button>
                        </div>
                    </div>

                    <!-- Step 3: Contact Information -->
                    <div class="form-step-content" data-step-content="3" style="display: none;">
                    <?php endif; ?>

                    <div class="form-row two-column">
                        <div class="form-group">
                            <label for="approval-name">
                                <span class="label-icon">👤</span>
                                <?php esc_html_e('Your Name', 'request-flow-pro'); ?> <span class="required">*</span>
                            </label>
                            <input type="text" id="approval-name" name="submitter_name"
                                placeholder="<?php esc_attr_e('John Doe', 'request-flow-pro'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="approval-email">
                                <span class="label-icon">✉️</span>
                                <?php esc_html_e('Your Email', 'request-flow-pro'); ?> <span class="required">*</span>
                            </label>
                            <input type="email" id="approval-email" name="submitter_email"
                                placeholder="<?php esc_attr_e('john@example.com', 'request-flow-pro'); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <?php if ($is_multistep): ?>
                            <div class="form-navigation">
                                <button type="button" class="btn btn-prev" data-prev="2">←
                                    <?php esc_html_e('Previous', 'request-flow-pro'); ?></button>
                                <button type="submit"
                                    class="btn btn-submit"><?php esc_html_e('Submit Request', 'request-flow-pro'); ?> ✓</button>
                            </div>
                        <?php else: ?>
                            <button type="submit"
                                class="btn btn-submit"><?php esc_html_e('Submit Request', 'request-flow-pro'); ?></button>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_multistep): ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>


        <?php

        return ob_get_clean();
    }

    /**
     * Handle form submission via AJAX
     */
    public function handle_form_submission()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'approval_form_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'request-flow-pro')));
        }

        // Validate required fields
        $required_fields = array('title', 'description', 'submitter_name', 'submitter_email');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => sprintf(__('The %s field is required.', 'request-flow-pro'), $field)));
            }
        }

        // Validate email
        if (!is_email($_POST['submitter_email'])) {
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'request-flow-pro')));
        }

        // Check domain whitelist/blacklist
        $email = sanitize_email($_POST['submitter_email']);
        $domain = substr(strrchr($email, "@"), 1);

        // Check whitelist
        if (get_option('approval_plugin_whitelist_enabled', 0)) {
            $whitelist = get_option('approval_plugin_domain_whitelist', '');
            $whitelisted_domains = array_filter(array_map('trim', explode("\n", $whitelist)));

            if (!empty($whitelisted_domains) && !in_array($domain, $whitelisted_domains)) {
                wp_send_json_error(array('message' => __('Sorry, requests from your email domain are not allowed.', 'request-flow-pro')));
            }
        }

        // Check blacklist
        if (get_option('approval_plugin_blacklist_enabled', 0)) {
            $blacklist = get_option('approval_plugin_domain_blacklist', '');
            $blacklisted_domains = array_filter(array_map('trim', explode("\n", $blacklist)));

            if (!empty($blacklisted_domains) && in_array($domain, $blacklisted_domains)) {
                wp_send_json_error(array('message' => __('Sorry, requests from your email domain are blocked.', 'request-flow-pro')));
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

            $pending_message = get_option('approval_plugin_show_pending_message', __('Your request has been submitted successfully! You will receive an email notification when the status changes.', 'request-flow-pro'));

            wp_send_json_success(array('message' => $pending_message));
        } else {
            wp_send_json_error(array('message' => __('Failed to submit your request. Please try again.', 'request-flow-pro')));
        }
    }

    /**
     * Send notification to admin
     */
    private function send_admin_notification($request_id)
    {
        $request = ApprovalDatabase::get_request($request_id);
        $admin_email = get_option('approval_plugin_admin_email', get_option('admin_email'));

        if ($request && $admin_email) {
            $from_name = get_option('approval_plugin_email_from_name', get_bloginfo('name'));
            $from_email = get_option('approval_plugin_email_from_email', get_option('admin_email'));

            $headers = array(
                'From: ' . $from_name . ' <' . $from_email . '>',
                'Content-Type: text/html; charset=UTF-8'
            );

            $subject = sprintf(__('New approval request: %s', 'request-flow-pro'), $request->title);
            $message = sprintf(
                __("<p>A new approval request has been submitted:</p><p><strong>Title:</strong> %s<br><strong>Priority:</strong> %s<br><strong>Category:</strong> %s<br><strong>Submitter:</strong> %s (%s)<br><strong>Description:</strong><br>%s</p><p><a href='%s'>View and manage this request in your WordPress admin panel</a></p>", 'request-flow-pro'),
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
    private function send_pending_notification($request_id)
    {
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

            $subject = get_option('approval_plugin_email_pending_subject', __('Your request has been received', 'request-flow-pro'));
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
    private function replace_placeholders($text, $request)
    {
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