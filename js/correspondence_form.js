// Copyright (C) 2022 nigel.bmlt@gmail.com
//
// This file is part of bmlt-workflow.
//
// bmlt-workflow is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// bmlt-workflow is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with bmlt-workflow.  If not, see <http://www.gnu.org/licenses/>.

/* global wp, jQuery, bmltwf_correspondence_data */

const { __ } = wp.i18n;

jQuery(document).ready(function ($) {
  // Format date for display
  function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
  }

  // Show error message with retry option
  function showError(message, canRetry = false) {
    let errorHtml = `<div class="bmltwf-error-message">${message}</div>`;
    if (canRetry) {
      errorHtml += `<button id="bmltwf-retry-button" class="bmltwf-retry-btn">${__('Try Again', 'bmlt-workflow')}</button>`;
    }
    $('#bmltwf-correspondence-error').html(errorHtml).show();
    if (canRetry) {
      $('#bmltwf-retry-button').on('click', function () {
        $('#bmltwf-correspondence-error').hide();
        $('#bmltwf-correspondence-loading').show();
        // eslint-disable-next-line no-use-before-define
        loadCorrespondence();
      });
    }
  }

  // Show reply-specific error
  function showReplyError(message) {
    $('.bmltwf-reply-error').remove();
    const errorHtml = `<div class="bmltwf-reply-error">${message}</div>`;
    $(errorHtml).insertBefore('#bmltwf-reply-form').fadeIn();
  }

  // Validate thread ID and email parameters
  function validateParameters() {
    if (!bmltwf_correspondence_data.thread_id) {
      showError(__('Invalid correspondence link. Please check the URL.', 'bmlt-workflow'));
      return false;
    }
    return true;
  }

  // Load correspondence data
  function loadCorrespondence() {
    if (!validateParameters()) {
      $('#bmltwf-correspondence-loading').hide();
      return;
    }

    $.ajax({
      url: bmltwf_correspondence_data.rest_url,
      method: 'GET',
      timeout: 10000, // 10 second timeout
      beforeSend(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', bmltwf_correspondence_data.nonce);
      },
      success(response) {
        $('#bmltwf-correspondence-loading').hide();

        if (response && response.correspondence && response.correspondence.length > 0) {
          // eslint-disable-next-line no-use-before-define
          displayCorrespondence(response);
        } else if (response && response.correspondence && response.correspondence.length === 0) {
          showError(__('No correspondence found for this submission.', 'bmlt-workflow'));
        } else {
          showError(__('Unable to load correspondence. Please try again later.', 'bmlt-workflow'), true);
        }
      },
      error(xhr, status) {
        $('#bmltwf-correspondence-loading').hide();
        let errorMessage = __('Unable to load correspondence. ', 'bmlt-workflow');

        // Try to get the specific error message from the API response
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        } else if (status === 'timeout') {
          errorMessage += __('The request timed out. Please check your connection and try again.', 'bmlt-workflow');
        } else if (xhr.status === 404) {
          errorMessage += __('Correspondence not found. Please check the URL.', 'bmlt-workflow');
        } else if (xhr.status === 403) {
          errorMessage += __('Access denied. You may not have permission to view this correspondence.', 'bmlt-workflow');
        } else if (xhr.status >= 500) {
          errorMessage += __('Server error. Please try again later.', 'bmlt-workflow');
        } else {
          errorMessage += __('Please check your connection and try again.', 'bmlt-workflow');
        }

        showError(errorMessage, status !== 'timeout' && xhr.status !== 404);
      },
    });
  }

  // Display correspondence data
  function displayCorrespondence(data) {
    const { submission } = data;
    const { correspondence } = data;
    const { is_closed } = data;
    const changeId = submission.change_id;

    // Display header information
    let submissionTypeText = __('Meeting Update', 'bmlt-workflow');
    if (submission.submission_type === 'reason_new') {
      submissionTypeText = __('New Meeting', 'bmlt-workflow');
    } else if (submission.submission_type === 'reason_close') {
      submissionTypeText = __('Close Meeting', 'bmlt-workflow');
    }
    // Get meeting name from submission data
    const meetingName = submission.meeting_name || '';

    let headerHtml = `<h2>${submissionTypeText} ${__('Correspondence', 'bmlt-workflow')}</h2>`;
    if (meetingName) {
      headerHtml += `<p><strong>${__('Meeting:', 'bmlt-workflow')}</strong> ${meetingName}</p>`;
    }
    headerHtml += `<p><strong>${__('Submission Date:', 'bmlt-workflow')}</strong> ${formatDate(submission.submission_time)}</p>`;
    if (is_closed) {
      headerHtml += `<p class="bmltwf-closed-notice"><em>${__('This submission has been closed. You can view the correspondence history but cannot send new messages.', 'bmlt-workflow')}</em></p>`;
    }

    $('#bmltwf-correspondence-header').html(headerHtml).show();

    // Display messages
    let messagesHtml = '';
    correspondence.forEach(function (message) {
      const isSubmitter = message.from_submitter === '1' || message.from_submitter === 1;
      messagesHtml += `<div class="bmltwf-correspondence-message ${
        isSubmitter ? 'bmltwf-submitter-message' : 'bmltwf-admin-message'}">`;
      messagesHtml += `<div class="bmltwf-message-header">${
        isSubmitter ? __('Submitter', 'bmlt-workflow') : __('Admin', 'bmlt-workflow')
      }<span class="bmltwf-message-time">${formatDate(message.created_at)}</span></div>`;
      messagesHtml += `<div class="bmltwf-message-content">${message.message}</div>`;
      messagesHtml += '</div>';
    });

    $('#bmltwf-correspondence-messages').html(messagesHtml).show();
    
    // Only show reply section if submission is not closed
    if (!is_closed) {
      $('#bmltwf-correspondence-reply').show();
      $('#bmltwf-correspondence-reply').data('change-id', changeId);
    }
  }

  // Handle reply button click
  $('#bmltwf-reply-button').on('click', function () {
    $('#bmltwf-reply-form').show();
    $(this).hide();
  });

  // Handle cancel button click
  $('#bmltwf-cancel-reply').on('click', function () {
    $('#bmltwf-reply-form').hide();
    $('#bmltwf-reply-button').show();
    $('#bmltwf-reply-text').val('');
  });

  // Handle send reply button click
  $('#bmltwf-send-reply').on('click', function () {
    const message = $('#bmltwf-reply-text').val().trim();
    if (!message) {
      showReplyError(__('Please enter a message before sending.', 'bmlt-workflow'));
      return;
    }

    if (message.length > 2000) {
      showReplyError(__('Message is too long. Please keep it under 2000 characters.', 'bmlt-workflow'));
      return;
    }

    const changeId = $('#bmltwf-correspondence-reply').data('change-id');
    const $sendButton = $(this);
    const originalText = $sendButton.text();

    // Disable button and show loading state
    $sendButton.prop('disabled', true).text(__('Sending...', 'bmlt-workflow'));
    $('.bmltwf-reply-error').remove();

    $.ajax({
      url: `${bmltwf_correspondence_data.submission_rest_url + changeId}/correspondence`,
      method: 'POST',
      timeout: 15000, // 15 second timeout
      beforeSend(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', bmltwf_correspondence_data.nonce);
      },
      data: {
        message,
        thread_id: bmltwf_correspondence_data.thread_id,
        from_submitter: 'true',
      },
      success() {
        // Show success message
        const successHtml = `<div class="bmltwf-success-message">${
          bmltwf_correspondence_data.i18n.reply_sent
        }</div>`;
        $(successHtml).insertBefore('#bmltwf-correspondence-reply').fadeIn();

        // Reset form
        $('#bmltwf-reply-form').hide();
        $('#bmltwf-reply-button').show();
        $('#bmltwf-reply-text').val('');

        // Reload correspondence after a short delay
        setTimeout(function () {
          $('.bmltwf-success-message').fadeOut(function () {
            $(this).remove();
          });
          loadCorrespondence();
        }, 2000);
      },
      error(xhr, status) {
        let errorMessage = __('Failed to send reply. ', 'bmlt-workflow');

        if (status === 'timeout') {
          errorMessage += __('The request timed out. Please try again.', 'bmlt-workflow');
        } else if (xhr.status === 403) {
          errorMessage += __('Access denied. You may not have permission to reply.', 'bmlt-workflow');
        } else if (xhr.status >= 500) {
          errorMessage += __('Server error. Please try again later.', 'bmlt-workflow');
        } else {
          errorMessage += __('Please check your connection and try again.', 'bmlt-workflow');
        }

        showReplyError(errorMessage);
      },
      complete() {
        // Re-enable button
        $sendButton.prop('disabled', false).text(originalText);
      },
    });
  });

  // Load correspondence on page load with connection check
  if (navigator.onLine === false) {
    $('#bmltwf-correspondence-loading').hide();
    showError(__('No internet connection. Please check your connection and try again.', 'bmlt-workflow'), true);
  } else {
    loadCorrespondence();
  }

  // Handle online/offline events
  $(window).on('online', function () {
    if ($('#bmltwf-correspondence-error').is(':visible')) {
      $('#bmltwf-correspondence-error').hide();
      $('#bmltwf-correspondence-loading').show();
      loadCorrespondence();
    }
  });

  $(window).on('offline', function () {
    showError(__('Connection lost. Please check your internet connection.', 'bmlt-workflow'), true);
  });
});
