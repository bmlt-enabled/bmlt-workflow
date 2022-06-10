<?php

declare(strict_types=1);

use wbw\WBW_Debug;
use wbw\BMLT\Integration;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};

require_once('config_phpunit.php');

/**
 * @covers wbw\BMLT\Integration
 * @uses wbw\WBW_Debug
 * @uses wbw\WBW_WP_Options
 */
final class IntegrationTest extends TestCase
{
    protected function setVerboseErrorHandler()
    {
        $handler = function ($errorNumber, $errorString, $errorFile, $errorLine) {
            echo "
ERROR INFO
Message: $errorString
File: $errorFile
Line: $errorLine
";
        };
        set_error_handler($handler);
    }

    protected function setUp(): void
    {

        $this->setVerboseErrorHandler();
        $basedir = getcwd();
        require_once($basedir . '/vendor/antecedent/patchwork/Patchwork.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/class-wp-error.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/class-wp-http-response.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/endpoints/class-wp-rest-controller.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-response.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-request.php');
        if (!class_exists('wpdb')) {
            require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/wp-db.php');
        }

        $this->formats = '<?xml version="1.0" encoding="UTF-8"?><formats xmlns="http://54.153.167.239" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://54.153.167.239/blank_bmlt/main_server/client_interface/xsd/GetFormats.php"><row sequence_index="0"><key_string>B</key_string><name_string>Beginners</name_string><description_string>This meeting is focused on the needs of new members of NA.</description_string><lang>en</lang><id>1</id><world_id>BEG</world_id></row><row sequence_index="1"><key_string>BL</key_string><name_string>Bi-Lingual</name_string><description_string>This Meeting can be attended by speakers of English and another language.</description_string><lang>en</lang><id>2</id><world_id>LANG</world_id></row><row sequence_index="2"><key_string>BT</key_string><name_string>Basic Text</name_string><description_string>This meeting is focused on discussion of the Basic Text of Narcotics Anonymous.</description_string><lang>en</lang><id>3</id><world_id>BT</world_id></row><row sequence_index="3"><key_string>C</key_string><name_string>Closed</name_string><description_string>This meeting is closed to non-addicts. You should attend only if you believe that you may have a problem with substance abuse.</description_string><lang>en</lang><id>4</id><world_id>CLOSED</world_id></row><row sequence_index="4"><key_string>CH</key_string><name_string>Closed Holidays</name_string><description_string>This meeting gathers in a facility that is usually closed on holidays.</description_string><lang>en</lang><id>5</id><world_id>CH</world_id></row><row sequence_index="5"><key_string>CL</key_string><name_string>Candlelight</name_string><description_string>This meeting is held by candlelight.</description_string><lang>en</lang><id>6</id><world_id>CAN</world_id></row><row sequence_index="6"><key_string>CS</key_string><name_string>Children under Supervision</name_string><description_string>Well-behaved, supervised children are welcome.</description_string><lang>en</lang><id>7</id></row><row sequence_index="7"><key_string>D</key_string><name_string>Discussion</name_string><description_string>This meeting invites participation by all attendees.</description_string><lang>en</lang><id>8</id><world_id>DISC</world_id></row><row sequence_index="8"><key_string>ES</key_string><name_string>Español</name_string><description_string>This meeting is conducted in Spanish.</description_string><lang>en</lang><id>9</id><world_id>LANG</world_id></row><row sequence_index="9"><key_string>GL</key_string><name_string>Gay/Lesbian/Transgender</name_string><description_string>This meeting is focused on the needs of gay, lesbian and transgender members of NA.</description_string><lang>en</lang><id>10</id><world_id>GL</world_id></row><row sequence_index="10"><key_string>IL</key_string><name_string>Illness</name_string><description_string>This meeting is focused on the needs of NA members with chronic illness.</description_string><lang>en</lang><id>11</id></row><row sequence_index="11"><key_string>IP</key_string><name_string>Informational Pamphlet</name_string><description_string>This meeting is focused on discussion of one or more Informational Pamphlets.</description_string><lang>en</lang><id>12</id><world_id>IP</world_id></row><row sequence_index="12"><key_string>IW</key_string><name_string>It Works -How and Why</name_string><description_string>This meeting is focused on discussion of the It Works -How and Why text.</description_string><lang>en</lang><id>13</id><world_id>IW</world_id></row><row sequence_index="13"><key_string>JT</key_string><name_string>Just for Today</name_string><description_string>This meeting is focused on discussion of the Just For Today text.</description_string><lang>en</lang><id>14</id><world_id>JFT</world_id></row><row sequence_index="14"><key_string>M</key_string><name_string>Men</name_string><description_string>This meeting is meant to be attended by men only.</description_string><lang>en</lang><id>15</id><world_id>M</world_id></row><row sequence_index="15"><key_string>NC</key_string><name_string>No Children</name_string><description_string>Please do not bring children to this meeting.</description_string><lang>en</lang><id>16</id><world_id>NC</world_id></row><row sequence_index="16"><key_string>O</key_string><name_string>Open</name_string><description_string>This meeting is open to addicts and non-addicts alike. All are welcome.</description_string><lang>en</lang><id>17</id><world_id>OPEN</world_id></row><row sequence_index="17"><key_string>Pi</key_string><name_string>Pitch</name_string><description_string>This meeting has a format that consists of each person who shares picking the next person.</description_string><lang>en</lang><id>18</id></row><row sequence_index="18"><key_string>RF</key_string><name_string>Rotating Format</name_string><description_string>This meeting has a format that changes for each meeting.</description_string><lang>en</lang><id>19</id><world_id>VAR</world_id></row><row sequence_index="19"><key_string>Rr</key_string><name_string>Round Robin</name_string><description_string>This meeting has a fixed sharing order (usually a circle.)</description_string><lang>en</lang><id>20</id></row><row sequence_index="20"><key_string>SC</key_string><name_string>Security Cameras</name_string><description_string>This meeting is held in a facility that has security cameras.</description_string><lang>en</lang><id>21</id></row><row sequence_index="21"><key_string>SD</key_string><name_string>Speaker/Discussion</name_string><description_string>This meeting is lead by a speaker, then opened for participation by attendees.</description_string><lang>en</lang><id>22</id><world_id>S-D</world_id></row><row sequence_index="22"><key_string>SG</key_string><name_string>Step Working Guide</name_string><description_string>This meeting is focused on discussion of the Step Working Guide text.</description_string><lang>en</lang><id>23</id><world_id>SWG</world_id></row><row sequence_index="23"><key_string>SL</key_string><name_string>ASL</name_string><description_string>This meeting provides an American Sign Language (ASL) interpreter for the deaf.</description_string><lang>en</lang><id>24</id></row><row sequence_index="24"><key_string>So</key_string><name_string>Speaker Only</name_string><description_string>This meeting is a speaker-only meeting. Other attendees do not participate in the discussion.</description_string><lang>en</lang><id>26</id><world_id>SPK</world_id></row><row sequence_index="25"><key_string>St</key_string><name_string>Step</name_string><description_string>This meeting is focused on discussion of the Twelve Steps of NA.</description_string><lang>en</lang><id>27</id><world_id>STEP</world_id></row><row sequence_index="26"><key_string>Ti</key_string><name_string>Timer</name_string><description_string>This meeting has sharing time limited by a timer.</description_string><lang>en</lang><id>28</id></row><row sequence_index="27"><key_string>To</key_string><name_string>Topic</name_string><description_string>This meeting is based upon a topic chosen by a speaker or by group conscience.</description_string><lang>en</lang><id>29</id><world_id>TOP</world_id></row><row sequence_index="28"><key_string>Tr</key_string><name_string>Tradition</name_string><description_string>This meeting is focused on discussion of the Twelve Traditions of NA.</description_string><lang>en</lang><id>30</id><world_id>TRAD</world_id></row><row sequence_index="29"><key_string>TW</key_string><name_string>Traditions Workshop</name_string><description_string>This meeting engages in detailed discussion of one or more of the Twelve Traditions of N.A.</description_string><lang>en</lang><id>31</id><world_id>TRAD</world_id></row><row sequence_index="30"><key_string>W</key_string><name_string>Women</name_string><description_string>This meeting is meant to be attended by women only.</description_string><lang>en</lang><id>32</id><world_id>W</world_id></row><row sequence_index="31"><key_string>WC</key_string><name_string>Wheelchair</name_string><description_string>This meeting is wheelchair accessible.</description_string><lang>en</lang><id>33</id><world_id>WCHR</world_id></row><row sequence_index="32"><key_string>YP</key_string><name_string>Young People</name_string><description_string>This meeting is focused on the needs of younger members of NA.</description_string><lang>en</lang><id>34</id><world_id>Y</world_id></row><row sequence_index="33"><key_string>OE</key_string><name_string>Open-Ended</name_string><description_string>No fixed duration. The meeting continues until everyone present has had a chance to share.</description_string><lang>en</lang><id>35</id></row><row sequence_index="34"><key_string>BK</key_string><name_string>Book Study</name_string><description_string>Approved N.A. Books</description_string><lang>en</lang><id>36</id><world_id>LIT</world_id></row><row sequence_index="35"><key_string>NS</key_string><name_string>No Smoking</name_string><description_string>Smoking is not allowed at this meeting.</description_string><lang>en</lang><id>37</id><world_id>NS</world_id></row><row sequence_index="36"><key_string>Ag</key_string><name_string>Agnostic</name_string><description_string>Intended for people with varying degrees of Faith.</description_string><lang>en</lang><id>38</id></row><row sequence_index="37"><key_string>FD</key_string><name_string>Five and Dime</name_string><description_string>Discussion of the Fifth Step and the Tenth Step</description_string><lang>en</lang><id>39</id></row><row sequence_index="38"><key_string>AB</key_string><name_string>Ask-It-Basket</name_string><description_string>A topic is chosen from suggestions placed into a basket.</description_string><lang>en</lang><id>40</id><world_id>QA</world_id></row><row sequence_index="39"><key_string>ME</key_string><name_string>Meditation</name_string><description_string>This meeting encourages its participants to engage in quiet meditation.</description_string><lang>en</lang><id>41</id><world_id>MED</world_id></row><row sequence_index="40"><key_string>RA</key_string><name_string>Restricted Attendance</name_string><description_string>This facility places restrictions on attendees.</description_string><lang>en</lang><id>42</id><world_id>RA</world_id></row><row sequence_index="41"><key_string>QA</key_string><name_string>Question and Answer</name_string><description_string>Attendees may ask questions and expect answers from Group members.</description_string><lang>en</lang><id>43</id><world_id>QA</world_id></row><row sequence_index="42"><key_string>CW</key_string><name_string>Children Welcome</name_string><description_string>Children are welcome at this meeting.</description_string><lang>en</lang><id>44</id><world_id>CW</world_id></row><row sequence_index="43"><key_string>CP</key_string><name_string>Concepts</name_string><description_string>This meeting is focused on discussion of the twelve concepts of NA.</description_string><lang>en</lang><id>45</id><world_id>CPT</world_id></row><row sequence_index="44"><key_string>FIN</key_string><name_string>Finnish</name_string><description_string>Finnish speaking meeting</description_string><lang>en</lang><id>46</id><world_id>LANG</world_id></row><row sequence_index="45"><key_string>ENG</key_string><name_string>English speaking</name_string><description_string>This Meeting can be attended by speakers of English.</description_string><lang>en</lang><id>47</id><world_id>LANG</world_id></row><row sequence_index="46"><key_string>PER</key_string><name_string>Persian</name_string><description_string>Persian speaking meeting</description_string><lang>en</lang><id>48</id><world_id>LANG</world_id></row><row sequence_index="47"><key_string>L/R</key_string><name_string>Lithuanian/Russian</name_string><description_string>Lithuanian/Russian Speaking Meeting</description_string><lang>en</lang><id>49</id><world_id>LANG</world_id></row><row sequence_index="48"><key_string>LC</key_string><name_string>Living Clean</name_string><description_string>This is a discussion of the NA book Living Clean -The Journey Continues.</description_string><lang>en</lang><id>51</id><world_id>LC</world_id></row><row sequence_index="49"><key_string>GP</key_string><name_string>Guiding Principles</name_string><description_string>This is a discussion of the NA book Guiding Principles - The Spirit of Our Traditions.</description_string><lang>en</lang><id>52</id><world_id>GP</world_id></row><row sequence_index="50"><key_string>VM</key_string><name_string>Virtual Meeting</name_string><description_string>Meets Virtually</description_string><lang>en</lang><id>54</id><world_id>VM</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="51"><key_string>TC</key_string><name_string>Temporarily Closed Facility</name_string><description_string>Facility is Temporarily Closed</description_string><lang>en</lang><id>55</id><world_id>TC</world_id></row><row sequence_index="52"><key_string>HY</key_string><name_string>Hybrid Meeting</name_string><description_string>Meets Virtually and In-person</description_string><lang>en</lang><id>56</id><world_id>HYBR</world_id></row></formats>';
        Brain\Monkey\setUp();

        Functions\when('\unserialize')->returnArg();
        $this->wbw_dbg = new WBW_Debug();
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();
        unset($this->wbw_dbg);
    }

    /**
     * @covers wbw\BMLT\Integration::testServerAndAuth
     */
    public function test_can_call_testServerAndAuth_with_success(): void
    {
        // testServerAndAuth($username, $password, $server)

        Functions\when('wp_safe_remote_post')->returnArg();
        Functions\when('wp_remote_retrieve_response_code')->justReturn('200');
        Functions\when('wp_remote_retrieve_body')->justReturn('<html></html>');
        Functions\when('http_build_query')->justReturn(1);

        $integration = new Integration();
        $response = $integration->testServerAndAuth("user", "pass", "server");
        $this->assertTrue($response);
    }

    /**
     * @covers wbw\BMLT\Integration::testServerAndAuth
     */
    public function test_cant_call_testServerAndAuth_with_invalid_server(): void
    {
        // testServerAndAuth($username, $password, $server)

        Functions\when('wp_safe_remote_post')->returnArg();
        Functions\when('wp_remote_retrieve_response_code')->justReturn('403');
        Functions\when('wp_remote_retrieve_body')->justReturn('<html></html>');
        Functions\when('http_build_query')->justReturn(1);

        $integration = new Integration();
        $response = $integration->testServerAndAuth("user", "pass", "server");
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\BMLT\Integration::testServerAndAuth
     */
    public function test_cant_call_testServerAndAuth_with_invalid_login(): void
    {
        // testServerAndAuth($username, $password, $server)

        Functions\when('wp_safe_remote_post')->returnArg();
        Functions\when('wp_remote_retrieve_response_code')->justReturn('200');
        Functions\when('wp_remote_retrieve_body')->justReturn('</head><body class="admin_body"><h2 class="c_comdef_not_auth_3">There was a problem with the user name or password that you entered.</h2><div class="c_comdef_admin_login_form_container_div"><noscript>');
        Functions\when('http_build_query')->justReturn(1);

        $integration = new Integration();
        $response = $integration->testServerAndAuth("user", "pass", "server");
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\BMLT\Integration::getMeetingFormats
     */
    public function test_can_call_getMeetingFormats(): void
    {
        //     public function getMeetingFormats()

        // Functions\when('wp_remote_retrieve_body')->justReturn('[{"key_string": "B","name_string": "Beginners","description_string": "This meeting is focused on the needs of new members of NA.","lang": "en","id": "1","world_id": "BEG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"},{"key_string": "BL","name_string": "Bi-Lingual","description_string": "This meeting is conducted in both English and another language.","lang": "en","id": "2","world_id": "LANG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"}]');
        Functions\when('wp_remote_retrieve_body')->justReturn($this->formats);

        Functions\when('wp_safe_remote_post')->returnArg();
        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("failure");
        $WBW_WP_Options->shouldReceive('secrets_decrypt')->andReturn('true');

        $integration = new Integration(null,$WBW_WP_Options);

        $response = $integration->getMeetingFormats();
        $this->assertIsArray($response);
    }

    /**
     * @covers wbw\BMLT\Integration::getMeetingFormats
     */
    public function test_cant_call_getMeetingFormats_with_invalid_bmlt_details(): void
    {
        //     public function getMeetingFormats()

        Functions\when('wp_remote_retrieve_body')->justReturn('[{"key_string": "B","name_string": "Beginners","description_string": "This meeting is focused on the needs of new members of NA.","lang": "en","id": "1","world_id": "BEG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"},{"key_string": "BL","name_string": "Bi-Lingual","description_string": "This meeting is conducted in both English and another language.","lang": "en","id": "2","world_id": "LANG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"}]');

        Functions\when('wp_safe_remote_post')->justReturn(new \WP_Error(1));

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("failure");
        $WBW_WP_Options->shouldReceive('secrets_decrypt')->andReturn('true');

        $integration = new Integration(null, $WBW_WP_Options);

        $response = $integration->getMeetingFormats();
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\BMLT\Integration::getMeetingStates
     */
    public function test_can_call_getMeetingStates_with_states_defined(): void
    {
        //         public function getMeetingStates()


        Functions\when('wp_remote_retrieve_body')->justReturn('[{"changesPerMeeting": "5","meeting_states_and_provinces": "MA,ME,NH,RI,VT"}]');

        Functions\when('wp_safe_remote_post')->returnArg();

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("failure");

        $integration = new Integration(null,$WBW_WP_Options);

        $response = $integration->getMeetingStates();
        $this->assertIsArray($response);
        $this->assertEquals(array("MA", "ME", "NH", "RI", "VT"), $response);
    }
    /**
     * @covers wbw\BMLT\Integration::getMeetingStates
     */
    public function test_can_call_getMeetingStates_with_no_states_defined(): void
    {
        //         public function getMeetingStates()


        Functions\when('wp_remote_retrieve_body')->justReturn('[{"changesPerMeeting": "5","meeting_states_and_provinces": ""}]');

        Functions\when('wp_safe_remote_post')->returnArg();

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("failure");

        $integration = new Integration(null, $WBW_WP_Options);

        $response = $integration->getMeetingStates();
        $this->assertFalse($response);
    }

    /**
     * @covers wbw\BMLT\Integration::getMeetingStates
     */
    public function test_cant_call_getMeetingStates_with_invalid_bmlt_details(): void
    {
        //         public function getMeetingStates()

        Functions\when('wp_remote_retrieve_body')->justReturn('[{"key_string": "B","name_string": "Beginners","description_string": "This meeting is focused on the needs of new members of NA.","lang": "en","id": "1","world_id": "BEG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"},{"key_string": "BL","name_string": "Bi-Lingual","description_string": "This meeting is conducted in both English and another language.","lang": "en","id": "2","world_id": "LANG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"}]');

        Functions\when('wp_safe_remote_post')->justReturn(new \WP_Error(1));

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("failure");

        $integration = new Integration(null,$WBW_WP_Options);

        $response = $integration->getMeetingStates();
        $this->assertInstanceOf(WP_Error::class, $response);
    }


    /**
     * @covers wbw\BMLT\Integration::getMeetingCounties
     */
    public function test_can_call_getMeetingCounties_with_counties_defined(): void
    {
        //         public function getMeetingCounties()


        Functions\when('wp_remote_retrieve_body')->justReturn('[{"changesPerMeeting": "5","meeting_states_and_provinces": "MA,ME,NH,RI,VT","meeting_counties_and_sub_provinces": "Androscoggin,Aroostook,Barnstable,Belknap"}]');

        Functions\when('wp_safe_remote_post')->returnArg();

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("failure");

        $integration = new Integration(null,$WBW_WP_Options);

        $response = $integration->getMeetingCounties();
        $this->assertIsArray($response);
        $this->assertEquals("Androscoggin", $response[0]);
    }
    /**
     * @covers wbw\BMLT\Integration::getMeetingCounties
     */
    public function test_can_call_getMeetingCounties_with_no_counties_defined(): void
    {
        //         public function getMeetingCounties()


        Functions\when('wp_remote_retrieve_body')->justReturn('[{"changesPerMeeting": "5","meeting_states_and_provinces": "MA,ME,NH,RI,VT","meeting_counties_and_sub_provinces": ""}]');

        Functions\when('wp_safe_remote_post')->returnArg();

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("failure");

        $integration = new Integration(null,$WBW_WP_Options);

        $response = $integration->getMeetingCounties();
        $this->assertFalse($response);
    }

    /**
     * @covers wbw\BMLT\Integration::getMeetingCounties
     */
    public function test_cant_call_getMeetingCounties_with_invalid_bmlt_details(): void
    {
        //         public function getMeetingCounties()

        Functions\when('wp_remote_retrieve_body')->justReturn('[{}]');

        Functions\when('wp_safe_remote_post')->justReturn(new \WP_Error(1));

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("failure");

        $integration = new Integration(null,$WBW_WP_Options);

        $response = $integration->getMeetingCounties();
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\BMLT\Integration::postAuthenticatedRootServerRequest
     */
    public function test_can_call_postAuthenticatedRootServerRequest_with_valid_auth(): void
    {
        //         public function postAuthenticatedRootServerRequest()

        Functions\when('\wp_remote_retrieve_body')->justReturn('[{}]');

        Functions\when('\wp_safe_remote_post')->returnArg();
        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("failure");
        $WBW_WP_Options->shouldReceive('secrets_decrypt')->andReturn('true');

        $integration = new Integration(null,$WBW_WP_Options);

        $response = $integration->postAuthenticatedRootServerRequest('test', array('args'=>'args'));
        $this->assertIsString($response);
    }

    /**
     * @covers wbw\BMLT\Integration::postAuthenticatedRootServerRequest
     */
    public function test_cant_call_postAuthenticatedRootServerRequest_with_valid_auth_no_args(): void
    {
        //         public function postAuthenticatedRootServerRequest()

        Functions\when('\wp_remote_retrieve_body')->justReturn('[{}]');

        Functions\when('\wp_safe_remote_post')->returnArg();
        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("failure");
        $WBW_WP_Options->shouldReceive('secrets_decrypt')->andReturn('true');

        $integration = new Integration(null,$WBW_WP_Options);

        $response = $integration->postAuthenticatedRootServerRequest('test', null);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\BMLT\Integration::postAuthenticatedRootServerRequest
     */
    public function test_cant_call_postAuthenticatedRootServerRequest_with_invalid_bmlt_details(): void
    {
        //             public function postAuthenticatedRootServerRequest($url, $postargs)

        Functions\when('wp_remote_retrieve_body')->justReturn('[{}]');
        Functions\when('wp_safe_remote_post')->justReturn(new \WP_Error(1));
        Functions\when('wp_remote_retrieve_cookies')->returnArg();

        $secretsstub = \Mockery::mock('WP_Options');
        /** @var Mockery::mock $secretsstub test */
        $secretsstub->shouldReceive('secrets_decrypt')->andReturn('true');
        $secretsstub->shouldReceive('wbw_get_option')->andReturn("failure");

        Functions\when('\is_wp_error')->justReturn(false);

        $integration = new Integration(null, $secretsstub);

        $response = $integration->postAuthenticatedRootServerRequest('test', array("arg1" => "args1"));
        $this->assertInstanceOf(WP_Error::class, $response);
    }

        /**
     * @covers wbw\BMLT\Integration::postAuthenticatedRootServerRequestSemantic
     */
    public function test_can_call_postAuthenticatedRootServerRequestSemantic_with_valid_auth(): void
    {
        //         public function postAuthenticatedRootServerRequestSemantic()

        Functions\when('\wp_remote_retrieve_body')->justReturn('[{}]');

        Functions\when('\wp_safe_remote_post')->returnArg();
        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("failure");
        $WBW_WP_Options->shouldReceive('secrets_decrypt')->andReturn('true');

        $integration = new Integration(null,$WBW_WP_Options);

        $response = $integration->postAuthenticatedRootServerRequestSemantic('test', array('args'=>'args'));
        $this->assertIsString($response);
    }

    /**
     * @covers wbw\BMLT\Integration::postAuthenticatedRootServerRequestSemantic
     */
    public function test_cant_call_postAuthenticatedRootServerRequestSemantic_with_valid_auth_no_args(): void
    {
        //         public function postAuthenticatedRootServerRequestSemantic()

        Functions\when('\wp_remote_retrieve_body')->justReturn('[{}]');

        Functions\when('\wp_safe_remote_post')->returnArg();
        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("failure");
        $WBW_WP_Options->shouldReceive('secrets_decrypt')->andReturn('true');

        $integration = new Integration(null,$WBW_WP_Options);

        $response = $integration->postAuthenticatedRootServerRequestSemantic('test', null);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\BMLT\Integration::postAuthenticatedRootServerRequestSemantic
     */
    public function test_cant_call_postAuthenticatedRootServerRequestSemantic_with_invalid_bmlt_details(): void
    {
        //             public function postAuthenticatedRootServerRequestSemantic($url, $postargs)

        // last call triggers the error
        Functions\when('wp_safe_remote_post')->justReturn(new \WP_Error(1));
        Functions\when('wp_remote_retrieve_cookies')->returnArg();
        Functions\when('wp_remote_retrieve_body')->justReturn('[{}]');


        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("failure");
        $WBW_WP_Options->shouldReceive('secrets_decrypt')->andReturn('true');


        Functions\when('\is_wp_error')->justReturn(false);

        $integration = new Integration(null, $WBW_WP_Options);

        $response = $integration->postAuthenticatedRootServerRequestSemantic('test', array("arg1" => "args1"));
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\BMLT\Integration::geolocateAddress
     * @covers wbw\BMLT\Integration::getGmapsKey
     * @covers wbw\BMLT\Integration::AuthenticateRootServer
     * @covers wbw\BMLT\Integration::post
     * @covers wbw\BMLT\Integration::get
     */
    public function test_can_call_geolocateAddress_with_valid_address(): void
    {

        Functions\when('wp_safe_remote_get')->returnArg();
        Functions\when('wp_safe_remote_post')->returnArg();
        Functions\when('wp_remote_retrieve_cookies')->returnArg();
        Functions\when('wp_remote_retrieve_body')->justReturn('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> <head> <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" /> <meta http-equiv="content-type" content="text/html; charset=utf-8" /> <meta http-equiv="Content-Script-Type" content="text/javascript" /> <meta http-equiv="Content-Style-Type" content="text/css" /> <link rel="stylesheet" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/styles.css?v=1650950537" /> <link rel="icon" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/images/shortcut.png" /> <link rel="preconnect" href="https://fonts.gstatic.com"> <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;700&display=swap" rel="stylesheet"> <title>Basic Meeting List Toolbox Administration Console</title> </head> <body class="admin_body"> <div class="bmlt_admin_logout_bar"><h4><a href="/bmlt-root-server-master/main_server/index.php?admin_action=logout">Sign Out (Server Administrator)</a></h4><div class="server_version_display_div"> 2.16.5 </div></div><div id="google_maps_api_error_div" class="bmlt_admin_google_api_key_error_bar item_hidden"><h4><a id="google_maps_api_error_a" href="https://bmlt.app/google-api-key/" target="_blank"></a></h4></div><div class="admin_page_wrapper"><div id="bmlt_admin_main_console" class="bmlt_admin_main_console_wrapper_div"> <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=googlemapstestkey&libraries=geometry"></script><script type="text/javascript">var my_localized_strings = {"default_meeting_published":true,"week_starts_on":0,"name":"English","enum":"en","comdef_map_radius_ranges":[0.0625,0.125,0.1875,0.25,0.4375,0.5,0.5625,0.75,0.8125,1,1.25,1.5,1.75,2,2.25,2.5,2.75,3,3.25,3.5,3.75,4,4.25,4.5,4.75,5,5.5,6,6.5,7,7.5,8,8.5,9,9.5,10,11,12,13,14,15,17.5,20,22.5,25,27.5,30,35,40,45,50,60,70,80,90,100,150,200],"include_service_body_email_in_semantic":false,"auto_geocoding_enabled":true,"zip_auto_geocoding_enabled":false,"county_auto_geocoding_enabled":false,"sort_formats":true,"meeting_counties_and_sub_provinces":[],"meeting_states_and_provinces":[],"google_api_key":"googlemapstestkey","dbPrefix":"na","region_bias":"au","default_duration_time":"1:30:00","default_minute_interval":5,"search_spec_map_center":{"longitude":-118.563659,"latitude":34.235918,"zoom":6},"change_type_strings":{"__THE_MEETING_WAS_CHANGED__":"The meeting was changed.","__THE_MEETING_WAS_CREATED__":"The meeting was created.","__THE_MEETING_WAS_DELETED__":"The meeting was deleted.","__THE_MEETING_WAS_ROLLED_BACK__":"The meeting was rolled back to a previous version.","__THE_FORMAT_WAS_CHANGED__":"The format was changed.","__THE_FORMAT_WAS_CREATED__":"The format was created.","__THE_FORMAT_WAS_DELETED__":"The format was deleted.","__THE_FORMAT_WAS_ROLLED_BACK__":"The format was rolled back to a previous version.","__THE_SERVICE_BODY_WAS_CHANGED__":"The service body was changed.","__THE_SERVICE_BODY_WAS_CREATED__":"The service body was created.","__THE_SERVICE_BODY_WAS_DELETED__":"The service body was deleted.","__THE_SERVICE_BODY_WAS_ROLLED_BACK__":"The service body was rolled back to a previous version.","__THE_USER_WAS_CHANGED__":"The user was changed.","__THE_USER_WAS_CREATED__":"The user was created.","__THE_USER_WAS_DELETED__":"The user was deleted.","__THE_USER_WAS_ROLLED_BACK__":"The user was rolled back to a previous version.","__BY__":"by","__FOR__":"for"},"detailed_change_strings":{"was_changed_from":"was changed from","to":"to","was_changed":"was changed","was_added_as":"was added as","was_deleted":"was deleted","was_published":"The meeting was published","was_unpublished":"The meeting was unpublished","formats_prompt":"The meeting format","duration_time":"The meeting duration","start_time":"The meeting start time","longitude":"The meeting longitude","latitude":"The meeting latitude","sb_prompt":"The meeting changed its Service Body from",');

        $json = '{ "results" : [ { "address_components" : [ { "long_name" : "Sydney", "short_name" : "Sydney", "types" : [ "colloquial_area", "locality", "political" ] }, { "long_name" : "New South Wales", "short_name" : "NSW", "types" : [ "administrative_area_level_1", "political" ] }, { "long_name" : "Australia", "short_name" : "AU", "types" : [ "country", "political" ] } ], "formatted_address" : "Sydney NSW, Australia", "geometry" : { "bounds" : { "northeast" : { "lat" : -33.5781409, "lng" : 151.3430209 }, "southwest" : { "lat" : -34.118347, "lng" : 150.5209286 } }, "location" : { "lat" : -33.8688197, "lng" : 151.2092955 }, "location_type" : "APPROXIMATE", "viewport" : { "northeast" : { "lat" : -33.5781409, "lng" : 151.3430209 }, "southwest" : { "lat" : -34.118347, "lng" : 150.5209286 } } }, "partial_match" : true, "place_id" : "ChIJP3Sa8ziYEmsRUKgyFmh9AQM", "types" : [ "colloquial_area", "locality", "political" ] } ], "status" : "OK" }';

        Functions\when('curl_exec')->justReturn($json);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=sydney%2C+australia&key=googlemapstestkey";

        Functions\expect('curl_init')->once()->with($url);
        // Functions\expect('curl_init')->once();
        Functions\when('curl_setopt')->returnArg();
        Functions\when('curl_close')->returnArg();
    
        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("failure");

        $integration = new Integration(true,$WBW_WP_Options);
        $response = $integration->geolocateAddress('sydney, australia');
        
        $this->wbw_dbg->debug_log("*** GEO RESPONSE");
        $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($response));

        $this->assertIsNumeric($response['latitude']);
        $this->assertIsNumeric($response['longitude']);

    }

        /**
     * @covers wbw\BMLT\Integration::geolocateAddress
     * @covers wbw\BMLT\Integration::getGmapsKey
     * @covers wbw\BMLT\Integration::AuthenticateRootServer
     * @covers wbw\BMLT\Integration::post
     * @covers wbw\BMLT\Integration::get
     */
    public function test_cant_call_geolocateAddress_with_invalid_address(): void
    {


        Functions\when('wp_safe_remote_get')->returnArg();
        Functions\when('wp_safe_remote_post')->returnArg();
        Functions\when('wp_remote_retrieve_cookies')->returnArg();
        Functions\when('wp_remote_retrieve_body')->justReturn('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> <head> <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" /> <meta http-equiv="content-type" content="text/html; charset=utf-8" /> <meta http-equiv="Content-Script-Type" content="text/javascript" /> <meta http-equiv="Content-Style-Type" content="text/css" /> <link rel="stylesheet" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/styles.css?v=1650950537" /> <link rel="icon" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/images/shortcut.png" /> <link rel="preconnect" href="https://fonts.gstatic.com"> <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;700&display=swap" rel="stylesheet"> <title>Basic Meeting List Toolbox Administration Console</title> </head> <body class="admin_body"> <div class="bmlt_admin_logout_bar"><h4><a href="/bmlt-root-server-master/main_server/index.php?admin_action=logout">Sign Out (Server Administrator)</a></h4><div class="server_version_display_div"> 2.16.5 </div></div><div id="google_maps_api_error_div" class="bmlt_admin_google_api_key_error_bar item_hidden"><h4><a id="google_maps_api_error_a" href="https://bmlt.app/google-api-key/" target="_blank"></a></h4></div><div class="admin_page_wrapper"><div id="bmlt_admin_main_console" class="bmlt_admin_main_console_wrapper_div"> <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=googlemapstestkey&libraries=geometry"></script><script type="text/javascript">var my_localized_strings = {"default_meeting_published":true,"week_starts_on":0,"name":"English","enum":"en","comdef_map_radius_ranges":[0.0625,0.125,0.1875,0.25,0.4375,0.5,0.5625,0.75,0.8125,1,1.25,1.5,1.75,2,2.25,2.5,2.75,3,3.25,3.5,3.75,4,4.25,4.5,4.75,5,5.5,6,6.5,7,7.5,8,8.5,9,9.5,10,11,12,13,14,15,17.5,20,22.5,25,27.5,30,35,40,45,50,60,70,80,90,100,150,200],"include_service_body_email_in_semantic":false,"auto_geocoding_enabled":true,"zip_auto_geocoding_enabled":false,"county_auto_geocoding_enabled":false,"sort_formats":true,"meeting_counties_and_sub_provinces":[],"meeting_states_and_provinces":[],"google_api_key":"googlemapstestkey","dbPrefix":"na","region_bias":"au","default_duration_time":"1:30:00","default_minute_interval":5,"search_spec_map_center":{"longitude":-118.563659,"latitude":34.235918,"zoom":6},"change_type_strings":{"__THE_MEETING_WAS_CHANGED__":"The meeting was changed.","__THE_MEETING_WAS_CREATED__":"The meeting was created.","__THE_MEETING_WAS_DELETED__":"The meeting was deleted.","__THE_MEETING_WAS_ROLLED_BACK__":"The meeting was rolled back to a previous version.","__THE_FORMAT_WAS_CHANGED__":"The format was changed.","__THE_FORMAT_WAS_CREATED__":"The format was created.","__THE_FORMAT_WAS_DELETED__":"The format was deleted.","__THE_FORMAT_WAS_ROLLED_BACK__":"The format was rolled back to a previous version.","__THE_SERVICE_BODY_WAS_CHANGED__":"The service body was changed.","__THE_SERVICE_BODY_WAS_CREATED__":"The service body was created.","__THE_SERVICE_BODY_WAS_DELETED__":"The service body was deleted.","__THE_SERVICE_BODY_WAS_ROLLED_BACK__":"The service body was rolled back to a previous version.","__THE_USER_WAS_CHANGED__":"The user was changed.","__THE_USER_WAS_CREATED__":"The user was created.","__THE_USER_WAS_DELETED__":"The user was deleted.","__THE_USER_WAS_ROLLED_BACK__":"The user was rolled back to a previous version.","__BY__":"by","__FOR__":"for"},"detailed_change_strings":{"was_changed_from":"was changed from","to":"to","was_changed":"was changed","was_added_as":"was added as","was_deleted":"was deleted","was_published":"The meeting was published","was_unpublished":"The meeting was unpublished","formats_prompt":"The meeting format","duration_time":"The meeting duration","start_time":"The meeting start time","longitude":"The meeting longitude","latitude":"The meeting latitude","sb_prompt":"The meeting changed its Service Body from",');

        $json = ' { "results" : [], "status" : "ZERO_RESULTS" }';
       
        Functions\when('curl_exec')->justReturn($json);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=junk%2C+junk&key=googlemapstestkey";

        Functions\expect('curl_init')->once()->with($url);
        // Functions\expect('curl_init')->once();
        Functions\when('curl_setopt')->returnArg();
        Functions\when('curl_close')->returnArg();
    
        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("failure");

        $integration = new Integration(true,$WBW_WP_Options);
        $response = $integration->geolocateAddress('junk, junk');
        
        $this->wbw_dbg->debug_log("*** GEO RESPONSE");
        $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($response));
        $this->assertInstanceOf(WP_Error::class, $response);

    }

       /**
     * @covers wbw\BMLT\Integration::geolocateAddress
     * @covers wbw\BMLT\Integration::getGmapsKey
     * @covers wbw\BMLT\Integration::AuthenticateRootServer
     * @covers wbw\BMLT\Integration::post
     * @covers wbw\BMLT\Integration::get
     */
    public function test_error_when_gmaps_call_returns_trash(): void
    {


        Functions\when('wp_safe_remote_get')->returnArg();
        Functions\when('wp_safe_remote_post')->returnArg();
        Functions\when('wp_remote_retrieve_cookies')->returnArg();
        Functions\when('wp_remote_retrieve_body')->justReturn('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> <head> <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" /> <meta http-equiv="content-type" content="text/html; charset=utf-8" /> <meta http-equiv="Content-Script-Type" content="text/javascript" /> <meta http-equiv="Content-Style-Type" content="text/css" /> <link rel="stylesheet" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/styles.css?v=1650950537" /> <link rel="icon" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/images/shortcut.png" /> <link rel="preconnect" href="https://fonts.gstatic.com"> <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;700&display=swap" rel="stylesheet"> <title>Basic Meeting List Toolbox Administration Console</title> </head> <body class="admin_body"> <div class="bmlt_admin_logout_bar"><h4><a href="/bmlt-root-server-master/main_server/index.php?admin_action=logout">Sign Out (Server Administrator)</a></h4><div class="server_version_display_div"> 2.16.5 </div></div><div id="google_maps_api_error_div" class="bmlt_admin_google_api_key_error_bar item_hidden"><h4><a id="google_maps_api_error_a" href="https://bmlt.app/google-api-key/" target="_blank"></a></h4></div><div class="admin_page_wrapper"><div id="bmlt_admin_main_console" class="bmlt_admin_main_console_wrapper_div"> <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=googlemapstestkey&libraries=geometry"></script><script type="text/javascript">var my_localized_strings = {"default_meeting_published":true,"week_starts_on":0,"name":"English","enum":"en","comdef_map_radius_ranges":[0.0625,0.125,0.1875,0.25,0.4375,0.5,0.5625,0.75,0.8125,1,1.25,1.5,1.75,2,2.25,2.5,2.75,3,3.25,3.5,3.75,4,4.25,4.5,4.75,5,5.5,6,6.5,7,7.5,8,8.5,9,9.5,10,11,12,13,14,15,17.5,20,22.5,25,27.5,30,35,40,45,50,60,70,80,90,100,150,200],"include_service_body_email_in_semantic":false,"auto_geocoding_enabled":true,"zip_auto_geocoding_enabled":false,"county_auto_geocoding_enabled":false,"sort_formats":true,"meeting_counties_and_sub_provinces":[],"meeting_states_and_provinces":[],"google_api_key":"googlemapstestkey","dbPrefix":"na","region_bias":"au","default_duration_time":"1:30:00","default_minute_interval":5,"search_spec_map_center":{"longitude":-118.563659,"latitude":34.235918,"zoom":6},"change_type_strings":{"__THE_MEETING_WAS_CHANGED__":"The meeting was changed.","__THE_MEETING_WAS_CREATED__":"The meeting was created.","__THE_MEETING_WAS_DELETED__":"The meeting was deleted.","__THE_MEETING_WAS_ROLLED_BACK__":"The meeting was rolled back to a previous version.","__THE_FORMAT_WAS_CHANGED__":"The format was changed.","__THE_FORMAT_WAS_CREATED__":"The format was created.","__THE_FORMAT_WAS_DELETED__":"The format was deleted.","__THE_FORMAT_WAS_ROLLED_BACK__":"The format was rolled back to a previous version.","__THE_SERVICE_BODY_WAS_CHANGED__":"The service body was changed.","__THE_SERVICE_BODY_WAS_CREATED__":"The service body was created.","__THE_SERVICE_BODY_WAS_DELETED__":"The service body was deleted.","__THE_SERVICE_BODY_WAS_ROLLED_BACK__":"The service body was rolled back to a previous version.","__THE_USER_WAS_CHANGED__":"The user was changed.","__THE_USER_WAS_CREATED__":"The user was created.","__THE_USER_WAS_DELETED__":"The user was deleted.","__THE_USER_WAS_ROLLED_BACK__":"The user was rolled back to a previous version.","__BY__":"by","__FOR__":"for"},"detailed_change_strings":{"was_changed_from":"was changed from","to":"to","was_changed":"was changed","was_added_as":"was added as","was_deleted":"was deleted","was_published":"The meeting was published","was_unpublished":"The meeting was unpublished","formats_prompt":"The meeting format","duration_time":"The meeting duration","start_time":"The meeting start time","longitude":"The meeting longitude","latitude":"The meeting latitude","sb_prompt":"The meeting changed its Service Body from",');

        $json = ' { "junk" : "junk" }';
       
        Functions\when('curl_exec')->justReturn($json);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=junk%2C+junk&key=googlemapstestkey";

        Functions\expect('curl_init')->once()->with($url);
        // Functions\expect('curl_init')->once();
        Functions\when('curl_setopt')->returnArg();
        Functions\when('curl_close')->returnArg();
    
        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("failure");

        $integration = new Integration(true,$WBW_WP_Options);
        $response = $integration->geolocateAddress('junk, junk');
        
        $this->wbw_dbg->debug_log("*** GEO RESPONSE");
        $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($response));
        $this->assertInstanceOf(WP_Error::class, $response);

    }

}
