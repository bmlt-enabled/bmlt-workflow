# BMLT Workflow (BMLTWF)

## Background
This plugin was developed for NA Australian Region to support automation of meeting adds/changes/deletes.
It is designed to reduce the current complex and heavy manual processing of forms by both the form submitter and the service body trusted servants.

For trusted servants, there is now one place that you can see submitted requests and action them, without data reentry into the BMLT admin console.

For admins, modification of email templates, service committees and email contact details can now be done in the same place, without complex logic in a form builder.

This initial release is specific to the NA Australian Region use case (particularly the FSO/Starter Kit email) but the code is written such that templates are relatively easy to adjust based on feedback from other regions.

## Features
- Form Submitters - Minimal typing of details with most content populated from BMLT
- Form Submitters - Notification of approval/rejection of changes, with messages from the trusted servant managing the change.
- Admin - Configurable BMLT settings
- Admin - Email template for mail to the submitter, and for an email to the fso for starter kit requests. Fields can be inserted from the form submission
- Admin - Shortcode configuration of the meeting form
- Admin - Configurable BMLT service areas for use within the workflow submission page, including access control
- Admin - Optional FSO, Nation and SubProvince fields depending on your BMLT usage
- Trusted Servants - Full featured approve/reject workflow and automatic insertion of changes into BMLT
- Trusted Servants - Notification will be sent to the wordpress email of trusted servants for any new submissions they are permitted to manage
## Installation
Via wordpress plugin download. Can be installed either as a multisite network activation, multisite single site activation or on a single site.

## Contact/Issues
For any other issues you find - please raise an issue here: https://github.com/bmlt-enabled/bmlt-workflow/issues and/or reach out on BMLT slack #wordpress-BMLT-workflow
You can always find the latest version of this code at: https://github.com/bmlt-enabled/bmlt-workflow/

## Usage
Locate **BMLT Workflow -> Configuration** in your Wordpress Admin page. You'll need to put in a valid BMLT Root Server address, username and password and press the Test Server button.
The username and password is a BMLT user who is configured as a **BMLT Service Body Administrator**. This user will be used to make changes to the service bodies they are configured as a **Full meeting list editor**. 
If you get a tick, then save settings. Update the 'From Address' to an address that your mailer is permitted to send from.

You should now be able to see the **BMLT Workflow->Service Bodies** menu option. Service bodies are retrieved from BMlT, without hierarchy. By default, none of them are available for meeting updates using the system. You can enable them using the checkboxes, then add yourself under 'Wordpress Users with Access' in any/all service bodies.

Create a new page, and add the shortcode `[bmltwf-meeting-update-form]`.  The form will be available, with searches populated from any service bodies that you've configured in the service body menu. 

Use the form and submit a meeting change request. Following form submission, you should see the request in the **BMLT Workflow->Workflow Submissions** menu.

Use approve, reject or quickedit to manage the form submission. Once approved, the submission will be committed directly to BMLT. The approve/reject message will be sent to the email address registered by the original form submitter.

### User configuration
A role `BMLT Workflow Trusted Servant` is created as part of plugin installation. This role provides no access to wordpress features and acts as a blank placeholder for trusted servants.
Create wordpress users for your trusted servants and assign them this role. Then within the service bodies page assign your wordpress users to the service bodies you would like them to manage.
The email address of these wordpress users will be used to send notifications when new submissions are received to their service body.
### Shortcode
Use a shortcode with the form `[bmltwf-meeting-update-form]`

### Email template field substitution
You can add fields to the email templates to substitute content from the form submission

The following fields are currently supported:

Within fso template body only:
- `{field:submitter_name}`
- `{field:meeting_name}`
- `{field:starter_kit_postal_address}`

Within submitter template body only:
- `{field:submission}`
## Testing with phpunit
Requires composer. From the root of the repository:
```composer update```
```./vendor/bin/phpunit```

