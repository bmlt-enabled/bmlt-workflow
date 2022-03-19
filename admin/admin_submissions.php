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
<div id="bmaw_submission_quickedit_dialog" class="hidden bmaw_submission_quickedit_dialog">
        <div class="bmaw-grid-col1">
            <label for="col1a">col1a</label>
            <input type="text" name="col1a" id="col1a" required>
            <label for="col1b">col1b</label>
            <input type="text" name="col1b" id="col1b" required>
            <label for="col1c">col1c</label>
            <input type="text" name="col1c" id="col1c" required>
            <label for="col1d">col1d</label>
            <input type="text" name="col1d" id="col1d" required>
            <label for="col1e">col1e</label>
            <input type="text" name="col1e" id="col1e" required>
            <label for="col1f">col1f</label>
            <input type="text" name="col1f" id="col1f" required>
        </div>
        <div class="bmaw-grid-col2">
            <label for="col2a">col2a</label>
            <input type="text" name="col2a" id="col2a" required>
            <label for="col2b">col2b</label>
            <input type="text" name="col2b" id="col2b" required>
            <label for="col2c">col2c</label>
            <input type="text" name="col2c" id="col2c" required>
            <label for="col2d">col2d</label>
            <input type="text" name="col2d" id="col2d" required>
            <label for="col2e">col2e</label>
            <input type="text" name="col2e" id="col2e" required>
            <label for="col2f">col2f</label>
            <input type="text" name="col2f" id="col2f" required>
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