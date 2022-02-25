# BMLT-meeting-admin-workflow (BMAW)

## Background
This plugin was developed for NA Australian Region to support automation of meeting adds/changes/deletes.
The initial version is designed to replace the current complex and heavy manual processing of forms by both the submitter and the form recipients. It still uses email to notify the recipients, but all of the content is now automated and validated based on reference against the BMLT.
For example, meeting change requests are diffed against the content of the BMLT.

For admins, modification of email templates, service committees and email contact details can now be done in the same place, without complex logic in a form builder.

This initial release is quite specific to the NA Australian Region use case (particularly the FSO/Starter Kit email) but the code is written such that templates are relatively easy to adjust based on feedback from other regions.
## Features
- Form Sender - Form field population from BMLT
- Form Recipient - Simple to understand changes, including deltas from the current BMLT entry
- Admin - Configurable BMLT settings
- Admin - Service Area email address management
- Admin - Email template management for update, close, new, other request, with form fields inserted from the form submission
- Admin - Configurable template and email for notifying the FSO of starter kit requests.
- Admin - Shortcode configuration of the meeting form, including parameters for searchable service areas

## Installation
Standard wordpress plugin installation procedure. Just copy the contents of this repo to your wp-content/plugins folder.

## Usage
See the BMAW Settings in your Wordpress Admin page. You'll need to put in a valid BMLT server address and press the Test Server button. If you get a tick, then save settings.

Update the 'From Address' to an address that your mailer is permitted to send from

