import { Selector } from 'testcafe';

export const cs = {
    // Admin page selectors
    correspondenceButton: Selector('button').withText('Correspondence'),
    correspondenceModal: Selector('#bmltwf_submission_correspondence_dialog'),
    correspondenceTextarea: Selector('#bmltwf-correspondence-message'),
    sendButton: Selector('#bmltwf-send-correspondence'),
    correspondenceHistory: Selector('#bmltwf-correspondence-messages'),
    
    // Public page selectors
    correspondenceForm: Selector('#bmltwf-correspondence-container'),
    submitButton: Selector('#bmltwf-send-reply'),
    replyButton: Selector('#bmltwf-reply-button'),
    replyTextarea: Selector('#bmltwf-reply-text'),
    successMessage: Selector('.bmltwf-success-message'),
    
    // Common selectors
    firstRow: Selector('table#dt-submission tbody tr').nth(0)
};