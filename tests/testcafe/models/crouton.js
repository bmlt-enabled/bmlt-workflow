import { Selector } from 'testcafe';

class Crouton {
    constructor () {
        this.groups_dropdown = Selector('span[aria-labelledby="select2-filter-dropdown-groups-container"]');
        this.meeting_name = Selector('#byday tr:not(.hide) .meeting-name');
        this.location_text = Selector('#byday tr:not(.hide) .location-text');
        this.virtual_meeting_link = Selector('#byday tr:not(.hide) .bmlt-column3 .glyphicon-globe + a');
        this.phone_meeting_number = Selector('#byday tr:not(.hide) .bmlt-column3 .glyphicon-earphone + a');
    }
}

export const ct = new Crouton();