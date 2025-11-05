Installation Guide - WordPress Approval Plugin
==============================================

Quick Start
-----------

1.  **Upload Plugin Files**
    
    *   Download/copy all plugin files to your WordPress installation
        
    *   Place the entire approval-plugin folder in /wp-content/plugins/
        
2.  **Activate Plugin**
    
    *   Go to WordPress Admin → Plugins
        
    *   Find “Approval Plugin” in the list
        
    *   Click “Activate”
        
3.  **Verify Installation**
    
    *   Check that “Approvals” appears in your admin menu
        
    *   The plugin will automatically create the database table
        

Adding the Form to Your Site
----------------------------

1.  **Create/Edit a Page or Post**
    
    *   Go to Pages → Add New (or edit existing)
        
    *   Add the shortcode: \[approval\_form\]
        
    *   Publish/Update the page
        
2.  **Test the Form**
    
    *   Visit the page on your frontend
        
    *   Fill out and submit a test request
        
    *   Check the admin panel to see the submission
        

Admin Usage
-----------

1.  **View Requests**
    
    *   Go to Approvals → All Requests
        
    *   See all submitted requests with their status
        
2.  **Manage Requests**
    
    *   Click “Approve” or “Reject” for pending requests
        
    *   Add admin notes when changing status
        
3.  **Configure Settings**
    
    *   Go to Approvals → Settings
        
    *   Enable/disable email notifications
        

Customization Options
---------------------

### Form Customization

Plain textANTLR4BashCC#CSSCoffeeScriptCMakeDartDjangoDockerEJSErlangGitGoGraphQLGroovyHTMLJavaJavaScriptJSONJSXKotlinLaTeXLessLuaMakefileMarkdownMATLABMarkupObjective-CPerlPHPPowerShell.propertiesProtocol BuffersPythonRRubySass (Sass)Sass (Scss)SchemeSQLShellSwiftSVGTSXTypeScriptWebAssemblyYAMLXML`   [approval_form title="Custom Title" success_message="Custom success message"]   `

### CSS Customization

Add custom CSS to your theme to style the form:

Plain textANTLR4BashCC#CSSCoffeeScriptCMakeDartDjangoDockerEJSErlangGitGoGraphQLGroovyHTMLJavaJavaScriptJSONJSXKotlinLaTeXLessLuaMakefileMarkdownMATLABMarkupObjective-CPerlPHPPowerShell.propertiesProtocol BuffersPythonRRubySass (Sass)Sass (Scss)SchemeSQLShellSwiftSVGTSXTypeScriptWebAssemblyYAMLXML`   .approval-form-container {      /* Your custom styles */  }   `

Troubleshooting
---------------

### Common Issues

1.  **Form not displaying**
    
    *   Check that the shortcode is spelled correctly: \[approval\_form\]
        
    *   Ensure the plugin is activated
        
2.  **Submissions not appearing**
    
    *   Check if JavaScript is enabled in the browser
        
    *   Verify database table was created (wp\_approval\_requests)
        
3.  **Email notifications not working**
    
    *   Check WordPress email configuration
        
    *   Verify settings in Approvals → Settings
        

### Database Issues

If the database table wasn’t created automatically:

1.  Deactivate the plugin
    
2.  Reactivate the plugin
    
3.  Check if the table wp\_approval\_requests exists in your database
    

### Permission Issues

Ensure your WordPress user has the manage\_options capability to access the admin interface.

File Permissions
----------------

Ensure proper file permissions:

*   Folders: 755
    
*   Files: 644
    

Support
-------

If you encounter issues:

1.  Check the WordPress debug log
    
2.  Verify all plugin files are uploaded correctly
    
3.  Test with a default WordPress theme
    
4.  Disable other plugins to check for conflicts
    

Next Steps
----------

After installation:

1.  Test the complete workflow (submit → approve/reject)
    
2.  Customize the form styling to match your theme
    
3.  Configure email notifications
    
4.  Train your team on using the admin interface
    

The plugin is now ready to handle approval requests on your WordPress site!