// import { Selector } from 'testcafe';
import { uf } from './models/meeting_update_form';

// const reason = Selector('#update_reason');
// const reasonOption = reason.find('option');

fixture `meeting_update_form_fixture`
    .page(uf.page_location);

test('New Meeting Submit Form', async t => {
    await t
    .click(uf.update_reason)
    .click((uf.update_reason).find('option').withText('New Meeting'))
    .expect(uf.update_reason.value).eql('reason_new')

    .typeText(uf.first_name, 'first')
    .typeText(uf.last_name, 'last')
    .typeText(uf.email_address, 'test@test.com.zz')
    .typeText(uf.contact_number_confidential, '`12345`')
    .typeText(uf.first_name, 'first')

    .click(uf.add_email)
    .click((uf.add_email).find('option').withText('Yes'))

    .click(uf.group_relationship)
    .click((uf.group_relationship).find('option').withText('Group Member'))

    .click(uf.submit)

});