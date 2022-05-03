import { Selector } from 'testcafe';
import { uf } from './models/meeting_update_form';

import { 
    select_dropdown_by_id,
    select_dropdown_by_text,
    select_dropdown_by_value
} 
from './helpers/helper.js';
import { userVariables } from '../../.testcaferc';

// const reason = Selector('#update_reason');
// const reasonOption = reason.find('option');

fixture `meeting_update_form_fixture`
    .page(userVariables.formpage);

test('e2e_New_meeting', async t => {

    await t.navigateTo(userVariables.formpage);

    await select_dropdown_by_value(uf.update_reason,'reason_new');

    // check our divs are visible
    await t
    .expect(uf.update_reason.value).eql('reason_new')

    // validate form is laid out correctly
    .expect(uf.personal_details.visible).eql(true)
    .expect(uf.meeting_details.visible).eql(true)
    .expect(uf.additional_info_div.visible).eql(true);

    // personal details
    await t
    .typeText(uf.first_name, 'first')
    .typeText(uf.last_name, 'last')
    .typeText(uf.email_address, 'test@test.com.zz')
    .typeText(uf.contact_number_confidential, '`12345`')

    // email dropdown
    await select_dropdown_by_text(uf.add_email,'Yes');
    await t
    .expect(uf.add_email.value).eql('yes');

    // group member dropdown
    await select_dropdown_by_value(uf.group_relationship,'Group Member');
    await t
    .expect(uf.group_relationship.value).eql('Group Member');

    // virtual meeting settings
    await select_dropdown_by_value(uf.virtual_hybrid_select,'hybrid');
    await t
    .expect(uf.virtual_hybrid_select.value).eql('hybrid')
    .expect(uf.virtual_meeting_link.visible).eql(true)
    .expect(uf.phone_meeting_number.visible).eql(true)
    .expect(uf.virtual_meeting_additional_info.visible).eql(true);
    await t
    .typeText(uf.phone_meeting_number, '+61 1800 253430 code #8303782669')
    .typeText(uf.virtual_meeting_link, 'https://us02web.zoom.us/j/83037287669?pwd=OWRRQU52ZC91TUpEUUExUU40eTh2dz09')
    .typeText(uf.virtual_meeting_additional_info, 'Zoom ID 83037287669 Passcode: testing');

    // meeting settings
    await t
    .typeText(uf.meeting_name, 'my test meeting');

    await select_dropdown_by_text(uf.weekday_tinyint,'Monday');

    await t 
    .typeText(uf.start_time, '10:40');

    await select_dropdown_by_value(uf.duration_hours,'04');
    await select_dropdown_by_value(uf.duration_minutes,'30');

    // format list
    await t
    .click(uf.format_list_clickable)
    .pressKey('b e g enter')
    .click(uf.format_list_clickable)
    .pressKey('l i n enter');

    await t 

    .typeText(uf.location_text, 'my location')
    .typeText(uf.location_street, 'street')
    .typeText(uf.location_info, 'info')
    .typeText(uf.location_municipality, 'municipality')
    // .typeText(uf.location_sub_province, 'subprovince')
    .typeText(uf.location_province, 'province')
    .typeText(uf.location_postal_code_1, '1234');

    await select_dropdown_by_text(uf.service_body_bigint,'a-level1');
    await t
    .typeText(uf.additional_info, 'my additional info');

    await select_dropdown_by_value(uf.starter_kit_required,'yes');
    await t
    // .typeText(uf.starter_kit_postal_address, 'postal address')
    .typeText(uf.starter_kit_postal_address, 'postal address')
    .expect(uf.starter_kit_postal_address.value).eql('postal address');

    await t
    .click(uf.submit)
    .expect(Selector('#page h3').innerText).match(/submission\ successful/);

});

test('Success_Change_Meeting_Name_And_Submit', async t => {

    await select_dropdown_by_value(uf.update_reason,'reason_change');

    // check our divs are visible
    await t
    .expect(uf.update_reason.value).eql('reason_change');

    // meeting selector
    await t.click('#select2-meeting-searcher-container');
    await t.typeText(Selector('[aria-controls="select2-meeting-searcher-results"]'),'Avalon');
    await t.pressKey('enter');

    // validate form is laid out correctly
    await t
    .expect(uf.personal_details.visible).eql(true)
    .expect(uf.meeting_details.visible).eql(true)
    .expect(uf.additional_info_div.visible).eql(true);

    
    // personal details
    await t
    .typeText(uf.first_name, 'first')
    .typeText(uf.last_name, 'last')
    .typeText(uf.email_address, 'test@test.com.zz')
    .typeText(uf.contact_number_confidential, '`12345`')

    .typeText(uf.meeting_name, 'update')
    // make sure highlighting is present
    .expect(uf.meeting_name.hasClass('wbw-changed')).ok();

    // email dropdown
    await select_dropdown_by_text(uf.add_email,'Yes');
    await t
    .expect(uf.add_email.value).eql('yes');

    // group member dropdown
    await select_dropdown_by_value(uf.group_relationship,'Group Member');
    await t
    .expect(uf.group_relationship.value).eql('Group Member');

    await t
    .typeText(uf.additional_info, 'my additional info');

    await t
    .click(uf.submit)
    .expect(Selector('#page h3').innerText).match(/submission\ successful/);

});

test('Success_Close_Meeting_And_Submit', async t => {

    await select_dropdown_by_value(uf.update_reason,'reason_close');

    // check our divs are visible
    await t
    .expect(uf.update_reason.value).eql('reason_close');

    // meeting selector
    await t.click('#select2-meeting-searcher-container');
    await t.typeText(Selector('[aria-controls="select2-meeting-searcher-results"]'),'Avalon');
    await t.pressKey('enter');

    // validate form is laid out correctl
    await t
    .expect(uf.personal_details.visible).eql(true)
    .expect(uf.meeting_details.visible).eql(true)
    .expect(uf.additional_info_div.visible).eql(true);

    
    // personal details
    await t
    .typeText(uf.first_name, 'first')
    .typeText(uf.last_name, 'last')
    .typeText(uf.email_address, 'test@test.com.zz')
    .typeText(uf.contact_number_confidential, '`12345`')

    .typeText(uf.meeting_name, 'update')

    // email dropdown
    await select_dropdown_by_text(uf.add_email,'Yes');
    await t
    .expect(uf.add_email.value).eql('yes');

    // group member dropdown
    await select_dropdown_by_value(uf.group_relationship,'Group Member');
    await t
    .expect(uf.group_relationship.value).eql('Group Member');

    await t
    .typeText(uf.additional_info, 'my additional info');

    await t
    .click(uf.submit)
    .expect(Selector('#page h3').innerText).match(/submission\ successful/);

});