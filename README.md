# Wordpress-BMLT-workflow (WBW) v0.3.2

## Background
This plugin was developed for NA Australian Region to support automation of meeting adds/changes/deletes.
The initial version is designed to replace the current complex and heavy manual processing of forms by both the submitter and the form recipients. It still uses email to notify the recipients, but all of the content is now automated and validated based on reference against the BMLT.
For example, meeting change requests are diffed against the content of the BMLT.

For trusted servants, there is now one place that you can see submitted requests and action them, without data reentry into the BMLT admin console.

For admins, modification of email templates, service committees and email contact details can now be done in the same place, without complex logic in a form builder.

This initial release is quite specific to the NA Australian Region use case (particularly the FSO/Starter Kit email) but the code is written such that templates are relatively easy to adjust based on feedback from other regions.
## Features
- Form Sender - Form field population from BMLT
- Form Recipient - Simple to understand changes, including deltas from the current BMLT entry
- Admin - Configurable BMLT settings
- Admin - Email template management for update, close, new, other request, with form fields inserted from the form submission
- Admin - Shortcode configuration of the meeting form, including parameters for searchable service areas
- Admin - Configurable BMLT service areas for use within the workflow submission page, including access control.
## Installation
Standard wordpress plugin installation procedure. Just copy the contents of this repo to your wp-content/plugins folder.

## Usage
Locate **BMLT Workflow -> Configuration** in your Wordpress Admin page. You'll need to put in a valid BMLT server address, username and password and press the Test Server button. If you get a tick, then save settings. Update the 'From Address' to an address that your mailer is permitted to send from

You should now be able to see the **BMLT Workflow->Service Bodies** menu option. Service bodies are retrieved from BMlT, without hierarchy. By default, none of them are available for meeting updates using the system. You can enable them using the checkboxes, then add yourself under 'Wordpress Users with Access' in any/all service areas.

Create a new page, and add the shortcode `[wbw-meeting-update-form]`.  The form will be available, with searches from any areas that you've configured in the service body menu. 

Use the form and submit a meeting change request. Following form submission, you should see the request in the **BMLT Workflow->Workflow Submissions** menu.

Use approve, reject or quickedit to manage the form submission. Once approved, the submission will be committed directly to BMLT.

### User configuration
A role `BMLT Workflow Trusted Servant` is created as part of plugin installation. This role provides no access to wordpress features and acts as a blank placeholder for trusted servants.
Create wordpress users for your trusted servants and assign them this role. Then within the service bodies page assign your wordpress users to the service bodies you would like them to manage.
### Shortcode
Use a shortcode with the form `[wbw-meeting-update-form]` substituting your service areas from BMLT in the parameters

### Email field substitution
You can add fields to the email templates to substitute content from the form submission, or from a BMLT lookup

The following fields are currently supported:

Within email template body or within To/CC address in the service area contact details configuration:
- `{field:email_address}`

Within email template body only:
- `{field:first_name}`
- `{field:last_name}`
- `{field:meeting_name}`
- `{field:start_time}`
- `{field:duration_time}`
- `{field:location_text}`
- `{field:location_street}`
- `{field:location_info}`
- `{field:location_municipality}`
- `{field:location_province}`
- `{field:location_postal_code_1}`
- `{field:virtual_meeting_link}`
- `{field:contact_number_confidential}`
- `{field:formats}`
- `{field:weekday}`
- `{field:additional_info}`
- `{field:starter_kit_postal_address}`

## Testing with phpunit
Requires composer. From the root of the repository:
```composer update```
```./vendor/bin/phpunit```


## 0.3.1 Beta Known Issues
- Email notifications are currently non functional in this beta release
- Support for 'Other' service body (for a user to select when they are unsure) is not implemented
- Quickedit/approve does not update the BMLT email address if the user selects 'set my email to the contact email'
- Not tested on wordpress multisite 

For any other issues you find - please raise an issue here: https://github.com/bmlt-enabled/wordpress-bmlt-workflow/issues and/or ping me on BMLT slack #wordpress-BMLT-workflow

## Changes from 0.31 Release
- Request to add email to meeting contact is now honoured through to backend
- Highlighting of changed fields in user form
- Additional PHPUnit test cases for approval rest interface
- Many cleanups and bugfixes

## Changes from 0.3 Release
- Hosting repo on BMLT-Enabled! https://github.com/bmlt-enabled/wordpress-bmlt-workflow
- Support for 'Other' and 'Close' meeting management, including publish or delete of closed meetings. This includes a new settings option for the admin to choose which default for Trusted servants.
- Additional PHPUnit test cases for approval rest interface

## Changes from 0.2 Release
- Improved front end form layout
- Close meeting will unpublish an existing meeting
- Submissions now contain full information from front end form
- Additional front end fields added (nation and subprovince)
- PHPUnit test cases built
