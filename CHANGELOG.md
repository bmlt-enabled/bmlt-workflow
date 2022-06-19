## 0.4.3 (Jun 19, 2022)
- Multiple css fixes to override Wordpress theming, including a custom select2 theme
- Testcafe now tests option save, and our 'optional form fields' for nation and province

## 0.4.2 (Jun 18, 2022)
- Fix for #65 ( thanks @paulnagle !)
- Multiple css and css loader fixes ( thanks @tempsaint @californiasteve !)

## 0.4.1 (Jun 16, 2022)
- Lots of bugfixes from clean site testing
- Testcafe now has a fresh wordpress site constructor, with plugin install, activation and setup
- #62 wbw_capability_manage_submissions capabilities not being added correctly on plugin activation (thanks @tempsaint !)
- #61 wbw_capability_manage_submissions capabilities not being added correctly to new users (thanks @tempsaint !)
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
- Virtual meetings and associated fields now fully supported! https://github.com/bmlt-enabled/wordpress-bmlt-workflow/issues/25 (thanks @brustar !)
- BMLT configuration UI rewritten
- Additional unit tests and coverage reports

## 0.3.6-beta (April 7, 2022)
- https://github.com/bmlt-enabled/wordpress-bmlt-workflow/issues/26 (thanks @brustar !)
- Refactoring REST code and unit test components
- Additional unit tests

## 0.3.5-beta (April 5, 2022)
- https://github.com/bmlt-enabled/wordpress-bmlt-workflow/issues/24, https://github.com/bmlt-enabled/wordpress-bmlt-workflow/issues/23 - highlighting fixes (thanks @rogersearle !)
- Mini-autoloader to support non composer deploy
- Nation and Sub-province fields are now optional and configurable (hidden / displayed / displayed and required) through the configuration panel 
- Select2 updated to 4.1.0-rc0

## 0.3.4-beta (April 4, 2022)
- https://github.com/bmlt-enabled/wordpress-bmlt-workflow/issues/20, https://github.com/bmlt-enabled/wordpress-bmlt-workflow/issues/19, https://github.com/bmlt-enabled/wordpress-bmlt-workflow/issues/17 - Accessibility fixes (thanks @kgrimley-bu !)
- https://github.com/bmlt-enabled/wordpress-bmlt-workflow/issues/16 - Quickedit fixes
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
* Hosting repo on BMLT-Enabled! https://github.com/bmlt-enabled/wordpress-bmlt-workflow
* Support for 'Other' and 'Close' meeting management, including publish or delete of closed meetings. This includes a new settings option for the admin to choose which default for Trusted servants.
* Additional PHPUnit test cases for approval rest interface

## 0.2-beta (March 23, 2022)
* Improved front end form layout
* Close meeting will unpublish an existing meeting
* Submissions now contain full information from front end form
* Additional front end fields added (nation and subprovince)
* PHPUnit test cases built
