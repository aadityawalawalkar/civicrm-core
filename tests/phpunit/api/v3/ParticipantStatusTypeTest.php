<?php

/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2013                                |
+--------------------------------------------------------------------+
| This file is a part of CiviCRM.                                    |
|                                                                    |
| CiviCRM is free software; you can copy, modify, and distribute it  |
| under the terms of the GNU Affero General Public License           |
| Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
|                                                                    |
| CiviCRM is distributed in the hope that it will be useful, but     |
| WITHOUT ANY WARRANTY; without even the implied warranty of         |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
| See the GNU Affero General Public License for more details.        |
|                                                                    |
| You should have received a copy of the GNU Affero General Public   |
| License and the CiviCRM Licensing Exception along                  |
| with this program; if not, contact CiviCRM LLC                     |
| at info[AT]civicrm[DOT]org. If you have questions about the        |
| GNU Affero General Public License or the licensing of CiviCRM,     |
| see the CiviCRM license FAQ at http://civicrm.org/licensing        |
+--------------------------------------------------------------------+
*/

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_ParticipantStatusTypeTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $params;
  protected $id;
  public $_eNoticeCompliant = TRUE;

  public $DBResetRequired = FALSE; function setUp() {
    $this->_apiversion = 3;
    $this->params = array(
      'version' => 3,
      'name' => 'test status',
      'label' => 'I am a test',
      'class' => 'Positive',
      'is_reserved' => 0,
      'is_active' => 1,
      'is_counted' => 1,
      'visibility_id' => 1,
      'weight' => 10,
    );
    parent::setUp();
  }

  function tearDown() {}

  public function testCreateParticipantStatusType() {
    $result = civicrm_api('participant_status_type', 'create', $this->params);
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
  }

  public function testGetParticipantStatusType() {
    $result = civicrm_api('participant_status_type', 'get', $this->params);
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->id = $result['id'];
  }

  public function testDeleteParticipantStatusType() {

    $ParticipantStatusType = civicrm_api('ParticipantStatusType', 'Create', $this->params);
    $entity = civicrm_api('participant_status_type', 'get', array('version' => 3));
    $result = civicrm_api('participant_status_type', 'delete', array('version' => 3, 'id' => $ParticipantStatusType['id']));
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $getCheck = civicrm_api('ParticipantStatusType', 'GET', array('version' => 3, 'id' => $ParticipantStatusType['id']));
    $checkDeleted = civicrm_api('ParticipantStatusType', 'Get', array(
      'version' => 3,
      ));
    $this->assertEquals($entity['count'] - 1, $checkDeleted['count'], 'In line ' . __LINE__);
  }
}

