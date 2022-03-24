<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

// We require the file we need to test.
require 'admin/meeting-update-form-handler.php';

final class test_meeting_update_form_handler extends TestCase
{
    public function test_reason_other() {

        $this->assertEquals('Hello', meeting_update_form_handler_rest('Hello'));
        $this->assertEquals('Hi', meeting_update_form_handler_rest('Hi'));
    }

    public function test_add() {
        $this->assertEquals(4, meeting_update_form_handler_rest(1, 3));
        $this->assertEquals(10, meeting_update_form_handler_rest(4, 6));
    }
}