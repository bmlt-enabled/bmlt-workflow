/* global bmltwf_correspondence_data */
jQuery(document).ready(function($) {
    // Load correspondence data
    function loadCorrespondence() {
        $.ajax({
            url: bmltwf_correspondence_data.rest_url,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', bmltwf_correspondence_data.nonce);
            },
            success: function(response) {
                $('#bmltwf-correspondence-loading').hide();
                
                if (response && response.correspondence && response.correspondence.length > 0) {
                    displayCorrespondence(response);
                } else {
                    $('#bmltwf-correspondence-error').text(bmltwf_correspondence_data.i18n.error).show();
                }
            },
            error: function() {
                $('#bmltwf-correspondence-loading').hide();
                $('#bmltwf-correspondence-error').text(bmltwf_correspondence_data.i18n.error).show();
            }
        });
    }
    
    // Format date for display
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString();
    }
    
    // Display correspondence data
    function displayCorrespondence(data) {
        const submission = data.submission;
        const correspondence = data.correspondence;
        const changeId = submission.change_id;
        
        // Display header information
        let headerHtml = '<h2>' + (submission.submission_type === 'reason_new' ? 'New Meeting' : 
                                  (submission.submission_type === 'reason_close' ? 'Close Meeting' : 'Meeting Update')) + 
                         ' Correspondence</h2>';
        headerHtml += '<p><strong>Submitter:</strong> ' + submission.submitter_name + '</p>';
        headerHtml += '<p><strong>Submission Date:</strong> ' + formatDate(submission.submission_time) + '</p>';
        
        $('#bmltwf-correspondence-header').html(headerHtml).show();
        
        // Display messages
        let messagesHtml = '';
        correspondence.forEach(function(message) {
            const isSubmitter = message.from_submitter === '1' || message.from_submitter === 1;
            messagesHtml += '<div class="bmltwf-correspondence-message ' + 
                           (isSubmitter ? 'bmltwf-submitter-message' : 'bmltwf-admin-message') + '">';
            messagesHtml += '<div class="bmltwf-message-header">' + 
                           (isSubmitter ? 'Submitter' : 'Admin') + 
                           '<span class="bmltwf-message-time">' + formatDate(message.created_at) + '</span></div>';
            messagesHtml += '<div class="bmltwf-message-content">' + message.message + '</div>';
            messagesHtml += '</div>';
        });
        
        $('#bmltwf-correspondence-messages').html(messagesHtml).show();
        $('#bmltwf-correspondence-reply').show();
        
        // Store change_id for reply
        $('#bmltwf-correspondence-reply').data('change-id', changeId);
    }
    
    // Handle reply button click
    $('#bmltwf-reply-button').on('click', function() {
        $('#bmltwf-reply-form').show();
        $(this).hide();
    });
    
    // Handle cancel button click
    $('#bmltwf-cancel-reply').on('click', function() {
        $('#bmltwf-reply-form').hide();
        $('#bmltwf-reply-button').show();
        $('#bmltwf-reply-text').val('');
    });
    
    // Handle send reply button click
    $('#bmltwf-send-reply').on('click', function() {
        const message = $('#bmltwf-reply-text').val().trim();
        if (!message) {
            return;
        }
        
        const changeId = $('#bmltwf-correspondence-reply').data('change-id');
        
        $.ajax({
            url: bmltwf_correspondence_data.submission_rest_url + changeId + '/correspondence',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', bmltwf_correspondence_data.nonce);
            },
            data: {
                message: message,
                thread_id: bmltwf_correspondence_data.thread_id,
                from_submitter: 'true'
            },
            success: function(response) {
                // Show success message
                const successHtml = '<div class="bmltwf-success-message">' + 
                                   bmltwf_correspondence_data.i18n.reply_sent + 
                                   '</div>';
                $(successHtml).insertBefore('#bmltwf-correspondence-reply').fadeIn();
                
                // Reset form
                $('#bmltwf-reply-form').hide();
                $('#bmltwf-reply-button').show();
                $('#bmltwf-reply-text').val('');
                
                // Reload correspondence after a short delay
                setTimeout(function() {
                    $('.bmltwf-success-message').fadeOut(function() {
                        $(this).remove();
                    });
                    loadCorrespondence();
                }, 2000);
            },
            error: function() {
                alert(bmltwf_correspondence_data.i18n.reply_error);
            }
        });
    });
    
    // Load correspondence on page load
    loadCorrespondence();
});