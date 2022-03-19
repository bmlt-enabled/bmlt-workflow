<?php

if (!defined('ABSPATH')) exit; // die if being called directly

wp_nonce_field('wp_rest', '_wprestnonce');

?>
<!-- Approve dialog -->
<div id="bmaw_submission_approve_dialog" class="hidden" style="max-width:800px">
    <label class='dialog_label' for="bmaw_submission_approve_dialog_textarea">Approval note:</label>
    <textarea class='dialog_textarea' id="bmaw_submission_approve_dialog_textarea" rows=5 cols=60 placeholder='Add a note to this approval for the submitter'></textarea>
    <p>You can use the quickedit function to make any extra changes before approval.</p>
    <p>Are you sure you would like to approve the submission?</p>
</div>

<!-- Delete dialog -->
<div id="bmaw_submission_delete_dialog" class="hidden" style="max-width:800px">
    <p>This change cannot be undone. Use this to remove an entirely unwanted submission from the list.</p>
    <p>Are you sure you would like to delete the submission completely?</p>
</div>

<!-- Reject dialog -->
<div id="bmaw_submission_reject_dialog" class="hidden" style="max-width:800px">
    <label class='dialog_label' for="bmaw_submission_reject_dialog_textarea">Rejection note:</label>
    <textarea class='dialog_textarea' id="bmaw_submission_reject_dialog_textarea" rows=5 cols=60 placeholder='Add a note to this rejection for the submitter'></textarea>
    <p>Are you sure you would like to reject this submission?</p>
</div>

<!-- Quickedit dialog -->
<div id="bmaw_submission_quickedit_dialog" class="hidden" >
    <div>
                <label for="start_time">Start Time<span class="bmaw-required-field"> *</span></label>
                <input type="text" name="start_time" size="10" id="start_time" required>
    </div>
    <div>
                <label for="start_time">Start Time<span class="bmaw-required-field"> *</span></label>
                <input type="text" name="start_time" size="10" id="start_time1" required>
    </div>
    <div>
                <label for="start_time">Start Time<span class="bmaw-required-field"> *</span></label>
                <input type="text" name="start_time" size="10" id="start_time2" required>
    </div>
    <div>
                <label for="start_time">Start Time<span class="bmaw-required-field"> *</span></label>
                <input type="text" name="start_time" size="10" id="start_time3" required>
    </div>
    <div>
                <label for="start_time">Start Time<span class="bmaw-required-field"> *</span></label>
                <input type="text" name="start_time" size="10" id="start_time4" required>
    </div>
    <div>
                <label for="start_time">Start Time<span class="bmaw-required-field"> *</span></label>
                <input type="text" name="start_time" size="10" id="start_time5" required>
    </div>
</div>

<div class="wrap">
    <div id="icon-users" class="icon32"></div>
    <h2>Meeting Submissions</h2>
    <hr class="wp-header-end">
    <div class="dt-container">
        <table id="dt-submission" class="display" style="width:90%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Submitter Name</th>
                    <th>Submitter Email</th>
                    <th>Change Summary</th>
                    <th>Submission Time</th>
                    <th>Change Time</th>
                    <th>Changed By</th>
                    <th>Change Made</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th>ID</th>
                    <th>Submitter Name</th>
                    <th>Submitter Email</th>
                    <th>Change Summary</th>
                    <th>Submission Time</th>
                    <th>Change Time</th>
                    <th>Changed By</th>
                    <th>Change Made</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
