# Wordpress-BMLT-workflow (WBW) v0.3.4

## Background
This plugin was developed for NA Australian Region to support automation of meeting adds/changes/deletes.
It is designed to reduce the current complex and heavy manual processing of forms by both the form submitter and the service body trusted servants.

For trusted servants, there is now one place that you can see submitted requests and action them, without data reentry into the BMLT admin console.

For admins, modification of email templates, service committees and email contact details can now be done in the same place, without complex logic in a form builder.

This initial release is quite specific to the NA Australian Region use case (particularly the FSO/Starter Kit email) but the code is written such that templates are relatively easy to adjust based on feedback from other regions.

## Features
- Form Submitters - Minimal typing of details with most content populated from BMLT.
- Form Submitters - Notification of approval/rejection of changes, with messages from the trusted servant managing the change.
- Admin - Configurable BMLT settings
- Admin - Email template for mail to the submitter, and for an email to the fso for starter kit requests. Fields can be inserted from the form submission
- Admin - Shortcode configuration of the meeting form
- Admin - Configurable BMLT service areas for use within the workflow submission page, including access control
- Trusted Servants - Full featured approve/reject workflow and automatic insertion of changes into BMLT.
- Trusted Servants - Notification will be sent to the wordpress email of trusted servants for any new submissions they are permitted to manage. 
## Installation
Standard wordpress plugin installation procedure. Just copy the contents of this repo to your wp-content/plugins folder.

## Usage
Locate **BMLT Workflow -> Configuration** in your Wordpress Admin page. You'll need to put in a valid BMLT server address, username and password and press the Test Server button.
The username and password is a BMLT user who is configured as a **BMLT Service Body Administrator**. This user will be used to make changes to the service bodies they are configured as a **Full meeting list editor**. 
If you get a tick, then save settings. Update the 'From Address' to an address that your mailer is permitted to send from.

You should now be able to see the **BMLT Workflow->Service Bodies** menu option. Service bodies are retrieved from BMlT, without hierarchy. By default, none of them are available for meeting updates using the system. You can enable them using the checkboxes, then add yourself under 'Wordpress Users with Access' in any/all service bodies.

Create a new page, and add the shortcode `[wbw-meeting-update-form]`.  The form will be available, with searches populated from any service bodies that you've configured in the service body menu. 

Use the form and submit a meeting change request. Following form submission, you should see the request in the **BMLT Workflow->Workflow Submissions** menu.

Use approve, reject or quickedit to manage the form submission. Once approved, the submission will be committed directly to BMLT. The approve/reject message will be sent to the email address registered by the original form submitter.

### User configuration
A role `BMLT Workflow Trusted Servant` is created as part of plugin installation. This role provides no access to wordpress features and acts as a blank placeholder for trusted servants.
Create wordpress users for your trusted servants and assign them this role. Then within the service bodies page assign your wordpress users to the service bodies you would like them to manage.
The email address of these wordpress users will be used to send notifications when new submissions are received to their service body.
### Shortcode
Use a shortcode with the form `[wbw-meeting-update-form]` substituting your service areas from BMLT in the parameters

### Email template field substitution
You can add fields to the email templates to substitute content from the form submission

The following fields are currently supported:

Within fso template body only:
- `{field:first_name}`
- `{field:last_name}`
- `{field:meeting_name}`
- `{field:starter_kit_postal_address}`

Within submitter template body only:
- `{field:submission}`
## Testing with phpunit
Requires composer. From the root of the repository:
```composer update```
```./vendor/bin/phpunit```

For any other issues you find - please raise an issue here: https://github.com/bmlt-enabled/wordpress-bmlt-workflow/issues and/or ping me on BMLT slack #wordpress-BMLT-workflow

## Changes from 0.32 Release
- Email notification (submitter and trusted servant) and notification to submitter on reject/approval support added. (#10)
- Quickedit now shows the additional info field (thanks @tempsaint!) (#13)
- Support for populating State and Subprovince from BMLT (thanks @pjaudiomv) (#1)
- Many cleanups, bugfixes and extra PHPUnit test cases
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
