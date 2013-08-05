<?php
/**
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


/**
 *  Test APIv3 civicrm_participant_* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Event
 */

class api_v3_ParticipantPaymentTest extends CiviUnitTestCase {

  protected $_apiversion = 3;
  protected $_contactID;
  protected $_createdParticipants;
  protected $_participantID;
  protected $_eventID;
  protected $_participantPaymentID;
  protected $_contributionTypeId;
  public $_eNoticeCompliant = TRUE;

  function get_info() {
    return array(
      'name' => 'Participant Create',
      'description' => 'Test all Participant Create API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();
    $tablesToTruncate = array(
      'civicrm_contribution',
      'civicrm_contact',
    );
    $this->quickCleanup($tablesToTruncate);
    $event = $this->eventCreate(NULL);
    $this->_eventID = $event['id'];
    $this->_contactID = $this->individualCreate();
    $this->_createdParticipants = array();
    $this->_individualId = $this->individualCreate();

    $this->_participantID = $this->participantCreate(array('contactID' => $this->_contactID, 'eventID' => $this->_eventID));
    $this->_contactID2 = $this->individualCreate();
    $this->_participantID2 = $this->participantCreate(array('contactID' => $this->_contactID2, 'eventID' => $this->_eventID));
    $this->_participantID3 = $this->participantCreate(array('contactID' => $this->_contactID2, 'eventID' => $this->_eventID));

    $this->_contactID3 = $this->individualCreate();
    $this->_participantID4 = $this->participantCreate(array('contactID' => $this->_contactID3, 'eventID' => $this->_eventID));
  }

  function tearDown() {
    $this->eventDelete($this->_eventID);
    $this->quickCleanup(
      array(
        'civicrm_contact',
        'civicrm_contribution',
        'civicrm_participant',
        'civicrm_participant_payment',
        'civicrm_line_item',
        'civicrm_financial_item',
        'civicrm_financial_trxn',
        'civicrm_entity_financial_trxn',
      ),
      TRUE
    );
  }

  ///////////////// civicrm_participant_payment_create methods

  /**
   * Test civicrm_participant_payment_create with wrong params type
   */
  function testPaymentCreateWrongParamsType() {
    $params = 'a string';
    $result = $this->callAPIFailure('participant_payment', 'create', $params);
  }

  /**
   * Test civicrm_participant_payment_create with empty params
   */
  function testPaymentCreateEmptyParams() {
    $params = array();
    $result = $this->callAPIFailure('participant_payment', 'create', $params);
  }

  /**
   * check without contribution_id
   */
  function testPaymentCreateMissingContributionId() {
    //Without Payment EntityID
    $params = array(
      'participant_id' => $this->_participantID,    );

    $participantPayment = $this->callAPIFailure('participant_payment', 'create', $params);
  }

  /**
   * check with valid array
   */
  function testPaymentCreate() {
    //Create Contribution & get contribution ID
    $contributionID = $this->contributionCreate($this->_contactID);

    //Create Participant Payment record With Values
    $params = array(
      'participant_id' => $this->_participantID,
      'contribution_id' => $contributionID,
    );

    $result = $this->callAPIAndDocument('participant_payment', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertTrue(array_key_exists('id', $result), 'in line ' . __LINE__);

    //delete created contribution
    $this->contributionDelete($contributionID);
  }


  ///////////////// civicrm_participant_payment_create methods

  /**
   * Test civicrm_participant_payment_create with wrong params type
   */
  function testPaymentUpdateWrongParamsType() {
    $params = 'a string';
    $result = $this->callAPIFailure('participant_payment', 'create', $params);
    $this->assertEquals('Input variable `params` is not an array', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * check with empty array
   */
  function testPaymentUpdateEmpty() {
    $params = array();
    $participantPayment = $this->callAPIFailure('participant_payment', 'create', $params);
  }

  /**
   * check with missing participant_id
   */
  function testPaymentUpdateMissingParticipantId() {
    //WithoutParticipantId
    $params = array(
      'contribution_id' => '3',    );

    $participantPayment = $this->callAPIFailure('participant_payment', 'create', $params);
  }

  /**
   * check with missing contribution_id
   */
  function testPaymentUpdateMissingContributionId() {
    $params = array(
      'participant_id' => $this->_participantID,    );
    $participantPayment = $this->callAPIFailure('participant_payment', 'create', $params);
  }

  /**
   * check financial records for offline Participants
   */
  function testPaymentOffline() {

    // create contribution w/o fee
    $contributionID = $this->contributionCreate($this->_contactID, $this->_contributionTypeId, NULL, NULL, 4, FALSE);

    $this->_participantPaymentID = $this->participantPaymentCreate($this->_participantID, $contributionID);
    $params = array(
      'id' => $this->_participantPaymentID,
      'participant_id' => $this->_participantID,
      'contribution_id' => $contributionID,    );

    // Update Payment
    $participantPayment = $this->callAPISuccess('participant_payment', 'create', $params);
    $this->assertEquals($participantPayment['id'], $this->_participantPaymentID);
    $this->assertTrue(array_key_exists('id', $participantPayment));
    // check Financial records
    $this->_checkFinancialRecords($params, 'offline');
    $params = array(
      'id' => $this->_participantPaymentID,
    );
    $deletePayment = $this->callAPISuccess('participant_payment', 'delete', $params);
  }

  /**
   * check financial records for online Participant
   */
  function testPaymentOnline() {

    $paymentProcessor = $this->processorCreate();
    $pageParams['processor_id'] = $paymentProcessor->id;
    $contributionPage = $this->contributionPageCreate($pageParams);
    $contributionParams = array(
       'contact_id' => $this->_contactID,
       'contribution_page_id' => $contributionPage['id'],
       'payment_processor' => $paymentProcessor->id,
    );
    $contributionID = $this->onlineContributionCreate($contributionParams, 1);

    $this->_participantPaymentID = $this->participantPaymentCreate($this->_participantID, $contributionID);
    $params = array(
      'id' => $this->_participantPaymentID,
      'participant_id' => $this->_participantID,
      'contribution_id' => $contributionID,    );

    // Update Payment
    $participantPayment = $this->callAPISuccess('participant_payment', 'create', $params);
    $this->assertEquals($participantPayment['id'], $this->_participantPaymentID);
    $this->assertTrue(array_key_exists('id', $participantPayment));
    // check Financial records
    $this->_checkFinancialRecords($params, 'online');
    $params = array(
      'id' => $this->_participantPaymentID,
    );
    $deletePayment = $this->callAPISuccess('participant_payment', 'delete', $params);
  }

  /**
   * check financial records for online Participant pay later scenario
   */
  function testPaymentPayLaterOnline() {

    $paymentProcessor = $this->processorCreate();
    $pageParams['processor_id'] = $paymentProcessor->id;
    $pageParams['is_pay_later'] = 1;
    $contributionPage = $this->contributionPageCreate($pageParams);
    $contributionParams = array(
      'contact_id' => $this->_contactID,
      'contribution_page_id' => $contributionPage['id'],
      'contribution_status_id' => 2,
      'is_pay_later' => 1,
    );
    $contributionID = $this->onlineContributionCreate($contributionParams, 1);

    $this->_participantPaymentID = $this->participantPaymentCreate($this->_participantID, $contributionID);
    $params = array(
      'id' => $this->_participantPaymentID,
      'participant_id' => $this->_participantID,
      'contribution_id' => $contributionID,    );

    // Update Payment
    $participantPayment = $this->callAPISuccess('participant_payment', 'create', $params);
    // check Financial Records
    $this->_checkFinancialRecords($params, 'payLater');
    $this->assertEquals($participantPayment['id'], $this->_participantPaymentID);
    $this->assertTrue(array_key_exists('id', $participantPayment));
    $params = array(
      'id' => $this->_participantPaymentID,
    );
    $deletePayment = $this->callAPISuccess('participant_payment', 'delete', $params);
  }

  ///////////////// civicrm_participant_payment_delete methods

  /**
   * Test civicrm_participant_payment_delete with wrong params type
   */
  function testPaymentDeleteWrongParamsType() {
    $params = 'a string';
    $result = $this->callAPIFailure('participant_payment', 'delete', $params);
  }

  /**
   * check with empty array
   */
  function testPaymentDeleteWithEmptyParams() {
    $params = array();
    $deletePayment = $this->callAPIFailure('participant_payment', 'delete', $params);
    $this->assertEquals('Mandatory key(s) missing from params array: id', $deletePayment['error_message']);
  }

  /**
   * check with wrong id
   */
  function testPaymentDeleteWithWrongID() {
    $params = array(
      'id' => 0,    );
    $deletePayment = $this->callAPIFailure('participant_payment', 'delete', $params);
    $this->assertEquals($deletePayment['error_message'], 'Mandatory key(s) missing from params array: id');
  }

  /**
   * check with valid array
   */
  function testPaymentDelete() {

    // create contribution
    $contributionID = $this->contributionCreate($this->_contactID, $this->_contributionTypeId);

    $this->_participantPaymentID = $this->participantPaymentCreate($this->_participantID, $contributionID);

    $params = array(
      'id' => $this->_participantPaymentID,
    );

    $result = $this->callAPIAndDocument('participant_payment', 'delete', $params, __FUNCTION__, __FILE__);
  }

  ///////////////// civicrm_participantPayment_get methods
  /**
   * Test civicrm_participantPayment_get - success expected.
   */
  public function testGet() {
    //Create Contribution & get contribution ID
    $contributionID = $this->contributionCreate($this->_contactID3, $this->_contributionTypeId);
    $participantPaymentID = $this->participantPaymentCreate($this->_participantID4, $contributionID);

    //Create Participant Payment record With Values
    $params = array(
      'participant_id' => $this->_participantID4,
      'contribution_id' => $contributionID,
    );

    $result = $this->callAPIAndDocument('participant_payment', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][$result['id']]['participant_id'], $this->_participantID4, 'Check Participant Id');
    $this->assertEquals($result['values'][$result['id']]['contribution_id'], $contributionID, 'Check Contribution Id');
  }

  function _checkFinancialRecords($params, $context) {
    $entityParams = array(
      'entity_id' => $params['id'],
      'entity_table' => 'civicrm_contribution',
    );
    $trxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams));
    $trxnParams = array(
      'id' => $trxn['financial_trxn_id'],
    );

    switch ($context) {
      case 'online':
        $compareParams = array(
          'to_financial_account_id' => 12,
          'total_amount' => 100,
          'status_id' => 1,
        );
        break;

      case 'offline':
        $compareParams = array(
          'to_financial_account_id' => 6,
          'total_amount' => 100,
          'status_id' => 1,
        );
        break;
      case 'payLater':
        $compareParams = array(
          'to_financial_account_id' => 7,
          'total_amount' => 100,
          'status_id' => 2,
        );
        break;
    }

    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn', $trxnParams, $compareParams);
    $entityParams = array(
      'financial_trxn_id' => $trxn['financial_trxn_id'],
      'entity_table' => 'civicrm_financial_item',
    );
    $entityTrxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams));
    $fitemParams = array(
      'id' => $entityTrxn['entity_id'],
    );
    if ($context == 'offline' || $context == 'online') {
      $compareParams = array(
        'amount' => 100,
        'status_id' => 1,
        'financial_account_id' => 1,
      );
    }
    elseif ($context == 'payLater') {
      $compareParams = array(
        'amount' => 100,
        'status_id' => 3,
        'financial_account_id' => 1,
      );
    }
    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialItem', $fitemParams, $compareParams);
  }
}

