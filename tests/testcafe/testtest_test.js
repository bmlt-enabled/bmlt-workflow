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

import { Selector } from "testcafe";

fixture`test_test_fixture`
  // .page(userVariables.admin_submissions_page_single)
  ;

test("testtest", async (t) => {

  await t.navigateTo("http://wordpress-php8-singlesite/a.html")
  .typeText("#myinput","hello")
  .expect(Selector("#myinput").value).eql("hello");
}
);