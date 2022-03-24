<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

// We require the file we need to test.
require 'admin/meeting_update_form_handler.php';

final class test_meeting_update_form_handler extends TestCase
{

    // array(17) {
    //     "action" =>
    //     string(28) "meeting_update_form_response"
    //     "update_reason" =>
    //     string(12) "reason_close"
    //     "other_reason" =>
    //     string(13) "testing other"
    //     "meeting_id" =>
    //     string(4) "3522"
    //     "duration_time" =>
    //     string(8) "01:00:00"
    //     "service_body_bigint" =>
    //     string(1) "2"
    //     "format_shared_id_list" =>
    //     string(24) "4,7,14,32,33,34,43,44,54"
    //     "virtual_meeting_link" =>
    //     string(0) ""
    //     "first_name" =>
    //     string(4) "name"
    //     "last_name" =>
    //     string(4) "last"
    //     "email_address" =>
    //     string(9) "aa@aa.com"
    //     "contact_number_confidential" =>
    //     string(0) ""
    //     "group_relationship" =>
    //     string(12) "Group Member"
    //     "additional_info" =>
    //     string(0) ""
    //     "starter_kit_required" =>
    //     string(3) "yes"
    //     "starter_kit_postal_address" =>
    //     string(0) ""
    //     "submit" =>
    //     string(11) "Submit Form"
    //   }

    public function test_reason_close() {

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_close",
            "other_reason" => "testing other",
            "meeting_id" => "3522",
            "duration_time" => "01:00:00",
            "service_body_bigint" => "2",
            "format_shared_id_list" => "4,7,14,32,33,34,43,44,54",
            "virtual_meeting_link" => "",
            "first_name" => "name",
            "last_name" => "last",
            "email_address" => "aa@aa.com",
            "contact_number_confidential" => "",
            "group_relationship" => "Group Member",
            "additional_info" => "",
            "starter_kit_required" => "yes",
            "starter_kit_postal_address" => "",
            "submit" => "Submit Form"
        );

        $response = meeting_update_form_handler_rest($form_post);
        var_dump($response);

        $this->assertEquals('Hello', meeting_update_form_handler_rest($form_post));
        $this->assertEquals('Hi', meeting_update_form_handler_rest('Hi'));
    }

    public function test_add() {
        $this->assertEquals(4, meeting_update_form_handler_rest(1, 3));
        $this->assertEquals(10, meeting_update_form_handler_rest(4, 6));
    }
}