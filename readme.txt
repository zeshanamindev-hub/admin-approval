=== Request Flow Pro ===
Contributors: zeshanamin
Tags: approval, workflow, requests, admin, management, forms
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A powerful WordPress solution for managing approval workflows with modern UI, email notifications, and comprehensive request tracking.

== Description ==

**Request Flow Pro** transforms how you manage requests in WordPress. Whether you're handling vacation requests, budget approvals, purchase orders, or any custom workflow - this solution provides a professional, modern approach.

= 🎯 Key Features =

* **Modern Dashboard** - Beautiful statistics cards with real-time metrics
* **Priority Levels** - Urgent, High, Medium, Low
* **Category Management** - Organize requests by type (Budget, Vacation, Purchase, etc.)
* **Bulk Actions** - Approve or reject multiple requests at once
* **Email Notifications** - Customizable templates for approved, rejected, and pending statuses
* **Domain Management** - Whitelist/blacklist email domains to prevent spam
* **AJAX Interface** - Fast, smooth interactions without page reloads
* **Search & Filter** - Real-time search across all request data
* **Export to CSV** - Download all requests for reporting
* **Responsive Design** - Works perfectly on mobile, tablet, and desktop
* **Multi-Step Forms** - Optional wizard-style frontend forms
* **Custom Email Templates** - Full control over notification emails with placeholders

= 📊 Statistics Dashboard =

Track key metrics at a glance:
* Total requests
* Pending approvals
* Approval rate percentage
* Average response time
* Status breakdown

= 🎨 Modern User Interface =

* Gradient color schemes
* Smooth animations
* Hover effects
* Professional typography
* Clean, intuitive layout

= 🔔 Email System =

* Automatic notifications on status changes
* Customizable email templates
* Support for HTML emails
* Template placeholders: {user_name}, {request_title}, {admin_notes}, etc.
* Custom "From" name and email
* Pending confirmation emails

= 🛡️ Security Features =

* Nonce verification on all forms
* Input sanitization
* Output escaping
* SQL injection protection with prepared statements
* Domain whitelist/blacklist

= 📝 Frontend Forms =

Two form styles available:
* **Standard Form** - Single page submission
* **Multi-Step Form** - Wizard with progress indicator

Use shortcodes:
* `[approval_form]` - Standard form
* `[approval_form multistep="yes"]` - Multi-step form
* `[approval_form title="Custom Title"]` - Custom title

= 🔧 Advanced Features =

* Auto-delete rejected requests after X days
* Bulk approval/rejection
* Request history tracking
* Admin notes on each request
* Category-based filtering
* Priority-based sorting
* CSV export for reporting

= 👥 Perfect For =

* HR departments (vacation, leave requests)
* Finance teams (budget approvals)
* Procurement (purchase orders)
* Project management (resource requests)
* Schools (permission slips, field trips)
* Membership sites (access requests)
* Any approval workflow

== Installation ==

= Automatic Installation =

1. Log into your WordPress admin
2. Go to Plugins → Add New
3. Search for "Request Flow Pro"
4. Click "Install Now"
5. Activate the plugin

= Manual Installation =

1. Download the plugin zip file
2. Go to Plugins → Add New → Upload Plugin
3. Choose the zip file and click "Install Now"
4. Activate the plugin
5. Go to Approvals → Settings to configure

= After Activation =

1. Visit **Approvals → Settings** to configure:
   - Email notifications
   - Email templates
   - Domain whitelist/blacklist
   - General settings

2. Add the form to any page using shortcode:
   `[approval_form]`

3. Start managing requests from **Approvals → All Requests**

== Frequently Asked Questions ==

= How do I add the submission form to my site? =

Add the shortcode `[approval_form]` to any page, post, or widget. For a multi-step form, use `[approval_form multistep="yes"]`.

= Can I customize the email notifications? =

Yes! Go to **Approvals → Settings → Email Templates**. You can customize subject and body for approved, rejected, and pending emails. Use placeholders like {user_name}, {request_title}, etc.

= How do I prevent spam submissions? =

Use the domain blacklist feature in **Settings → Domain Management** to block known spam domains. You can also enable whitelisting to only accept specific domains.

= Can I export the requests? =

Yes! Click the "Export CSV" button on the All Requests page or go to **Settings → Advanced → Export to CSV**.

= Does it support bulk actions? =

Yes! Select multiple requests using checkboxes and use the "Bulk Actions" dropdown to approve or reject multiple requests at once.

= Is it mobile-friendly? =

Absolutely! The plugin is fully responsive and works beautifully on all devices.

= Can I track statistics? =

Yes! The dashboard shows total requests, pending count, approval rate, average response time, and status breakdowns.

= How do I set priority levels? =

When submitting a request, users can select from Urgent, High, Medium, or Low priority. Admins see color-coded priority badges.

= Can I organize requests by category? =

Yes! Requests can be categorized as General, Budget, Purchase, Vacation, Project, or Other.

= Is it translation ready? =

Yes! The plugin uses WordPress translation standards and includes the text domain 'request-flow-pro'.

= What happens when I uninstall? =

The plugin includes an uninstall script that removes all database tables and options. Your WordPress site will be left clean.

= Can I customize the form fields? =

Currently, the plugin includes standard fields (Title, Description, Name, Email, Priority, Category). Custom fields feature is planned for future releases.

== Screenshots ==

1. Modern dashboard with statistics cards and request overview
2. Request details modal with approve/reject options
3. Comprehensive settings page with multiple tabs
4. Beautiful frontend submission form (standard)
5. Multi-step form with progress indicator
6. Email template customization interface
7. Domain whitelist/blacklist management
8. Mobile responsive design

== Changelog ==

= 1.0.0 - 2025-01-05 =
* Initial release
* Modern dashboard with statistics
* Priority levels (Urgent, High, Medium, Low)
* Category management
* Email notification system with custom templates
* Domain whitelist/blacklist
* Bulk actions
* AJAX-powered interface
* Search and filter functionality
* CSV export
* Multi-step forms
* Responsive design
* Complete settings panel

== Upgrade Notice ==

= 1.0.0 =
Initial release of Approval Plugin with modern UI and comprehensive features.

== Additional Information ==

= Support =

For support, feature requests, or bug reports, please visit:
* Plugin support forum
* GitHub repository (if applicable)
* Your support email

= Privacy Policy =

This plugin does not collect or transmit any user data to external servers. All data is stored in your WordPress database. Email notifications are sent using your WordPress installation's wp_mail() function.

= Credits =

Developed with ❤️ for the WordPress community.

== Development ==

= Hooks & Filters =

The plugin provides several hooks for developers:

**Actions:**
* `approval_request_submitted` - Fires when a request is submitted
* `approval_request_approved` - Fires when a request is approved
* `approval_request_rejected` - Fires when a request is rejected

**Filters:**
* `approval_email_template` - Filter email templates
* `approval_form_fields` - Filter form fields
* `approval_allowed_categories` - Filter available categories

= Database =

The plugin creates one table: `wp_approval_requests`

Fields include: id, title, description, priority, category, submitter_name, submitter_email, status, admin_notes, created_at, updated_at
