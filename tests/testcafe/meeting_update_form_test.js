import { Selector } from 'testcafe';

var host = "http://54.153.167.239/flop/sample-page-2/";

const reason = Selector('#update_reason');
const reasonOption = reason.find('option');

fixture `meeting_update_form`
    .page(host);

test('New Meeting Submit Form', async t => {
    await t
    .click(reason)
    .click(reasonOption.withText('New Meeting'))
    .expect(reason.value).eql('reason_new')

    .typeText('#first_name', 'first')
    .typeText('#last_name', 'last')
    .typeText('#email_address', 'test@test.com.zz')
    .typeText('#first_name', 'first')
    .typeText('#first_name', 'first')

    .click(Selector('#add_email'))
    .click(Selector('#add_email').find('option').withText('Yes'))

    // <select name="add_email" id="add_email">
    //     <option value="yes">Yes</option>
    //     <option value="no" selected>No</option>
    // </select>
    // <label for="contact_number_confidential">Contact Number (Confidential)</label>
    // <input type="number" name="contact_number_confidential" id="contact_number_confidential">
    // <label for="group_relationship">Relationship to group</label>

});