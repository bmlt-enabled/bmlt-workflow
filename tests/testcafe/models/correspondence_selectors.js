import { Selector } from 'testcafe';

export const cs = {
    correspondenceButton: Selector('button').withText('Correspondence'),
    correspondenceModal: Selector('#correspondence-modal'),
    correspondenceTextarea: Selector('#correspondence-message'),
    sendButton: Selector('#send-correspondence'),
    correspondenceHistory: Selector('#correspondence-history'),
    correspondenceForm: Selector('[id*="correspondence-form"]'),
    submitButton: Selector('#submit-correspondence'),
    successMessage: Selector('.success-message'),
    firstRow: Selector('table#dt-submission tbody tr').nth(0)
};