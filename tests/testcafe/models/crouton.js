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