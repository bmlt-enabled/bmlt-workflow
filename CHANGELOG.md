## 1.1.17 (Oct 14, 2024)
- Fixes contact phone number format limitation on end user form (thanks @adamH !)

## 1.1.16 (Sep 12, 2024)
- Fixes reported bug #188 for virtual meeting publish displaying in quickedit (thanks @tempsaint !)
- Add querystring parameter support for deep linking from crouton (thanks @dg !)

## 1.1.13 (Aug 13,2024)
- Feature: Map marker in quickedit is now movable, mimicking the behaviour of BMLT UI
- Support for the BMLT zip and county auto geocoding setting, which requires BMLT 3.1.1 or above (thanks @AdamH !)
- #184 - Feature: Support for publish/unpublish meetings on the virtual.na.org site, by adjusting the worldid from U to G (thanks @brustar64 !)
- Added deprecation of BMLT 2.x note to submissions page

## 1.1.12 (Jul 28, 2024)
- Haven't done this release thing in a while...

## 1.1.11 (Jul 28, 2024)
- Return error message when workflow account does not have access to any service bodies
- Bugfix for location handling for LOCATION tagged formats (thanks @brustar64 !)
- Clean up some google maps javascript errors

## 1.1.10 (Oct 28, 2023)
- #182 Bugfix for missing options if plugin initialise was not called - this may cause some very strange errors on required fields (thanks NA admin team in Australia !)
- #181 Bugfix for invalid handling of deleted fields in a meeting submission (thanks @brustar64 !)

## 1.1.9 (Sep 26, 2023)
- Bugfix for javascript naming collision (thanks @Klodd64 !)

## 1.1.8 (Apr 19, 2023)
- Update for italian translation (thanks @CaliforniaSteve !)
- Tested against Wordpress 6.3

## 1.1.7 (Apr 19, 2023)
- Release for broken language files

## 1.1.6 (Apr 19, 2023)
- #155 Updated French Translation (thanks @Klodd65 !)
- Added contact_number to FSO fields
- #169 Bug: Clipboard not working in config page
- Tested on WordPress 6.2

## 1.1.5 (Mar 26, 2023)
- #173 Feature: Support for publish/unpublish meetings - we now support user self publish/unpublish meetings using the change meeting option

## 1.1.4 (Mar 24, 2023)
- Fix a couple of translation bugs
- Actually fix #161 correctly :(

## 1.1.3 (Mar 24, 2023)
- #167 Bug: FSO starter kit requests not being sent (thanks SinclairP !)
- #170 Bug: Auto resize not working on quick view for some fields (thanks AnitaK !)
- #171 Bug: County Field isn't work with Quick Edit Mode (thanks @Klodd65 !)
- #161 Bug: Temporary Closures against BMLT3x failing to approve due to invalid venueType (thanks markd !)
- Italian translation included (thanks @californiasteve !)
## 1.1.2 (Mar 10, 2023)
- #160 Getting an error in the logs - Undefined array key...Integration.php on line 630 #160 (thanks @brustar64 !)

## 1.1.1 (Mar 9, 2023)
- Fixing broken phpunit test cases

## 1.1.0 (Mar 9, 2023)
- Feature: Multi language support in both backend and frontend (thanks @klodd65 !)
- French translation included
- #145 Bug: geolocate fails on good address #14 (thanks @cdiddy1979 !)

## 1.0.29 (Mar 5, 2023)
- Feature: Admins and superadmins (with manage_options capability) have full visibility of the workflow submissions pane
- Feature (disabled for now): First cut of multi language support
- #158 New meeting added an additional format (thanks @TheRichWoods !)

## 1.0.28 (Feb 24, 2023)
- Feature: Support using our own dedicated google maps API key (thanks Colin P)
- Feature: Better google maps error responses
- Feature: Cache google maps key from BMLT
- Improve support for detecting a live BMLT root server upgrade, without constantly hitting serverinfo
- Move some classes to traits to clean up code and test cases
- #152 Bug: virtual meeting link not saving in quickedit
- #151 Bug: Clicking the More Info Button also Selects/Deselects Submissions (thanks @tempsaint !)

## 1.0.25 (Feb 19, 2023)
- Javascript fix for google maps
- #150 Service Body Dropdown Confusion (thanks @klgrimley !)

## 1.0.24 (Feb 19, 2023)
- Map view in Quickedit is back, in pog form

## 1.0.23 (Feb 18, 2023)
- #141 Bug: turning off a service body from form display makes submission page hang
- Roll back Map view on Quickedit page - needs to change maps API from embed to javascript :( 
- Fix for erroring CI/CD test cases

## 1.0.22 (Feb 18, 2023)
- #139 Bug: Quickedit not clearing correctly between multiple edits (thanks markd !)
- Major test case rewrite to use Mockoon for BMLT backend
- #144 BMLT Root Server Configuration Change Warning (thanks @klgrimley !)
- BMLT Root Server configuration page UI updates and test cases
- Better feedback on Geolocation errors in quicksearch
- Map view on Quickedit page
- Disable debug in config file (doh) (thanks dennis.m )

## 1.0.21 (Feb 11, 2023)
- #138 Users dropdown on Service Bodies config page not showing all users (thanks @pjaudiomv !)
- CSS fix for body margin (thanks KevinG !)

## 1.0.20 (Feb 10, 2023)
- Actually bump the version number properly
- #134 Bug: duration time set to 0h0m after quickedit
- #132 Feature: Update all contact details, not just email
- #97 Feature: Filter Submissions View (thanks @tempsaint !) - We now have a search bar and a filter by dropdown on the submissions viewer
- #137 Change Last Name field label to Last Initial (thanks @tempsaint !)

## 1.0.19 (Jan 30, 2023)
- First version in live use in Australia region, and lots of bugs/features to fix!
- #126 Feature: Add service body to meeting submission notification subject line
- #125 Feature: When user submits change of venue from virtual to face-to-face, allow removal of the virtual details from BMLT
- #124 Feature: Modify meeting needs to more clearly show what actually changed in the submission management and quickedit pages
- #123 Bug: Quickedit does not show venue type
- #131 Bug: 'undefined' showing in format list on bmlt3x #131
- #129 Bug: new meeting should not show previous venue type in submission page
- #128 Bug: submission display doesn't honour the adjusted naming for subprovince etc
- #127 bug: New meeting submitted through bmlt-workflow gets added to the wrong day (thanks @paulnagle !)

## 1.0.18 (Jan 23, 2023)
- #120 Server version upgrades handled incorrectly
- #121 Feature: Make it clear that 'close meeting' is for permanent closures

## 1.0.17 (Jan 18, 2023)
- #98 Either add Venue Type up top or move the Virtual Meeting Options panel - virtual meeting options are now displayed seperately in the form from the venue type, and venue type appears in the main form content above the location entry (thanks @tempsaint !)

## 1.0.16 (Jan 6, 2023)
- #116 Bug: Map location being updated to default, even though geolocation is disabled (thanks @paulnagle !)

## 1.0.15 (Jan 5, 2023)
- #115 Bug: Location Info changes not being submitted (thanks @paulnagle !)
- #114 Feature Request: Submit button feedback (thanks @paulnagle !)

## 1.0.14 (Jan 3, 2023)
- Bugfix for handling deleted service bodies correctly

## 1.0.13 (Dec 30, 2022)
- Fix version check for release candidate BMLT versions (thanks @californiasteve !)

## 1.0.12 (Dec 28, 2022)
- Fix verbose phpunit test

## 1.0.11 (Dec 28, 2022)
- BMLT3.x api support!! (thanks bmlt-enabled team!)
- Major test suite rewrite for docker testing
- Multiple bugfixes for restore function
- Bugfix for issue where only a single service body is viewable (thanks KevinC !)

## 1.0.9 (Oct 13, 2022)
- #103 Feature Request: Allow geocode disabling (thanks @paulnagle !)
- Lots of refactoring test cases to support bmlt changes

## 1.0.8 (Oct 5, 2022)
- #101 QuickEdit page errors (thanks @paulnagle !)
- #100 more test cases required for virtual meetings
- #96 geocode success/failed notification from quickedit goes to wrong pane

## 1.0.7 (Oct 3, 2022)
- #95 css highlighting on form change broken in 1.0.6
- #94 'delete' button in submission form not working correctly for non wordpress admin - Now implemented as an option
- Revamped the admin options page to add support for more optional fields
- #93 Feature Request: Allow Meeting Formats to be left blank (thanks @paulnagle !)
- #92 Feature Request: Allow "State" to be an optional form field (thanks @paulnagle !)
- #91 Feature Request: Allow the location fields to be renamed (thanks @paulnagle !)

## 1.0.6 (Sep 26, 2022)
- Fixed menu bug for unprivileged users and updated test cases to support

## 1.0.5 (Sep 20, 2022)
- #84 CSS fixes for Enfold theme
- #88 CSS fixes for single column mobile display
- #87 shortcode descriptive text is not clear ( thanks adamh !)
- #86 backup and restore has no descriptive text ( thanks adamh !)
- #83 don't mention starter kit in subtext when fso disabled ( thanks @pjaudiomv !)

## 1.0.4 (Sep 12, 2022)
- #81 Change language in settings from BMLT to BMLT Root Server (thanks @tempsaint !)
- #59 filter visible service bodies based on the bot user permissions, rather than all service bodies (thanks @pjaudiomv !)
- Updated test cases and new wordpress deployer (thanks @pjaudiomv !)

## 1.0.3 (Sep 10, 2022)
- Updated with multisite support and test cases
- Updated icons for wordpress publish(thanks KevinC !)

## 1.0.2 (Aug 19, 2022)
- First release pushed to wordpress.org!

## 1.0.1 (Aug 16, 2022)
- Lots of code changes to support wordpress publish guidelines

## 1.0.0 (Aug 12, 2022)
- Version number bump and rename of tables and options for wordpess publish

## 0.4.5 (Jul 24, 2022)
- #79 Postcode field not a required field, ability to turn off (thanks @rogersearle !) - option for hiding postcode is now provided in the configuration page
- #78 Postcode/zipcode format is too restrictive (thanks @paulnagle !) - now takes a free text field
- #77 Starter Kit / FSO issues (thanks @tempsaint !) - option for hiding fso entirely is now provided in the configuration page
- #75 Virtual Meeting requires location (thanks @kcad !)
- lots more cleanup to testcafe test cases
- copyright license headers added to all relevant source files (in prep for wordpress publish)
- repo renamed to bmlt-workflow (in prep for wordpress publish)

## 0.4.4 (Jul 14, 2022)
- #72 location_province field not populating with info from meeting (thanks @pjaudiomv !)
- #73 database tables not created if bmltwf_db_version found, regardless of whether the tables actually exist (thanks @pjaudiomv !)
- lots of cleanup to testcafe test cases
- new phpunit test cases for BMLTWF_Database
- uninstall now handled cleanly

## 0.4.3 (Jun 20, 2022)
- Multiple css fixes to override Wordpress theming, including a custom select2 theme
- Testcafe now tests option save, and our 'optional form fields' for nation and province #66
- Fix for 'save settings page does not give any notification from wordpress that the settings were in fact saved' #67

## 0.4.2 (Jun 18, 2022)
- Fix for #65 ( thanks @paulnagle !)
- Multiple css and css loader fixes ( thanks @tempsaint @californiasteve !)

## 0.4.1 (Jun 16, 2022)
- Lots of bugfixes from clean site testing
- Testcafe now has a fresh wordpress site constructor, with plugin install, activation and setup
- #62 bmltwf_capability_manage_submissions capabilities not being added correctly on plugin activation (thanks @tempsaint !)
- #61 bmltwf_capability_manage_submissions capabilities not being added correctly to new users (thanks @tempsaint !)
- #60 default settings not being added with register_setting on new installation (thanks @californiasteve kevinC)

## 0.4.0 (Jun 10, 2022)
- Backup and Restore functionality implemented, making plugin deactivation a safe activity and moving this plugin out of beta! #2
- BMLT password now encrypted at rest and in backups, keyed against the Wordpress installation
- Select2 elements correctly reset on form change #57 (thanks @pjaudiomv !)
- PR from @pjaudiomv to take out spurious ampersands
- Removed BMLT XML service body query in favour of more stable json query (thanks @pjaudiomv !)
- We now have an awesome logo! (thanks kevinC !)
- Lots of code refactoring and additional unit tests
- Form now errors if no service bodies are set to show #58 (thanks @pjaudiomv !)
- Municipality text fix in quickedit window #55 (thanks @brustar !)
- Additional REST api sanitisation #6
- Testcafe now tests admin submission page, backup and restore and also end-to-end against crouton

## 0.3.10-beta (May 28th, 2022)
- **Breaking database changes in this release - please deactivate and reactivate the plugin before using**
- Changes requested size validation fixed #7
- Sorting reverse time by default #49 (thanks @brustar !)
- JSON error messages handled on front end #47
- Geolocation fails after trying to add Virtual details #47 (thanks @brustar !)
- Tooltip accessibility fixes #45 (thanks @klgrimley !)
- Virtual meetings (virtual only) will hide location fields to match BMLT admin UI
- Virtual meetings (temp closure) now handled correctly #51
- Fix for 'meeting change email notification message doesn't include some fields' #48 
- BMLT service body descriptive text now shown on service bodies page (thanks @brustar !)
- Fix for starter kit request and details not showing in submission list #39 

## 0.3.9-beta
- Geolocation support for meetings added (thanks @brustar !)

## 0.3.8-beta 
- **Breaking database changes in this release - please deactivate and reactivate the plugin before using**
- 'Other' change type removed #36
- 'Other' service body removed #33 (thanks @brustar !)
- Time display in submission amended when no changes have been made #34
- Day/Time shown in meeting submission change summary #31
- Time display in submission is now in local browser time #37
- Testcafe user form success tests created (and lots of bugs found already!)

## 0.3.7-beta (April 9, 2022)
- Virtual meetings and associated fields now fully supported! https://github.com/bmlt-enabled/bmlt-workflow/issues/25 (thanks @brustar !)
- BMLT configuration UI rewritten
- Additional unit tests and coverage reports

## 0.3.6-beta (April 7, 2022)
- https://github.com/bmlt-enabled/bmlt-workflow/issues/26 (thanks @brustar !)
- Refactoring REST code and unit test components
- Additional unit tests

## 0.3.5-beta (April 5, 2022)
- https://github.com/bmlt-enabled/bmlt-workflow/issues/24, https://github.com/bmlt-enabled/bmlt-workflow/issues/23 - highlighting fixes (thanks @rogersearle !)
- Mini-autoloader to support non composer deploy
- Nation and Sub-province fields are now optional and configurable (hidden / displayed / displayed and required) through the configuration panel 
- Select2 updated to 4.1.0-rc0

## 0.3.4-beta (April 4, 2022)
- https://github.com/bmlt-enabled/bmlt-workflow/issues/20, https://github.com/bmlt-enabled/bmlt-workflow/issues/19, https://github.com/bmlt-enabled/bmlt-workflow/issues/17 - Accessibility fixes (thanks @kgrimley-bu !)
- https://github.com/bmlt-enabled/bmlt-workflow/issues/16 - Quickedit fixes
- Namespace refactoring
- Debug changes to handle CI pipeline nicely

## 0.3.3-beta (March 31, 2022)
* -Beta Known Issues-
* Support for 'Other' service body (for a user to select when they are unsure) is not implemented
* Not tested on wordpress multisite

## 0.32-beta (March 29, 2022)
* Email notification (submitter and trusted servant) and notification to submitter on reject/approval support added. (#10)
* Quickedit now shows the additional info field (thanks @tempsaint!) (#13)
* Support for populating State and Subprovince from BMLT (thanks @pjaudiomv) (#1)
* Many cleanups, bugfixes and extra PHPUnit test cases

## 0.32-beta (March 29, 2022)
* Email notification (submitter and trusted servant) and notification to submitter on reject/approval support added. (#10)
* Quickedit now shows the additional info field (thanks @tempsaint!) (#13)
* Support for populating State and Subprovince from BMLT (thanks @pjaudiomv) (#1)
* Many cleanups, bugfixes and extra PHPUnit test cases

## 0.31-beta (March 28, 2022)
* Request to add email to meeting contact is now honoured through to backend
* Highlighting of changed fields in user form
* Additional PHPUnit test cases for approval rest interface
* Many cleanups and bugfixes

## 0.3-beta (March 26, 2022)
* Hosting repo on BMLT-Enabled! https://github.com/bmlt-enabled/bmlt-workflow
* Support for 'Other' and 'Close' meeting management, including publish or delete of closed meetings. This includes a new settings option for the admin to choose which default for Trusted servants.
* Additional PHPUnit test cases for approval rest interface

## 0.2-beta (March 23, 2022)
* Improved front end form layout
* Close meeting will unpublish an existing meeting
* Submissions now contain full information from front end form
* Additional front end fields added (nation and subprovince)
* PHPUnit test cases built
