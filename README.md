WordPress Approval Plugin
=========================

A comprehensive WordPress plugin for managing approval workflows and requests. This plugin allows users to submit requests through a frontend form and provides administrators with a complete backend interface to review, approve, or reject submissions.

Features
--------

*   **Frontend Submission Form**: Users can submit approval requests using the \[approval\_form\] shortcode
    
*   **Admin Dashboard**: Complete admin interface to manage all approval requests
    
*   **Status Management**: Approve, reject, or keep requests pending
    
*   **Email Notifications**: Automatic email notifications for status changes
    
*   **Filtering & Search**: Filter requests by status and search functionality
    
*   **Responsive Design**: Works on desktop and mobile devices
    
*   **Security**: Built with WordPress security best practices
    

Installation
------------

1.  Upload the approval-plugin folder to your /wp-content/plugins/ directory
    
2.  Activate the plugin through the ‘Plugins’ menu in WordPress
    
3.  The plugin will automatically create the necessary database table upon activation
    

Usage
-----

### For Site Administrators

1.  **Access Admin Panel**: Go to Approvals in your WordPress admin menu
    
2.  **View Requests**: See all submitted requests with filtering options (All, Pending, Approved, Rejected)
    
3.  **Manage Requests**: Approve or reject pending requests directly from the list
    
4.  **Configure Settings**: Access settings to enable/disable email notifications
    

### For Frontend Users

1.  **Add Form to Page/Post**: Use the shortcode \[approval\_form\] to display the submission form
    
2.  **Submit Request**: Fill out the form with title, description, name, and email
    
3.  **Receive Notifications**: Get email updates when your request status changes
    

Shortcodes
----------

### \[approval\_form\]

Displays the approval request submission form.

**Parameters:**

*   title - Custom form title (default: “Submit Request for Approval”)
    
*   success\_message - Custom success message (default: “Your request has been submitted successfully!”)
    

**Example:**

Plain textANTLR4BashCC#CSSCoffeeScriptCMakeDartDjangoDockerEJSErlangGitGoGraphQLGroovyHTMLJavaJavaScriptJSONJSXKotlinLaTeXLessLuaMakefileMarkdownMATLABMarkupObjective-CPerlPHPPowerShell.propertiesProtocol BuffersPythonRRubySass (Sass)Sass (Scss)SchemeSQLShellSwiftSVGTSXTypeScriptWebAssemblyYAMLXML`   [requflpr_form title="Submit Your Proposal" success_message="Thank you! We'll review your proposal soon."]   `

Database Schema
---------------

The plugin creates a table wp\_approval\_requests with the following structure:

*   id - Unique request identifier
    
*   title - Request title
    
*   description - Request description
    
*   submitter\_name - Name of the person submitting
    
*   submitter\_email - Email of the submitter
    
*   status - Current status (pending, approved, rejected)
    
*   admin\_notes - Notes from administrators
    
*   created\_at - Submission timestamp
    
*   updated\_at - Last update timestamp
    

File Structure
--------------

Plain textANTLR4BashCC#CSSCoffeeScriptCMakeDartDjangoDockerEJSErlangGitGoGraphQLGroovyHTMLJavaJavaScriptJSONJSXKotlinLaTeXLessLuaMakefileMarkdownMATLABMarkupObjective-CPerlPHPPowerShell.propertiesProtocol BuffersPythonRRubySass (Sass)Sass (Scss)SchemeSQLShellSwiftSVGTSXTypeScriptWebAssemblyYAMLXML`   approval-plugin/  ├── approval-plugin.php              # Main plugin file  ├── includes/  │   ├── class-approval-admin.php     # Admin interface  │   ├── class-approval-database.php  # Database operations  │   └── class-approval-shortcodes.php # Frontend shortcodes  ├── assets/  │   ├── css/  │   │   └── admin-style.css          # Admin styling  │   └── js/  │       └── admin-script.js          # Admin JavaScript  └── README.md                        # This file   `

Customization
-------------

### Styling the Frontend Form

The frontend form includes basic CSS that can be overridden in your theme. Key CSS classes:

*   .approval-form-container - Main form container
    
*   .approval-form - Form element
    
*   .form-group - Individual form fields
    
*   .approval-submit-btn - Submit button
    

### Hooks and Filters

The plugin is built with extensibility in mind. You can add custom functionality using WordPress hooks:

Plain textANTLR4BashCC#CSSCoffeeScriptCMakeDartDjangoDockerEJSErlangGitGoGraphQLGroovyHTMLJavaJavaScriptJSONJSXKotlinLaTeXLessLuaMakefileMarkdownMATLABMarkupObjective-CPerlPHPPowerShell.propertiesProtocol BuffersPythonRRubySass (Sass)Sass (Scss)SchemeSQLShellSwiftSVGTSXTypeScriptWebAssemblyYAMLXML`   // Example: Add custom validation  add_action('approval_before_insert_request', 'my_custom_validation');  // Example: Modify email content  add_filter('approval_notification_message', 'my_custom_email_content');   `

Security Features
-----------------

*   CSRF protection with WordPress nonces
    
*   Input sanitization and validation
    
*   Capability checks for admin functions
    
*   SQL injection prevention with prepared statements
    

Requirements
------------

*   WordPress 5.0 or higher
    
*   PHP 7.4 or higher
    
*   MySQL 5.6 or higher
    

Support
-------

For support and feature requests, please contact the plugin developer or submit an issue through the appropriate channels.

License
-------

This plugin is licensed under the GPL v2 or later.

Changelog
---------

### Version 1.0.0

*   Initial release
    
*   Basic approval workflow functionality
    
*   Frontend submission form
    
*   Admin management interface
    
*   Email notifications
    
*   Status filtering and search