<?php

namespace bmltwf\Tests {
    use PHPUnit\Framework\TestCase;
    use Mockery;
    use bmltwf\BMLTWF_Email;
    use Brain\Monkey\Functions;

    class BMLTWF_EmailTest extends TestCase
    {
        private $email;
        private $wp_mail_calls;

        protected function setUp(): void
        {
            parent::setUp();
            \Brain\Monkey\setUp();
            
            $this->wp_mail_calls = [];
            
            // Mock WordPress functions
            Functions\when('get_bloginfo')->alias(function($show) {
                return $show === 'name' ? 'Test Site' : 'admin@test.com';
            });
            Functions\when('get_site_url')->justReturn('https://test.com');
            Functions\when('get_option')->alias(function($option, $default = false) {
                $options = [
                    'bmltwf_email_from_address' => 'from@test.com',
                    'bmltwf_admin_notification_email_subject' => 'Admin: {field:submission_type} - {field:change_id}',
                    'bmltwf_admin_notification_email_template' => 'New submission: {field:submission}',
                    'bmltwf_submitter_email_subject' => 'Acknowledgement: {field:change_id}',
                    'bmltwf_submitter_email_template' => 'Thank you {field:submitter_name}',
                    'bmltwf_approval_email_subject' => 'Approved: {field:change_id}',
                    'bmltwf_approval_email_template' => 'Your request has been approved',
                    'bmltwf_fso_email_subject' => 'FSO Request: {field:name}',
                    'bmltwf_fso_email_template' => 'Starter kit for {field:name}',
                    'bmltwf_correspondence_submitter_email_subject' => 'New message: {field:change_id}',
                    'bmltwf_correspondence_submitter_email_template' => 'You have a new message',
                    'bmltwf_correspondence_admin_email_subject' => 'Admin message: {field:change_id}',
                    'bmltwf_correspondence_admin_email_template' => 'New correspondence received'
                ];
                return $options[$option] ?? $default;
            });
            Functions\when('__')->returnArg();
            
            // Use closure to capture wp_mail calls
            $wp_mail_calls = &$this->wp_mail_calls;
            Functions\when('\wp_mail')->alias(function($to, $subject, $message, $headers = '') use (&$wp_mail_calls) {
                $wp_mail_calls[] = compact('to', 'subject', 'message', 'headers');
                return true;
            });
            
            // Mock debug functionality
            Functions\when('error_log')->justReturn(true);
            if (!defined('BMLTWF_DEBUG')) {
                define('BMLTWF_DEBUG', false);
            }
            global $wpdb, $bmltwf_debug_enabled;
            $wpdb = \Mockery::mock('wpdb');
            $wpdb->shouldReceive('get_var')->andReturn(null);
            $bmltwf_debug_enabled = false;
            
            $this->email = new BMLTWF_Email();
        }

        protected function tearDown(): void
        {
            \Brain\Monkey\tearDown();
            \Mockery::close();
            parent::tearDown();
        }

        public function testSubstituteTemplateFields()
        {
            $template = 'Hello {field:name}, your ID is {field:id}';
            $fields = ['name' => 'John', 'id' => 123];

            $reflection = new \ReflectionClass($this->email);
            $method = $reflection->getMethod('substitute_template_fields');
            $method->setAccessible(true);
            
            $result = $method->invoke($this->email, $template, $fields);

            $this->assertEquals('Hello John, your ID is 123', $result);
        }

        public function testSendTemplatedEmail()
        {
            $to = 'test@example.com';
            $subject_template = 'Subject: {field:type}';
            $body_template = 'Body: {field:message}';
            $fields = ['type' => 'Test', 'message' => 'Hello World'];
            $default_subject = 'Default Subject';

            $result = $this->email->send_templated_email($to, $subject_template, $body_template, $fields, $default_subject);

            $this->assertTrue($result);
            $this->assertCount(1, $this->wp_mail_calls);
            
            $call = $this->wp_mail_calls[0];
            $this->assertEquals('test@example.com', $call['to']);
            $this->assertEquals('Subject: Test', $call['subject']);
            $this->assertEquals('Body: Hello World', $call['message']);
            $this->assertContains('Content-Type: text/html; charset=UTF-8', $call['headers']);
            $this->assertContains('From: Test Site <from@test.com>', $call['headers']);
        }

        public function testSendAdminNotification()
        {
            $context = [
                'change_id' => 123,
                'submission_type' => 'New Meeting',
                'service_body_name' => 'Test Service Body'
            ];
            $additional_fields = ['submission' => 'Meeting details'];

            $result = $this->email->send_admin_notification('admin@test.com', $context, $additional_fields);

            $this->assertTrue($result);
            $this->assertNotEmpty($this->wp_mail_calls);
            $this->assertCount(1, $this->wp_mail_calls);
            
            $call = $this->wp_mail_calls[0];
            $this->assertEquals('admin@test.com', $call['to']);
            $this->assertEquals('Admin: New Meeting - 123', $call['subject']);
            $this->assertEquals('New submission: Meeting details', $call['message']);
        }

        public function testSendSubmitterAcknowledgement()
        {
            $context = ['change_id' => 123, 'submitter_name' => 'John Doe'];
            $additional_fields = ['submission' => 'Your submission'];

            $result = $this->email->send_submitter_acknowledgement('user@test.com', $context, $additional_fields);

            $this->assertTrue($result);
            $this->assertNotEmpty($this->wp_mail_calls);
            $call = $this->wp_mail_calls[0];
            $this->assertEquals('user@test.com', $call['to']);
            $this->assertEquals('Acknowledgement: 123', $call['subject']);
            $this->assertEquals('Thank you John Doe', $call['message']);
        }

        public function testSendApprovalEmail()
        {
            $context = ['change_id' => 123, 'submitter_name' => 'John Doe'];
            $additional_fields = ['action_message' => 'Great job!'];

            $result = $this->email->send_approval_email('user@test.com', $context, $additional_fields);

            $this->assertTrue($result);
            $this->assertNotEmpty($this->wp_mail_calls);
            $call = $this->wp_mail_calls[0];
            $this->assertEquals('user@test.com', $call['to']);
            $this->assertEquals('Approved: 123', $call['subject']);
            $this->assertEquals('Your request has been approved', $call['message']);
        }

        public function testSendFsoEmail()
        {
            $context = ['submitter_name' => 'John Doe', 'name' => 'New Meeting'];
            $additional_fields = [
                'starter_kit_postal_address' => '123 Main St',
                'contact_number' => '555-1234'
            ];

            $result = $this->email->send_fso_email('fso@test.com', $context, $additional_fields);

            $this->assertTrue($result);
            $this->assertNotEmpty($this->wp_mail_calls);
            $call = $this->wp_mail_calls[0];
            $this->assertEquals('fso@test.com', $call['to']);
            $this->assertEquals('FSO Request: New Meeting', $call['subject']);
            $this->assertEquals('Starter kit for New Meeting', $call['message']);
        }

        public function testAllEmailMethodsWithMeetingData()
        {
            $context = ['change_id' => 123, 'submitter_name' => 'John Doe'];
            $meeting_data = [
                'name' => 'Weekly Meeting',
                'day' => 1,
                'startTime' => '19:00',
                'location_text' => 'Community Center'
            ];

            // Test that all email methods accept meeting data without errors
            $this->email->send_admin_notification('admin@test.com', $context, [], $meeting_data);
            $this->email->send_submitter_acknowledgement('user@test.com', $context, [], $meeting_data);
            $this->email->send_approval_email('user@test.com', $context, [], $meeting_data);
            $this->email->send_fso_email('fso@test.com', $context, [], $meeting_data);
            $this->email->send_correspondence_submitter_notification('user@test.com', $context, [], $meeting_data);
            $this->email->send_correspondence_admin_notification('admin@test.com', $context, [], $meeting_data);

            $this->assertCount(6, $this->wp_mail_calls);
            
            // Verify all emails were sent successfully
            foreach ($this->wp_mail_calls as $call) {
                $this->assertNotEmpty($call['to']);
                $this->assertNotEmpty($call['subject']);
                $this->assertNotEmpty($call['message']);
            }
        }
    }
}
