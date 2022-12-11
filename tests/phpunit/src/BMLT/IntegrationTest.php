<?php
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


declare(strict_types=1);

use bmltwf\BMLT\Integration;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};

require_once('config_phpunit.php');

/**
 * @covers bmltwf\BMLT\Integration
 * @uses bmltwf\BMLTWF_Debug
 */
final class IntegrationTest extends TestCase
{
    use \bmltwf\BMLTWF_Debug;

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

        Brain\Monkey\setUp();

        Functions\when('\wp_remote_get')->returnArg();
        Functions\when('\wp_remote_post')->returnArg();
        Functions\when('\wp_remote_request')->returnArg();
        Functions\when('\wp_remote_retrieve_response_message')->returnArg();
        
        Functions\when('\unserialize')->returnArg();
        Functions\when('\get_option')->alias(function($value) {
            if($value === 'bmltwf_bmlt_password')
            {
                return(json_decode('{"config":{"size":"MzI=","salt":"\/5ObzNuYZ\/Y5aoYTsr0sZw==","limit_ops":"OA==","limit_mem":"NTM2ODcwOTEy","alg":"Mg==","nonce":"VukDVzDkAaex\/jfB"},"encrypted":"fertj+qRqQrs9tC+Cc32GrXGImHMfiLyAW7sV6Xojw=="}',true));
            }
            else
            {
                return("true");
            }});

            $this->formatsxml = '<?xml version="1.0" encoding="UTF-8"?><formats xmlns="http://localhost" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://localhost:8000/main_server/client_interface/xsd/GetFormats.php"><row sequence_index="0"><key_string>B</key_string><name_string>Beginners</name_string><description_string>This meeting is focused on the needs of new members of NA.</description_string><lang>en</lang><id>1</id><world_id>BEG</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="1"><key_string>BL</key_string><name_string>Bi-Lingual</name_string><description_string>This Meeting can be attended by speakers of English and another language.</description_string><lang>en</lang><id>2</id><format_used_in_database>1</format_used_in_database></row><row sequence_index="2"><key_string>BT</key_string><name_string>Basic Text</name_string><description_string>This meeting is focused on discussion of the Basic Text of Narcotics Anonymous.</description_string><lang>en</lang><id>3</id><world_id>BT</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="3"><key_string>C</key_string><name_string>Closed</name_string><description_string>This meeting is closed to non-addicts. You should attend only if you believe that you may have a problem with substance abuse.</description_string><lang>en</lang><id>4</id><world_id>CLOSED</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="4"><key_string>CH</key_string><name_string>Closed Holidays</name_string><description_string>This meeting gathers in a facility that is usually closed on holidays.</description_string><lang>en</lang><id>5</id><world_id>CH</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="5"><key_string>CL</key_string><name_string>Candlelight</name_string><description_string>This meeting is held by candlelight.</description_string><lang>en</lang><id>6</id><world_id>CAN</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="6"><key_string>CS</key_string><name_string>Children under Supervision</name_string><description_string>Well-behaved, supervised children are welcome.</description_string><lang>en</lang><id>7</id><format_used_in_database>1</format_used_in_database></row><row sequence_index="7"><key_string>D</key_string><name_string>Discussion</name_string><description_string>This meeting invites participation by all attendees.</description_string><lang>en</lang><id>8</id><world_id>DISC</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="8"><key_string>ES</key_string><name_string>Espanol</name_string><description_string>This meeting is conducted in Spanish.</description_string><lang>en</lang><id>9</id><world_id>LANG</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="9"><key_string>GL</key_string><name_string>Gay/Lesbian/Transgender</name_string><description_string>This meeting is focused on the needs of gay, lesbian and transgender members of NA.</description_string><lang>en</lang><id>10</id><world_id>GL</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="10"><key_string>IL</key_string><name_string>Illness</name_string><description_string>This meeting is focused on the needs of NA members with chronic illness.</description_string><lang>en</lang><id>11</id><format_used_in_database>1</format_used_in_database></row><row sequence_index="11"><key_string>IP</key_string><name_string>Informational Pamphlet</name_string><description_string>This meeting is focused on discussion of one or more Informational Pamphlets.</description_string><lang>en</lang><id>12</id><world_id>IP</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="12"><key_string>IW</key_string><name_string>It Works -How and Why</name_string><description_string>This meeting is focused on discussion of the It Works -How and Why text.</description_string><lang>en</lang><id>13</id><world_id>IW</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="13"><key_string>JT</key_string><name_string>Just for Today</name_string><description_string>This meeting is focused on discussion of the Just For Today text.</description_string><lang>en</lang><id>14</id><world_id>JFT</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="14"><key_string>M</key_string><name_string>Men</name_string><description_string>This meeting is focused on topics encountered by men in NA.</description_string><lang>en</lang><id>15</id><world_id>M</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="15"><key_string>NC</key_string><name_string>No Children</name_string><description_string>Please do not bring children to this meeting.</description_string><lang>en</lang><id>16</id><world_id>NC</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="16"><key_string>O</key_string><name_string>Open</name_string><description_string>This meeting is open to addicts and non-addicts alike. All are welcome.</description_string><lang>en</lang><id>17</id><world_id>OPEN</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="17"><key_string>Pi</key_string><name_string>Pitch</name_string><description_string>This meeting has a format that consists of each person who shares picking the next person.</description_string><lang>en</lang><id>18</id><format_used_in_database>1</format_used_in_database></row><row sequence_index="18"><key_string>RF</key_string><name_string>Rotating Format</name_string><description_string>This meeting has a format that changes for each meeting.</description_string><lang>en</lang><id>19</id><world_id>VAR</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="19"><key_string>Rr</key_string><name_string>Round Robin</name_string><description_string>This meeting has a fixed sharing order (usually a circle.)</description_string><lang>en</lang><id>20</id><format_used_in_database>1</format_used_in_database></row><row sequence_index="20"><key_string>SC</key_string><name_string>Surveillance Cameras</name_string><description_string>This meeting is held in a facility that has surveillance cameras.</description_string><lang>en</lang><id>21</id><format_used_in_database>1</format_used_in_database></row><row sequence_index="21"><key_string>SD</key_string><name_string>Speaker/Discussion</name_string><description_string>This meeting is lead by a speaker, then opened for participation by attendees.</description_string><lang>en</lang><id>22</id><world_id>SPK</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="22"><key_string>SG</key_string><name_string>Step Working Guide</name_string><description_string>This meeting is focused on discussion of the Step Working Guide text.</description_string><lang>en</lang><id>23</id><world_id>SWG</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="23"><key_string>SL</key_string><name_string>ASL</name_string><description_string>This meeting provides an American Sign Language (ASL) interpreter for the deaf.</description_string><lang>en</lang><id>24</id><format_used_in_database>1</format_used_in_database></row><row sequence_index="24"><key_string>So</key_string><name_string>Speaker Only</name_string><description_string>This meeting is a speaker-only meeting. Other attendees do not participate in the discussion.</description_string><lang>en</lang><id>26</id><world_id>SPK</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="25"><key_string>St</key_string><name_string>Step</name_string><description_string>This meeting is focused on discussion of the Twelve Steps of NA.</description_string><lang>en</lang><id>27</id><world_id>STEP</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="26"><key_string>Ti</key_string><name_string>Timer</name_string><description_string>This meeting has sharing time limited by a timer.</description_string><lang>en</lang><id>28</id><format_used_in_database>1</format_used_in_database></row><row sequence_index="27"><key_string>To</key_string><name_string>Topic</name_string><description_string>This meeting is based upon a topic chosen by a speaker or by group conscience.</description_string><lang>en</lang><id>29</id><world_id>TOP</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="28"><key_string>Tr</key_string><name_string>Tradition</name_string><description_string>This meeting is focused on discussion of the Twelve Traditions of NA.</description_string><lang>en</lang><id>30</id><world_id>TRAD</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="29"><key_string>TW</key_string><name_string>Traditions Workshop</name_string><description_string>This meeting engages in detailed discussion of one or more of the Twelve Traditions of N.A.</description_string><lang>en</lang><id>31</id><world_id>TRAD</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="30"><key_string>W</key_string><name_string>Women</name_string><description_string>This meeting is focused on topics encountered by women in NA.</description_string><lang>en</lang><id>32</id><world_id>W</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="31"><key_string>WC</key_string><name_string>Wheelchair</name_string><description_string>This meeting is wheelchair accessible.</description_string><lang>en</lang><id>33</id><world_id>WCHR</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="32"><key_string>YP</key_string><name_string>Young People</name_string><description_string>This meeting is focused on the needs of younger members of NA.</description_string><lang>en</lang><id>34</id><world_id>Y</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="33"><key_string>OE</key_string><name_string>Open-Ended</name_string><description_string>No fixed duration. The meeting continues until everyone present has had a chance to share.</description_string><lang>en</lang><id>35</id><format_used_in_database>1</format_used_in_database></row><row sequence_index="34"><key_string>BK</key_string><name_string>Book Study</name_string><description_string>Approved N.A. Books</description_string><lang>en</lang><id>36</id><world_id>LIT</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="35"><key_string>NS</key_string><name_string>No Smoking</name_string><description_string>Smoking is not allowed at this meeting.</description_string><lang>en</lang><id>37</id><world_id>NS</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="36"><key_string>Ag</key_string><name_string>Agnostic</name_string><description_string>Intended for people with varying degrees of Faith.</description_string><lang>en</lang><id>38</id></row><row sequence_index="37"><key_string>FD</key_string><name_string>Five and Dime</name_string><description_string>Discussion of the Fifth Step and the Tenth Step</description_string><lang>en</lang><id>39</id><format_used_in_database>1</format_used_in_database></row><row sequence_index="38"><key_string>AB</key_string><name_string>Ask-It-Basket</name_string><description_string>A topic is chosen from suggestions placed into a basket.</description_string><lang>en</lang><id>40</id><world_id>QA</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="39"><key_string>ME</key_string><name_string>Meditation</name_string><description_string>This meeting encourages its participants to engage in quiet meditation.</description_string><lang>en</lang><id>41</id><world_id>MED</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="40"><key_string>RA</key_string><name_string>Restricted Attendance</name_string><description_string>This facility places restrictions on attendees.</description_string><lang>en</lang><id>42</id><world_id>RA</world_id></row><row sequence_index="41"><key_string>QA</key_string><name_string>Question and Answer</name_string><description_string>Attendees may ask questions and expect answers from Group members.</description_string><lang>en</lang><id>43</id><world_id>QA</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="42"><key_string>CW</key_string><name_string>Children Welcome</name_string><description_string>Children are welcome at this meeting.</description_string><lang>en</lang><id>44</id><world_id>CW</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="43"><key_string>CP</key_string><name_string>Concepts</name_string><description_string>This meeting is focused on discussion of the twelve concepts of NA.</description_string><lang>en</lang><id>45</id><world_id>CPT</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="44"><key_string>FIN</key_string><name_string>Finnish</name_string><description_string>finnish speaking meeting</description_string><lang>en</lang><id>46</id><world_id>LANG</world_id></row><row sequence_index="45"><key_string>ENG</key_string><name_string>English speaking</name_string><description_string>This Meeting can be attended by speakers of English.</description_string><lang>en</lang><id>47</id><world_id>LANG</world_id></row><row sequence_index="46"><key_string>PER</key_string><name_string>Persian</name_string><description_string>Persian speeking meeting</description_string><lang>en</lang><id>48</id><world_id>LANG</world_id></row><row sequence_index="47"><key_string>L/R</key_string><name_string>Lithuanian/Russian</name_string><description_string>Lithuanian/Russian Speaking Meeting</description_string><lang>en</lang><id>49</id><world_id>LANG</world_id></row><row sequence_index="48"><key_string>WEB</key_string><name_string>Online Meeting</name_string><description_string>This is a meeting that gathers on the Internet.</description_string><lang>en</lang><id>50</id></row><row sequence_index="49"><key_string>LC</key_string><name_string>Living Clean</name_string><description_string>This is a discussion of the NA book Living Clean -The Journey Continues.</description_string><lang>en</lang><id>51</id><world_id>LC</world_id><format_used_in_database>1</format_used_in_database></row><row sequence_index="50"><key_string>ID</key_string><name_string>ID Required to Enter</name_string><description_string>This meeting is held in a facility that requires visitors to provide ID to enter.</description_string><lang>en</lang><id>52</id><format_used_in_database>1</format_used_in_database></row><row sequence_index="51"><key_string>QA</key_string><name_string>Question and Answer</name_string><description_string>Q&amp;A-style meeting where questions about the NA way of life are asked and answered.</description_string><lang>en</lang><id>53</id><world_id>QA</world_id></row><row sequence_index="52"><key_string>VM</key_string><name_string>Virtual Meeting</name_string><description_string>Meets Virtually</description_string><lang>en</lang><id>54</id><world_id>VM</world_id></row><row sequence_index="53"><key_string>TC</key_string><name_string>Temporarily Closed Facility</name_string><description_string>Facility is Temporarily Closed</description_string><lang>en</lang><id>55</id><world_id>TC</world_id></row><row sequence_index="54"><key_string>HY</key_string><name_string>Hybrid Meeting</name_string><description_string>Meets Virtually and In-person</description_string><lang>en</lang><id>56</id><world_id>HYBR</world_id></row></formats>';

            $this->formats = <<<EOD
        [
            {
                "id": 1,
                "worldId": "BEG",
                "type": "COMMON_NEEDS_OR_RESTRICTION",
                "translations": [
                { "key": "BEG", "name": "Beginners", "description": "This meeting is focused on the needs of new members of NA.", "language": "en" },
                { "key": "BEG", "name": "Principiantes", "description": "Esta reuni\u00f3n se centr\u00f3 en las necesidades de los nuevos miembros de NA.", "language": "es" },
                {
                    "key": "B",
                    "name": "\u062a\u0627\u0632\u0647 \u0648\u0627\u0631\u062f\u0627\u0646",
                    "description": "\u0627\u06cc\u0646 \u062c\u0644\u0633\u0647 \u0628\u0631 \u0631\u0648\u06cc \u0646\u06cc\u0627\u0632\u0647\u0627\u06cc \u062a\u0627\u0632\u0647 \u0648\u0627\u0631\u062f\u0627\u0646 \u062f\u0631 \u0645\u0639\u062a\u0627\u062f\u0627\u0646 \u06af\u0645\u0646\u0627\u0645 \u0645\u062a\u0645\u0631\u06a9\u0632 \u0645\u06cc\u0628\u0627\u0634\u062f",
                    "language": "fa"
                },
                { "key": "B", "name": "Nowoprzybyli", "description": "Mityng koncentruje si\u0119 na potrzebach nowyh cz\u0142onk\u00f3w NA.", "language": "pl" },
                { "key": "B", "name": "Nowoprzybyli", "description": "Mityng koncentruje si\u0119 na potrzebach nowyh cz\u0142onk\u00f3w NA.", "language": "pl" },
                { "key": "RC", "name": "Rec\u00e9m-chegados", "description": "Esta reuni\u00e3o tem foco nas necessidades de novos membros em NA.", "language": "pt" }
                ]
            },
            {
                "id": 48,
                "worldId": null,
                "type": null,
                "translations": [
                { "key": "S", "name": "Spiritual Principals", "description": "This meeting is focused on spiritual principals.", "language": "en" },
                { "key": "S", "name": "Directores Espirituales", "description": "Esta reuni\u00f3n se centr\u00f3 en los principios espirituales.", "language": "es" },
                { "key": "PER", "name": "\u0641\u0627\u0631\u0633\u06cc", "description": "\u062c\u0644\u0633\u0647 \u0628\u0647 \u0632\u0628\u0627\u0646 \u0641\u0627\u0631\u0633\u06cc", "language": "fa" },
                { "key": "PER", "name": "Perski", "description": "Mityng odbywa si\u0119 w j\u0119zyku perskim", "language": "pl" },
                { "key": "PER", "name": "Perski", "description": "Mityng odbywa si\u0119 w j\u0119zyku perskim", "language": "pl" },
                { "key": "PER", "name": "Persa", "description": "Reuni\u00e3o em l\u00edngua persa", "language": "pt" }
                ]
            },
            {
                "id": 6,
                "worldId": "CAN",
                "type": "LOCATION",
                "translations": [
                { "key": "CAN", "name": "Candlelight", "description": "This meeting is held by candlelight.", "language": "en" },
                { "key": "CAN", "name": "Luz De Una Vela", "description": "Esta reuni\u00f3n se celebra con velas.", "language": "es" },
                {
                    "key": "CL",
                    "name": "\u0634\u0645\u0639 \u0631\u0648\u0634\u0646",
                    "description": "\u0627\u06cc\u0646 \u062c\u0644\u0633\u0647 \u0628\u0647\u0645\u0631\u0627\u0647 \u0634\u0645\u0639 \u0631\u0648\u0634\u0646 \u0628\u0631\u06af\u0632\u0627\u0631 \u0645\u06cc\u06af\u0631\u062f\u062f",
                    "language": "fa"
                },
                { "key": "CL", "name": "\u015awieczka", "description": "Ten mityng odbywa si\u0119 przy blasku \u015bwiecy.", "language": "pl" },
                { "key": "CL", "name": "\u015awieczka", "description": "Ten mityng odbywa si\u0119 przy blasku \u015bwiecy.", "language": "pl" },
                { "key": "VL", "name": "Luz de velas", "description": "Esta reuni\u00e3o acontece \u00e0 luz de velas.", "language": "pt" }
                ]
            },
            {
                "id": 7,
                "worldId": "CW",
                "type": "COMMON_NEEDS_OR_RESTRICTION",
                "translations": [
                { "key": "CW", "name": "Children Welcome", "description": "Children are welcome.", "language": "en" },
                { "key": "CW", "name": "Los Ni\u00f1os Son Bienvenidos", "description": "", "language": "es" },
                {
                    "key": "CS",
                    "name": "\u06a9\u0648\u062f\u06a9\u0627\u0646 \u0628\u06cc \u0633\u0631\u067e\u0631\u0633\u062a",
                    "description": "\u062e\u0648\u0634 \u0631\u0641\u062a\u0627\u0631\u06cc",
                    "language": "fa"
                },
                { "key": "CW", "name": "Enfants bienvenus", "description": "Les enfants sont les bienvenus \u00e0 cette r\u00e9union.", "language": "fr" },
                { "key": "CS", "name": "Dzieci pod opiek\u0105", "description": "Dzieci uzale\u017cnionych mile widziane pod warunkiem odpowiedniego zachowania.", "language": "pl" },
                { "key": "CA", "name": "Crian\u00e7a sob supervis\u00e3o", "description": "Bem-comportadas, crian\u00e7as sob supervis\u00e3o s\u00e3o bem-vindas.", "language": "pt" }
                ]
            },
            {
                "id": 8,
                "worldId": "DISC",
                "type": "MEETING_FORMAT",
                "translations": [
                { "key": "D", "name": "Discussion", "description": "This meeting invites participation by all attendees.", "language": "en" },
                { "key": "D", "name": "Discusi\u00f3n", "description": "Esta reuni\u00f3n invita a la participaci\u00f3n de todos los asistentes.", "language": "es" },
                {
                    "key": "D",
                    "name": "\u0628\u062d\u062b \u0648 \u06af\u0641\u062a\u06af\u0648",
                    "description": "\u0627\u06cc\u0646 \u062c\u0644\u0633\u0647 \u0627\u0632 \u062a\u0645\u0627\u0645\u06cc \u0634\u0631\u06a9\u062a \u06a9\u0646\u0646\u062f\u06af\u0627\u0646 \u062f\u0639\u0648\u062a \u0628\u0647 \u0628\u062d\u062b \u0645\u06cc\u06a9\u0646\u062f",
                    "language": "fa"
                },
                { "key": "D", "name": "Dyskusja", "description": "Mityng dla wszystkich ch\u0119tnych.", "language": "pl" },
                { "key": "D", "name": "Dyskusja", "description": "Mityng dla wszystkich ch\u0119tnych.", "language": "pl" },
                { "key": "D", "name": "Discuss\u00e3o", "description": "Esta reuni\u00e3o convida a participa\u00e7\u00e3o de todos.", "language": "pt" }
                ]
            },
            {
                "id": 9,
                "worldId": null,
                "type": "COMMON_NEEDS_OR_RESTRICTION",
                "translations": [
                { "key": "ESP", "name": "Espanol", "description": "This meeting primary spoken in Spanish.", "language": "en" },
                { "key": "ESP", "name": "Espanol", "description": "Esta reuni\u00f3n principal que se habla en espa\u00f1ol.", "language": "es" },
                {
                    "key": "ES",
                    "name": "\u0627\u0633\u067e\u0627\u0646\u06cc\u0627\u06cc\u06cc",
                    "description": "\u0627\u06cc\u0646 \u062c\u0644\u0633\u0647 \u0628\u0647 \u0632\u0628\u0627\u0646 \u0627\u0633\u067e\u0627\u0646\u06cc\u0627\u06cc\u06cc \u0628\u0631\u06af\u0632\u0627\u0631 \u0645\u06cc\u06af\u0631\u062f\u062f",
                    "language": "fa"
                },
                { "key": "ES", "name": "Hiszpa\u0144ski", "description": "Mityng odbywa si\u0119 w j\u0119zyku hiszpa\u0144skim.", "language": "pl" },
                { "key": "ES", "name": "Hiszpa\u0144ski", "description": "Mityng odbywa si\u0119 w j\u0119zyku hiszpa\u0144skim.", "language": "pl" },
                { "key": "ES", "name": "Espanhol", "description": "Esta reuni\u00e3o acontece em Espanhol.", "language": "pt" }
                ]
            }]
        EOD;

    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();
        unset($this->bmltwf_dbg);
    }

    /**
     * @covers bmltwf\BMLT\Integration::testServerAndAuthv2
     */
    public function test_can_call_testServerAndAuthv2_with_success(): void
    {
        // testServerAndAuthv2($username, $password, $server)

        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->justReturn('<html></html>');
        Functions\when('http_build_query')->justReturn(1);

        $integration = new Integration(null, null, "2.0.0");
        $response = $integration->testServerAndAuthv2("user", "pass", "server");
        $this->assertTrue($response);
    }

    /**
     * @covers bmltwf\BMLT\Integration::testServerAndAuthv2
     */
    public function test_cant_call_testServerAndAuthv2_with_invalid_server(): void
    {
        // testServerAndAuthv2($username, $password, $server)

        Functions\when('\wp_remote_retrieve_response_code')->justReturn(403);
        Functions\when('\wp_remote_retrieve_body')->justReturn('<html></html>');
        Functions\when('http_build_query')->justReturn(1);

        $integration = new Integration(null, null, "2.0.0");
        $response = $integration->testServerAndAuthv2("user", "pass", "server");
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\BMLT\Integration::testServerAndAuthv2
     */
    public function test_cant_call_testServerAndAuthv2_with_invalid_login(): void
    {
        // testServerAndAuthv2($username, $password, $server)

        Functions\when('\wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('\wp_remote_retrieve_body')->justReturn('</head><body class="admin_body"><h2 class="c_comdef_not_auth_3">There was a problem with the user name or password that you entered.</h2><div class="c_comdef_admin_login_form_container_div"><noscript>');
        Functions\when('http_build_query')->justReturn(1);

        $integration = new Integration(null, null, "2.0.0");
        $response = $integration->testServerAndAuthv2("user", "pass", "server");
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\BMLT\Integration::getMeetingFormatsv2
     */
    public function test_can_call_getMeetingFormatsv2(): void
    {
        //     public function getMeetingFormatsv2()

        // Functions\when('wp_remote_retrieve_body')->justReturn('[{"key_string": "B","name_string": "Beginners","description_string": "This meeting is focused on the needs of new members of NA.","lang": "en","id": "1","world_id": "BEG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"},{"key_string": "BL","name_string": "Bi-Lingual","description_string": "This meeting is conducted in both English and another language.","lang": "en","id": "2","world_id": "LANG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"}]');
        Functions\when('\wp_remote_retrieve_body')->justReturn($this->formatsxml);
        Functions\when('\wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('\wp_remote_post')->justReturn(array('response' => array('code' => 200)));
        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));

        $integration = new Integration(null, "2.0.0");

        $response = $integration->getMeetingFormatsv2();
        $this->assertIsArray($response);
    }

    /**
     * @covers bmltwf\BMLT\Integration::getMeetingFormatsv2
     */
    public function test_cant_call_getMeetingFormatsv2_with_invalid_bmlt_details(): void
    {
        //     public function getMeetingFormatsv2()

        Functions\when('\wp_remote_retrieve_body')->justReturn('[{"key_string": "B","name_string": "Beginners","description_string": "This meeting is focused on the needs of new members of NA.","lang": "en","id": "1","world_id": "BEG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"},{"key_string": "BL","name_string": "Bi-Lingual","description_string": "This meeting is conducted in both English and another language.","lang": "en","id": "2","world_id": "LANG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"}]');
        Functions\when('\wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('\wp_remote_post')->justReturn(new \WP_Error(1));

        $integration = new Integration(null, "2.0.0");

        $response = $integration->getMeetingFormatsv2();
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\BMLT\Integration::getMeetingStates
     */
    public function test_can_call_getMeetingStates_with_states_defined(): void
    {
        //         public function getMeetingStates()


        Functions\when('\wp_remote_retrieve_body')->justReturn('[{"changesPerMeeting": "5","meeting_states_and_provinces": "MA,ME,NH,RI,VT"}]');
        Functions\when('\wp_remote_retrieve_response_code')->justReturn(200);

        $integration = new Integration(null, "2.0.0");

        $response = $integration->getMeetingStates();
        $this->assertIsArray($response);
        $this->assertEquals(array("MA", "ME", "NH", "RI", "VT"), $response);
    }
    /**
     * @covers bmltwf\BMLT\Integration::getMeetingStates
     */
    public function test_can_call_getMeetingStates_with_no_states_defined(): void
    {
        //         public function getMeetingStates()


        Functions\when('\wp_remote_retrieve_body')->justReturn('[{"changesPerMeeting": "5","meeting_states_and_provinces": ""}]');
        Functions\when('\wp_remote_retrieve_response_code')->justReturn(200);

        $integration = new Integration(null, "2.0.0");

        $response = $integration->getMeetingStates();
        $this->assertFalse($response);
    }

    /**
     * @covers bmltwf\BMLT\Integration::getMeetingStates
     */
    public function test_cant_call_getMeetingStates_with_invalid_bmlt_details(): void
    {
        //         public function getMeetingStates()

        Functions\when('\wp_remote_retrieve_body')->justReturn('[{"key_string": "B","name_string": "Beginners","description_string": "This meeting is focused on the needs of new members of NA.","lang": "en","id": "1","world_id": "BEG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"},{"key_string": "BL","name_string": "Bi-Lingual","description_string": "This meeting is conducted in both English and another language.","lang": "en","id": "2","world_id": "LANG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"}]');
        Functions\when('\wp_remote_retrieve_response_code')->justReturn(400);
        Functions\when('\wp_remote_get')->justReturn(new \WP_Error(1));

        $integration = new Integration(null, "2.0.0");

        $response = $integration->getMeetingStates();
        $this->assertInstanceOf(WP_Error::class, $response);
    }


    /**
     * @covers bmltwf\BMLT\Integration::getMeetingCounties
     */

    public function test_can_call_getMeetingCounties_with_counties_defined(): void
    {
        //         public function getMeetingCounties()

        Functions\when('\wp_remote_retrieve_body')->justReturn('[{"changesPerMeeting": "5","meeting_states_and_provinces": "MA,ME,NH,RI,VT","meeting_counties_and_sub_provinces": "Androscoggin,Aroostook,Barnstable,Belknap"}]');
        Functions\when('\wp_remote_retrieve_response_code')->justReturn(200);

        $integration = new Integration(null, "2.0.0");

        $response = $integration->getMeetingCounties();
        $this->assertIsArray($response);
        $this->assertEquals("Androscoggin", $response[0]);
    }

    /**
     * @covers bmltwf\BMLT\Integration::getMeetingCounties
     */
    public function test_can_call_getMeetingCounties_with_no_counties_defined(): void
    {
        //         public function getMeetingCounties()


        Functions\when('wp_remote_retrieve_body')->justReturn('[{"changesPerMeeting": "5","meeting_states_and_provinces": "MA,ME,NH,RI,VT","meeting_counties_and_sub_provinces": ""}]');
        Functions\when('\wp_remote_retrieve_response_code')->justReturn(200);

        $integration = new Integration(null, "2.0.0");

        $response = $integration->getMeetingCounties();
        $this->assertFalse($response);
    }

    /**
     * @covers bmltwf\BMLT\Integration::getMeetingCounties
     */
    public function test_cant_call_getMeetingCounties_with_invalid_bmlt_details(): void
    {
        //         public function getMeetingCounties()

        Functions\when('wp_remote_retrieve_body')->justReturn('[{}]');
        Functions\when('\wp_remote_retrieve_response_code')->justReturn(500);
        Functions\when('wp_remote_get')->justReturn(new \WP_Error(1));

        $integration = new Integration(null, "2.0.0");

        $response = $integration->getMeetingCounties();
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\BMLT\Integration::postAuthenticatedRootServerRequest
     */
    public function test_can_call_postAuthenticatedRootServerRequest_with_valid_auth(): void
    {
        //         public function postAuthenticatedRootServerRequest()

        Functions\when('\wp_remote_retrieve_body')->justReturn('[{}]');
        Functions\when('\wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));

        $integration = new Integration(null, "2.0.0");

        $response = $integration->postAuthenticatedRootServerRequest('test', array('args' => 'args'));
        $this->assertIsString($response);
    }

    /**
     * @covers bmltwf\BMLT\Integration::postAuthenticatedRootServerRequest
     */
    public function test_cant_call_postAuthenticatedRootServerRequest_with_valid_auth_no_args(): void
    {
        //         public function postAuthenticatedRootServerRequest()

        Functions\when('\wp_remote_retrieve_body')->justReturn('[{}]');
        Functions\when('\wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));

        $integration = new Integration(null, "2.0.0");

        $response = $integration->postAuthenticatedRootServerRequest('test', null);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\BMLT\Integration::postAuthenticatedRootServerRequest
     */
    public function test_cant_call_postAuthenticatedRootServerRequest_with_invalid_bmlt_details(): void
    {
        //             public function postAuthenticatedRootServerRequest($url, $postargs)

        Functions\when('\wp_remote_retrieve_body')->justReturn('[{}]');
        Functions\when('\wp_remote_post')->justReturn(new \WP_Error(1));
        Functions\when('\wp_remote_retrieve_cookies')->returnArg();
        Functions\when('\wp_remote_retrieve_response_code')->justReturn(200);

        Functions\when('\is_wp_error')->justReturn(false);

        $integration = new Integration(null, "2.0.0");

        $response = $integration->postAuthenticatedRootServerRequest('test', array("arg1" => "args1"));
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\BMLT\Integration::postAuthenticatedRootServerRequestSemantic
     */
    public function test_can_call_postAuthenticatedRootServerRequestSemantic_with_valid_auth(): void
    {
        //         public function postAuthenticatedRootServerRequestSemantic()

        Functions\when('\wp_remote_retrieve_body')->justReturn('[{}]');
        Functions\when('\wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));

        $integration = new Integration(null, "2.0.0");

        $response = $integration->postAuthenticatedRootServerRequestSemantic('test', array('args' => 'args'));
        $this->assertIsString($response);
    }

    /**
     * @covers bmltwf\BMLT\Integration::postAuthenticatedRootServerRequestSemantic
     */
    public function test_cant_call_postAuthenticatedRootServerRequestSemantic_with_valid_auth_no_args(): void
    {
        //         public function postAuthenticatedRootServerRequestSemantic()

        Functions\when('\wp_remote_retrieve_body')->justReturn('[{}]');
        Functions\when('\wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));

        $integration = new Integration(null, "2.0.0");

        $response = $integration->postAuthenticatedRootServerRequestSemantic('test', null);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\BMLT\Integration::postAuthenticatedRootServerRequestSemantic
     */
    public function test_cant_call_postAuthenticatedRootServerRequestSemantic_with_invalid_bmlt_details(): void
    {
        //             public function postAuthenticatedRootServerRequestSemantic($url, $postargs)

        // last call triggers the error
        Functions\when('\wp_remote_post')->justReturn(new \WP_Error(1));
        Functions\when('\wp_remote_retrieve_cookies')->returnArg();
        Functions\when('\wp_remote_retrieve_body')->justReturn('[{}]');
        Functions\when('\wp_remote_retrieve_response_code')->justReturn(200);

        Functions\when('\is_wp_error')->justReturn(false);

        $integration = new Integration(null, "2.0.0");

        $response = $integration->postAuthenticatedRootServerRequestSemantic('test', array("arg1" => "args1"));
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\BMLT\Integration::geolocateAddress
     * @covers bmltwf\BMLT\Integration::getGmapsKey
     * @covers bmltwf\BMLT\Integration::AuthenticateRootServer
     * @covers bmltwf\BMLT\Integration::post
     * @covers bmltwf\BMLT\Integration::get
     */
    public function test_can_call_geolocateAddress_with_valid_address(): void
    {

        Functions\when('wp_remote_retrieve_cookies')->returnArg();

        $gmapskey = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> <head> <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" /> <meta http-equiv="content-type" content="text/html; charset=utf-8" /> <meta http-equiv="Content-Script-Type" content="text/javascript" /> <meta http-equiv="Content-Style-Type" content="text/css" /> <link rel="stylesheet" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/styles.css?v=1650950537" /> <link rel="icon" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/images/shortcut.png" /> <link rel="preconnect" href="https://fonts.gstatic.com"> <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;700&display=swap" rel="stylesheet"> <title>Basic Meeting List Toolbox Administration Console</title> </head> <body class="admin_body"> <div class="bmlt_admin_logout_bar"><h4><a href="/bmlt-root-server-master/main_server/index.php?admin_action=logout">Sign Out (Server Administrator)</a></h4><div class="server_version_display_div"> 2.16.5 </div></div><div id="google_maps_api_error_div" class="bmlt_admin_google_api_key_error_bar item_hidden"><h4><a id="google_maps_api_error_a" href="https://bmlt.app/google-api-key/" target="_blank"></a></h4></div><div class="admin_page_wrapper"><div id="bmlt_admin_main_console" class="bmlt_admin_main_console_wrapper_div"> <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=googlemapstestkey&libraries=geometry"></script><script type="text/javascript">var my_localized_strings = {"default_meeting_published":true,"week_starts_on":0,"name":"English","enum":"en","comdef_map_radius_ranges":[0.0625,0.125,0.1875,0.25,0.4375,0.5,0.5625,0.75,0.8125,1,1.25,1.5,1.75,2,2.25,2.5,2.75,3,3.25,3.5,3.75,4,4.25,4.5,4.75,5,5.5,6,6.5,7,7.5,8,8.5,9,9.5,10,11,12,13,14,15,17.5,20,22.5,25,27.5,30,35,40,45,50,60,70,80,90,100,150,200],"include_service_body_email_in_semantic":false,"auto_geocoding_enabled":true,"zip_auto_geocoding_enabled":false,"county_auto_geocoding_enabled":false,"sort_formats":true,"meeting_counties_and_sub_provinces":[],"meeting_states_and_provinces":[],"google_api_key":"googlemapstestkey","dbPrefix":"na","region_bias":"au","default_duration_time":"1:30:00","default_minute_interval":5,"search_spec_map_center":{"longitude":-118.563659,"latitude":34.235918,"zoom":6},"change_type_strings":{"__THE_MEETING_WAS_CHANGED__":"The meeting was changed.","__THE_MEETING_WAS_CREATED__":"The meeting was created.","__THE_MEETING_WAS_DELETED__":"The meeting was deleted.","__THE_MEETING_WAS_ROLLED_BACK__":"The meeting was rolled back to a previous version.","__THE_FORMAT_WAS_CHANGED__":"The format was changed.","__THE_FORMAT_WAS_CREATED__":"The format was created.","__THE_FORMAT_WAS_DELETED__":"The format was deleted.","__THE_FORMAT_WAS_ROLLED_BACK__":"The format was rolled back to a previous version.","__THE_SERVICE_BODY_WAS_CHANGED__":"The service body was changed.","__THE_SERVICE_BODY_WAS_CREATED__":"The service body was created.","__THE_SERVICE_BODY_WAS_DELETED__":"The service body was deleted.","__THE_SERVICE_BODY_WAS_ROLLED_BACK__":"The service body was rolled back to a previous version.","__THE_USER_WAS_CHANGED__":"The user was changed.","__THE_USER_WAS_CREATED__":"The user was created.","__THE_USER_WAS_DELETED__":"The user was deleted.","__THE_USER_WAS_ROLLED_BACK__":"The user was rolled back to a previous version.","__BY__":"by","__FOR__":"for"},"detailed_change_strings":{"was_changed_from":"was changed from","to":"to","was_changed":"was changed","was_added_as":"was added as","was_deleted":"was deleted","was_published":"The meeting was published","was_unpublished":"The meeting was unpublished","formats_prompt":"The meeting format","duration_time":"The meeting duration","start_time":"The meeting start time","longitude":"The meeting longitude","latitude":"The meeting latitude","sb_prompt":"The meeting changed its Service Body from",';

        $json = '{ "results" : [ { "address_components" : [ { "long_name" : "Sydney", "short_name" : "Sydney", "types" : [ "colloquial_area", "locality", "political" ] }, { "long_name" : "New South Wales", "short_name" : "NSW", "types" : [ "administrative_area_level_1", "political" ] }, { "long_name" : "Australia", "short_name" : "AU", "types" : [ "country", "political" ] } ], "formatted_address" : "Sydney NSW, Australia", "geometry" : { "bounds" : { "northeast" : { "lat" : -33.5781409, "lng" : 151.3430209 }, "southwest" : { "lat" : -34.118347, "lng" : 150.5209286 } }, "location" : { "lat" : -33.8688197, "lng" : 151.2092955 }, "location_type" : "APPROXIMATE", "viewport" : { "northeast" : { "lat" : -33.5781409, "lng" : 151.3430209 }, "southwest" : { "lat" : -34.118347, "lng" : 150.5209286 } } }, "partial_match" : true, "place_id" : "ChIJP3Sa8ziYEmsRUKgyFmh9AQM", "types" : [ "colloquial_area", "locality", "political" ] } ], "status" : "OK" }';

        Functions\expect('wp_remote_retrieve_body')->times(5)->andReturn('','', '', $gmapskey, $json);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_get')->justReturn(array());

        $integration = new Integration(true, "2.0.0");
        $response = $integration->geolocateAddress('sydney, australia');

        $this->debug_log("*** GEO RESPONSE");
        $this->debug_log(($response));

        $this->assertNotInstanceOf(\WP_Error::class, $response);
        $this->assertIsNumeric($response['latitude']);
        $this->assertIsNumeric($response['longitude']);
    }

    /**
     * @covers bmltwf\BMLT\Integration::geolocateAddress
     * @covers bmltwf\BMLT\Integration::getGmapsKey
     * @covers bmltwf\BMLT\Integration::AuthenticateRootServer
     * @covers bmltwf\BMLT\Integration::post
     * @covers bmltwf\BMLT\Integration::get
     */
    public function test_cant_call_geolocateAddress_with_invalid_address(): void
    {

        Functions\when('\wp_remote_retrieve_cookies')->returnArg();
        Functions\when('\wp_remote_retrieve_response_code')->justReturn(200);
        $gmapskey = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> <head> <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" /> <meta http-equiv="content-type" content="text/html; charset=utf-8" /> <meta http-equiv="Content-Script-Type" content="text/javascript" /> <meta http-equiv="Content-Style-Type" content="text/css" /> <link rel="stylesheet" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/styles.css?v=1650950537" /> <link rel="icon" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/images/shortcut.png" /> <link rel="preconnect" href="https://fonts.gstatic.com"> <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;700&display=swap" rel="stylesheet"> <title>Basic Meeting List Toolbox Administration Console</title> </head> <body class="admin_body"> <div class="bmlt_admin_logout_bar"><h4><a href="/bmlt-root-server-master/main_server/index.php?admin_action=logout">Sign Out (Server Administrator)</a></h4><div class="server_version_display_div"> 2.16.5 </div></div><div id="google_maps_api_error_div" class="bmlt_admin_google_api_key_error_bar item_hidden"><h4><a id="google_maps_api_error_a" href="https://bmlt.app/google-api-key/" target="_blank"></a></h4></div><div class="admin_page_wrapper"><div id="bmlt_admin_main_console" class="bmlt_admin_main_console_wrapper_div"> <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=googlemapstestkey&libraries=geometry"></script><script type="text/javascript">var my_localized_strings = {"default_meeting_published":true,"week_starts_on":0,"name":"English","enum":"en","comdef_map_radius_ranges":[0.0625,0.125,0.1875,0.25,0.4375,0.5,0.5625,0.75,0.8125,1,1.25,1.5,1.75,2,2.25,2.5,2.75,3,3.25,3.5,3.75,4,4.25,4.5,4.75,5,5.5,6,6.5,7,7.5,8,8.5,9,9.5,10,11,12,13,14,15,17.5,20,22.5,25,27.5,30,35,40,45,50,60,70,80,90,100,150,200],"include_service_body_email_in_semantic":false,"auto_geocoding_enabled":true,"zip_auto_geocoding_enabled":false,"county_auto_geocoding_enabled":false,"sort_formats":true,"meeting_counties_and_sub_provinces":[],"meeting_states_and_provinces":[],"google_api_key":"googlemapstestkey","dbPrefix":"na","region_bias":"au","default_duration_time":"1:30:00","default_minute_interval":5,"search_spec_map_center":{"longitude":-118.563659,"latitude":34.235918,"zoom":6},"change_type_strings":{"__THE_MEETING_WAS_CHANGED__":"The meeting was changed.","__THE_MEETING_WAS_CREATED__":"The meeting was created.","__THE_MEETING_WAS_DELETED__":"The meeting was deleted.","__THE_MEETING_WAS_ROLLED_BACK__":"The meeting was rolled back to a previous version.","__THE_FORMAT_WAS_CHANGED__":"The format was changed.","__THE_FORMAT_WAS_CREATED__":"The format was created.","__THE_FORMAT_WAS_DELETED__":"The format was deleted.","__THE_FORMAT_WAS_ROLLED_BACK__":"The format was rolled back to a previous version.","__THE_SERVICE_BODY_WAS_CHANGED__":"The service body was changed.","__THE_SERVICE_BODY_WAS_CREATED__":"The service body was created.","__THE_SERVICE_BODY_WAS_DELETED__":"The service body was deleted.","__THE_SERVICE_BODY_WAS_ROLLED_BACK__":"The service body was rolled back to a previous version.","__THE_USER_WAS_CHANGED__":"The user was changed.","__THE_USER_WAS_CREATED__":"The user was created.","__THE_USER_WAS_DELETED__":"The user was deleted.","__THE_USER_WAS_ROLLED_BACK__":"The user was rolled back to a previous version.","__BY__":"by","__FOR__":"for"},"detailed_change_strings":{"was_changed_from":"was changed from","to":"to","was_changed":"was changed","was_added_as":"was added as","was_deleted":"was deleted","was_published":"The meeting was published","was_unpublished":"The meeting was unpublished","formats_prompt":"The meeting format","duration_time":"The meeting duration","start_time":"The meeting start time","longitude":"The meeting longitude","latitude":"The meeting latitude","sb_prompt":"The meeting changed its Service Body from",';

        $json = ' { "results" : [], "status" : "ZERO_RESULTS" }';

        Functions\expect('wp_remote_retrieve_body')->times(5)->andReturn('','', '', $gmapskey, $json);

        Functions\when('wp_remote_get')->justReturn(array());

        $integration = new Integration(true, "2.0.0");
        $response = $integration->geolocateAddress('junk, junk');

        $this->debug_log("*** GEO RESPONSE");
        $this->debug_log(($response));
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\BMLT\Integration::geolocateAddress
     * @covers bmltwf\BMLT\Integration::getGmapsKey
     * @covers bmltwf\BMLT\Integration::AuthenticateRootServer
     * @covers bmltwf\BMLT\Integration::post
     * @covers bmltwf\BMLT\Integration::get
     */
    public function test_error_when_gmaps_call_returns_trash(): void
    {

        Functions\when('\wp_remote_retrieve_cookies')->returnArg();
        Functions\when('\wp_remote_retrieve_response_code')->justReturn(200);

        $gmapskey = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> <head> <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" /> <meta http-equiv="content-type" content="text/html; charset=utf-8" /> <meta http-equiv="Content-Script-Type" content="text/javascript" /> <meta http-equiv="Content-Style-Type" content="text/css" /> <link rel="stylesheet" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/styles.css?v=1650950537" /> <link rel="icon" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/images/shortcut.png" /> <link rel="preconnect" href="https://fonts.gstatic.com"> <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;700&display=swap" rel="stylesheet"> <title>Basic Meeting List Toolbox Administration Console</title> </head> <body class="admin_body"> <div class="bmlt_admin_logout_bar"><h4><a href="/bmlt-root-server-master/main_server/index.php?admin_action=logout">Sign Out (Server Administrator)</a></h4><div class="server_version_display_div"> 2.16.5 </div></div><div id="google_maps_api_error_div" class="bmlt_admin_google_api_key_error_bar item_hidden"><h4><a id="google_maps_api_error_a" href="https://bmlt.app/google-api-key/" target="_blank"></a></h4></div><div class="admin_page_wrapper"><div id="bmlt_admin_main_console" class="bmlt_admin_main_console_wrapper_div"> <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=googlemapstestkey&libraries=geometry"></script><script type="text/javascript">var my_localized_strings = {"default_meeting_published":true,"week_starts_on":0,"name":"English","enum":"en","comdef_map_radius_ranges":[0.0625,0.125,0.1875,0.25,0.4375,0.5,0.5625,0.75,0.8125,1,1.25,1.5,1.75,2,2.25,2.5,2.75,3,3.25,3.5,3.75,4,4.25,4.5,4.75,5,5.5,6,6.5,7,7.5,8,8.5,9,9.5,10,11,12,13,14,15,17.5,20,22.5,25,27.5,30,35,40,45,50,60,70,80,90,100,150,200],"include_service_body_email_in_semantic":false,"auto_geocoding_enabled":true,"zip_auto_geocoding_enabled":false,"county_auto_geocoding_enabled":false,"sort_formats":true,"meeting_counties_and_sub_provinces":[],"meeting_states_and_provinces":[],"google_api_key":"googlemapstestkey","dbPrefix":"na","region_bias":"au","default_duration_time":"1:30:00","default_minute_interval":5,"search_spec_map_center":{"longitude":-118.563659,"latitude":34.235918,"zoom":6},"change_type_strings":{"__THE_MEETING_WAS_CHANGED__":"The meeting was changed.","__THE_MEETING_WAS_CREATED__":"The meeting was created.","__THE_MEETING_WAS_DELETED__":"The meeting was deleted.","__THE_MEETING_WAS_ROLLED_BACK__":"The meeting was rolled back to a previous version.","__THE_FORMAT_WAS_CHANGED__":"The format was changed.","__THE_FORMAT_WAS_CREATED__":"The format was created.","__THE_FORMAT_WAS_DELETED__":"The format was deleted.","__THE_FORMAT_WAS_ROLLED_BACK__":"The format was rolled back to a previous version.","__THE_SERVICE_BODY_WAS_CHANGED__":"The service body was changed.","__THE_SERVICE_BODY_WAS_CREATED__":"The service body was created.","__THE_SERVICE_BODY_WAS_DELETED__":"The service body was deleted.","__THE_SERVICE_BODY_WAS_ROLLED_BACK__":"The service body was rolled back to a previous version.","__THE_USER_WAS_CHANGED__":"The user was changed.","__THE_USER_WAS_CREATED__":"The user was created.","__THE_USER_WAS_DELETED__":"The user was deleted.","__THE_USER_WAS_ROLLED_BACK__":"The user was rolled back to a previous version.","__BY__":"by","__FOR__":"for"},"detailed_change_strings":{"was_changed_from":"was changed from","to":"to","was_changed":"was changed","was_added_as":"was added as","was_deleted":"was deleted","was_published":"The meeting was published","was_unpublished":"The meeting was unpublished","formats_prompt":"The meeting format","duration_time":"The meeting duration","start_time":"The meeting start time","longitude":"The meeting longitude","latitude":"The meeting latitude","sb_prompt":"The meeting changed its Service Body from",';

        $json = ' { "junk" : "junk" }';

        Functions\expect('\wp_remote_retrieve_body')->times(5)->andReturn('','', '', $gmapskey, $json);

        Functions\when('\wp_remote_get')->justReturn(array());

        $integration = new Integration(true, "2.0.0");
        $response = $integration->geolocateAddress('junk, junk');

        $this->debug_log("*** GEO RESPONSE");
        $this->debug_log(($response));
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\BMLT\Integration::is_v3_server
     **/

    public function test_is_v3_server_returns_true_if_v3(): void
    {
        $integration = new Integration(true, "3.0.0");
        $response = $integration->is_v3_server();
        $this->assertTrue(($response));
    }

    /**
     * @covers bmltwf\BMLT\Integration::is_v3_server
     **/

    public function test_is_v3_server_returns_true_if_v2(): void
    {
        $integration = new Integration(true, "2.0.0");
        $response = $integration->is_v3_server();
        $this->assertFalse(($response));
    }

    /**
     * @covers bmltwf\BMLT\Integration::getMeetingFormats
     * @covers bmltwf\BMLT\Integration::getMeetingFormatsv3
     **/
    
    public function test_getMeetingFormatsv3(): void
    {

        $integration = new Integration(true, "3.0.0", "token",time()+2000);

        Functions\when('\wp_remote_get')->justReturn('1');
        Functions\when('\wp_remote_retrieve_body')->justReturn($this->formats);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(204);

        $response = $integration->getMeetingFormats();
        $this->assertIsArray($response);
        $this->debug_log($response);
        $respjson = json_encode($response);

        // $this->assertEquals($respjson,'{"1":{"world_id":"BEG","lang":"en","description_string":"This meeting is focused on the needs of new members of NA.","name_string":"Beginners","key_string":"BEG"},"48":{"world_id":null,"lang":"en","description_string":"This meeting is focused on spiritual principals.","name_string":"Spiritual Principals","key_string":"S"},"6":{"world_id":"CAN","lang":"en","description_string":"This meeting is held by candlelight.","name_string":"Candlelight","key_string":"CAN"},"7":{"world_id":"CW","lang":"en","description_string":"Children are welcome.","name_string":"Children Welcome","key_string":"CW"},"8":{"world_id":"DISC","lang":"en","description_string":"This meeting invites participation by all attendees.","name_string":"Discussion","key_string":"D"},"9":{"world_id":null,"lang":"en","description_string":"This meeting primary spoken in Spanish.","name_string":"Espanol","key_string":"ESP"}}');
        $this->assertEquals($respjson,'{"1":{"world_id":"BEG","type":"COMMON_NEEDS_OR_RESTRICTION","lang":"en","description_string":"This meeting is focused on the needs of new members of NA.","name_string":"Beginners","key_string":"BEG"},"48":{"world_id":null,"type":null,"lang":"en","description_string":"This meeting is focused on spiritual principals.","name_string":"Spiritual Principals","key_string":"S"},"6":{"world_id":"CAN","type":"LOCATION","lang":"en","description_string":"This meeting is held by candlelight.","name_string":"Candlelight","key_string":"CAN"},"7":{"world_id":"CW","type":"COMMON_NEEDS_OR_RESTRICTION","lang":"en","description_string":"Children are welcome.","name_string":"Children Welcome","key_string":"CW"},"8":{"world_id":"DISC","type":"MEETING_FORMAT","lang":"en","description_string":"This meeting invites participation by all attendees.","name_string":"Discussion","key_string":"D"},"9":{"world_id":null,"type":"COMMON_NEEDS_OR_RESTRICTION","lang":"en","description_string":"This meeting primary spoken in Spanish.","name_string":"Espanol","key_string":"ESP"}}');
    }


    /**
     * @covers bmltwf\BMLT\Integration::deleteMeeting
     * @covers bmltwf\BMLT\Integration::deleteMeetingv3
     **/
    
    public function test_deleteMeeting_against_v3_with_valid_meeting(): void
    {

        Functions\when('wp_remote_retrieve_response_code')->justReturn(204);

        $integration = new Integration(true, "3.0.0", "token",time()+2000);
        $this->assertTrue($integration->deleteMeeting(1));

    }

    /**
     * @covers bmltwf\BMLT\Integration::deleteMeeting
     * @covers bmltwf\BMLT\Integration::deleteMeetingv3
     **/
    
    public function test_deleteMeeting_against_v3_with_invalid_meeting(): void
    {

        Functions\when('wp_remote_retrieve_response_code')->justReturn(404);

        $integration = new Integration(true, "3.0.0", "token", time()+2000);
        $response = $integration->deleteMeeting(1);
        $this->assertInstanceOf(WP_Error::class, $response);

    }

    /**
     * @covers bmltwf\BMLT\Integration::updateMeeting
     * @covers bmltwf\BMLT\Integration::updateMeetingv3
     **/
    
    public function test_changeMeeting_against_v3_with_valid_meeting(): void
    {

        Functions\when('wp_remote_retrieve_response_code')->justReturn(204);
        $change = array('id_bigint' => 1,'location_text' => 'updated');

        $integration = new Integration(true, "3.0.0", "token",time()+2000);
        $this->assertTrue($integration->updateMeeting($change));

    }

    /**
     * @covers bmltwf\BMLT\Integration::updateMeeting
     * @covers bmltwf\BMLT\Integration::updateMeetingv3
     **/
    
    public function test_updateMeeting_against_v3_with_invalid_meeting(): void
    {

        Functions\when('wp_remote_retrieve_response_code')->justReturn(404);
        $change = array('id_bigint' => 1,'location_text' => 'updated');

        $integration = new Integration(true, "3.0.0", "token", time()+2000);
        $response = $integration->updateMeeting($change);
        $this->assertInstanceOf(WP_Error::class, $response);

    }

    /**
     * @covers bmltwf\BMLT\Integration::updateMeeting
     * @covers bmltwf\BMLT\Integration::updateMeetingv3
     **/
    
    public function test_updateMeeting_against_v3_with_invalid_change(): void
    {

        Functions\when('wp_remote_retrieve_response_code')->justReturn(422);
        $change = array('id_bigint' => 1,'location_text' => 'updated');

        $integration = new Integration(true, "3.0.0", "token", time()+2000);
        $response = $integration->updateMeeting($change);
        $this->assertInstanceOf(WP_Error::class, $response);

    }

    /**
     * @covers bmltwf\BMLT\Integration::getServiceBodies
     * @covers bmltwf\BMLT\Integration::getServiceBodiesv3
     **/
    
    public function test_getServiceBodies_against_v3(): void
    {
        $servicebodies = <<<EOD
        [{"id": 9,"parentId": 20,"name": "Unity Springs Area","description": "Unity Springs Area","type": "AS","adminUserId": 12,"assignedUserIds": [86, 145, 12],"url": "http://www.unityspringsna.org","helpline": "888-385-3121","email": "unityspringsarea@gmail.com","worldId": "AR63340"  },  {"id": 18,"parentId": 20,"name": "Central Florida Area","description": "Central Florida Area","type": "AS","adminUserId": 21,"assignedUserIds": [21, 145, 86],"url": "http://centralfloridana.org/","helpline": "(877) 240-0002","email": "bobbymey@msn.com","worldId": "AR63337"  },  {"id": 20,"parentId": 42,"name": "South Florida Region","description": "South Florida Region","type": "RS","adminUserId": 23,"assignedUserIds": [86, 145, 87, 23],"url": "https://sfrna.net","helpline": "844-623-5674","email": "public-relations@sfrna.net","worldId": "RG633"  },  {"id": 21,"parentId": 20,"name": "Beach and Bay Area","description": "Beach and Bay","type": "AS","adminUserId": 24,"assignedUserIds": [24, 86, 145],"url": "","helpline": "800) 273-4599","email": "","worldId": "AR63303"  },  {"id": 22,"parentId": 20,"name": "Conch Republic Area","description": "Conch Republic Area","type": "AS","adminUserId": 25,"assignedUserIds": [25, 145, 86],"url": "http://www.floridakeysna.org/","helpline": "305) 664-2270","email": "","worldId": "AR63305"  },  {"id": 23,"parentId": 20,"name": "Gold Coast Area","description": "Gold Coast Area","type": "AS","adminUserId": 26,"assignedUserIds": [26, 145, 86],"url": "http://goldcoastna.org/","helpline": "(888) 524-1777","email": "webmaster@goldcoastna.org","worldId": "AR63313"  },  {"id": 24,"parentId": 20,"name": "Gulf Coast Area","description": "Gulf Coast Area","type": "AS","adminUserId": 35,"assignedUserIds": [35],"url": "http://www.nagulfcoastfla.org/","helpline": "(866) 389-1344","email": "","worldId": "AR63316"  },  {"id": 25,"parentId": 20,"name": "Mid-Coast Area","description": "Mid-Coast Area","type": "AS","adminUserId": 27,"assignedUserIds": [27, 145, 86],"url": "http://www.midcoastarea.org/","helpline": "561-393-0303","email": "","worldId": "AR63322"  },  {"id": 26,"parentId": 20,"name": "North Dade Area","description": "North Dade Area","type": "AS","adminUserId": 28,"assignedUserIds": [24, 42, 86, 28, 145, 33, 143],"url": "http://www.northdadearea.org/","helpline": "(866) 935-8811","email": "secretary@northdadearea.org","worldId": "AR63325"  },  {"id": 27,"parentId": 20,"name": "Peace River Area","description": "Peace River Area","type": "AS","adminUserId": 29,"assignedUserIds": [29, 145, 86],"url": "http://peaceriverna.org/","helpline": "(800) 381-7371","email": "","worldId": "AR63326"  },  {"id": 28,"parentId": 20,"name": "Shark Coast Area","description": "Shark Coast Area","type": "AS","adminUserId": 30,"assignedUserIds": [30, 145, 86],"url": "http://sharkcoastna.org/","helpline": "941-493-5747","email": "","worldId": "AR63327"  },  {"id": 29,"parentId": 20,"name": "South Atlantic Area","description": "South Atlantic Area","type": "AS","adminUserId": 31,"assignedUserIds": [31],"url": "http://southatlanticna.org/","helpline": "","email": "southatlanticna@gmail.com","worldId": "AR63329"  },  {"id": 30,"parentId": 20,"name": "South Broward Area","description": "South Broward Area","type": "AS","adminUserId": 32,"assignedUserIds": [86, 145, 32],"url": "http://southbrowardna.org/","helpline": "954-967-6755","email": "sbapublicrelations@gmail.com","worldId": "AR63330"  },  {"id": 31,"parentId": 20,"name": "South Dade Area","description": "South Dade Area","type": "AS","adminUserId": 33,"assignedUserIds": [33, 145, 86],"url": "","helpline": "305-265-9555","email": "","worldId": "AR63334"  },  {"id": 32,"parentId": 20,"name": "Sunset Coast Area","description": "Sunset Coast Area","type": "AS","adminUserId": 34,"assignedUserIds": [34, 145, 86],"url": "http://sunsetcoastna.com","helpline": "239) 451-3275","email": "sunsetcoastnar@gmail.com","worldId": "AR63336"  }]
EOD;
        Functions\when('\wp_remote_retrieve_body')->justReturn($servicebodies);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);

        $integration = new Integration(true, "3.0.0", "token", time()+2000);
        $response = $integration->getServiceBodies();

        $this->assertEquals($response[9]['name'], "Unity Springs Area");
        $this->assertEquals($response[20]['description'], "South Florida Region");
    }

    /**
     * @covers bmltwf\BMLT\Integration::createMeeting
     * @covers bmltwf\BMLT\Integration::createMeetingv3
     **/
    
    public function test_createMeeting_against_v3_with_valid_meeting(): void
    {
                
        Functions\when('\wp_remote_retrieve_body')->justReturn($this->formats);

        Functions\when('wp_remote_retrieve_response_code')->justReturn(201);
        $meeting = array(
                "meeting_name" => "lkj",
                "start_time" => "11:01:00",
                "duration_time" => "01:00:00",
                "location_text" => "lkjlk",
                "location_street" => "lkjlkj",
                "location_municipality" => "New York",
                "weekday_tinyint" => "1",
                "service_body_bigint" => "1050",
                "format_shared_id_list" => "7",
                "virtual_meeting_link" => "https://www.google.com",
                "venue_type" => "3",
                "latitude" => "40.7127753",
                "longitude" => "-74.0059728",
                "published" => "1"
        );
        $integration = new Integration(true, "3.0.0", "token", time()+2000);
        $response = $integration->createMeeting($meeting);
        $this->assertNotInstanceOf(WP_Error::class, $response);

    }

    /**
     * @covers bmltwf\BMLT\Integration::createMeeting
     * @covers bmltwf\BMLT\Integration::createMeetingv3
     **/
    
    public function test_createMeeting_against_v3_with_invalid_meeting(): void
    {
        Functions\when('\wp_remote_retrieve_body')->justReturn($this->formats);

        Functions\when('wp_remote_retrieve_response_code')->justReturn(201);
        $meeting = array(
                "meeting_name" => "lkj",
                "start_time" => "11:01:00",
                "duration_time" => "01:00:00",
                "location_text" => "lkjlk",
                "location_street" => "lkjlkj",
                "location_municipality" => "New York",
                "weekday_tinyint" => "1",
                "service_body_bigint" => "1050",
                "format_shared_id_list" => "7",
                "virtual_meeting_link" => "https://www.google.com",
                "venue_type" => "10",
                "latitude" => "40.7127753",
                "longitude" => "-74.0059728",
                "published" => "1"
        );

        Functions\when('wp_remote_retrieve_response_code')->justReturn(422);

        $integration = new Integration(true, "3.0.0", "token", time()+2000);
        $response = $integration->createMeeting($meeting);
        $this->assertInstanceOf(WP_Error::class, $response);

    }

}