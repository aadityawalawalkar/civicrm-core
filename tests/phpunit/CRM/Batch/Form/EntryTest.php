<?php

/**
 *  File for the EntryTest class
 *
 *  (PHP 5)
 *
 *   @package   CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

require_once 'CiviTest/CiviUnitTestCase.php';


/**
 *  Test CRM/Member/BAO Membership Log add , delete functions
 *
 *  @package   CiviCRM
 */
class CRM_Batch_Form_EntryTest extends CiviUnitTestCase {

  public $_eNoticeCompliant = TRUE;
  /**
   * Membership type name used in test function
   * @var String
   */
  protected $_membershipTypeName = NULL;

  /**
   * Membership type id used in test function
   * @var String
   */
  protected $_membershipTypeID = NULL;

  /**
   * Contact id used in test function
   * @var String
   */
  protected $_contactID = NULL;
  /**
   * Contact id used in test function
   * @var String
   */
  protected $_contactID2 = NULL;

  /**
   * Contact id used in test function
   * @var String
   */
  protected $_contactID3 = NULL;

  /**
   * Contact id used in test function
   * @var String
   */
  protected $_contactID4 = NULL;

  /**
   * Describe test class
   * @return array
   */
  function get_info() {
    return array(
      'name' => 'MembershipParserTest',
      'description' => 'Test import parser function',
      'group' => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();

    $params = array(
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'name_a_b' => 'Test Employee of',
      'name_b_a' => 'Test Employer of',
    );
    $this->_relationshipTypeId = $this->relationshipTypeCreate($params);
    $this->_orgContactID = $this->organizationCreate();
    $this->_financialTypeId = 1;
    $this->_membershipTypeName = 'Mickey Mouse Club Member';
    $params = array(
      'name' => $this->_membershipTypeName,
      'description' => NULL,
      'minimum_fee' => 10,
      'duration_unit' => 'year',
      'member_of_contact_id' => $this->_orgContactID,
      'period_type' => 'fixed',
      'duration_interval' => 1,
      'financial_type_id' => $this->_financialTypeId,
      'relationship_type_id' => $this->_relationshipTypeId,
      'visibility' => 'Public',
      'is_active' => 1,
      'fixed_period_start_day' => 101,
      'fixed_period_rollover_day' => 1231
    );
    $membershipType = CRM_Member_BAO_MembershipType::add($params, $ids);
    $this->_membershipTypeID = $membershipType->id;

    $this->_mebershipStatusID = $this->membershipStatusCreate('test status');
    $this->_contactID = $this->individualCreate();
    $contact2Params = array(
      'first_name' => 'Anthonita',
      'middle_name' => 'J.',
      'last_name' => 'Anderson',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email' => 'b@c.com',
      'contact_type' => 'Individual',
    );
    $this->_contactID2 = $this->individualCreate($contact2Params);
    $this->_contactID3 = $this->individualCreate(array('first_name' => 'bobby', 'email' => 'c@d.com'));
    $this->_contactID4 = $this->individualCreate(array('first_name' => 'bobbynita', 'email' => 'c@de.com'));

    $session = CRM_Core_Session::singleton();
    $session->set('dateTypes', 1);

  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   */
  function tearDown() {
    $tablesToTruncate = array('civicrm_membership', 'civicrm_membership_log', 'civicrm_contribution', 'civicrm_membership_payment');
    $this->quickCleanup($tablesToTruncate);
    $this->relationshipTypeDelete($this->_relationshipTypeId);
    $this->membershipTypeDelete(array('id' => $this->_membershipTypeID));
    $this->membershipStatusDelete($this->_mebershipStatusID);
    $this->contactDelete($this->_contactID);
    $this->contactDelete($this->_contactID2);
    $this->contactDelete($this->_orgContactID);
  }

  /**
   *  Test Import
   */
  function testProcessMembership() {
    $form = new CRM_Batch_Form_Entry();
    $params = $this->getMembershipData();
    $this->assertTrue($form->testProcessMembership($params));
    $result = $this->callAPISuccess('membership', 'get', array());
    $this->assertEquals(3, $result['count']);

    //check start dates #1 should default to 1 Jan this year, #2 should be as entered
    $this->assertEquals(date('Y-m-d', strtotime('first day of January this year')), $result['values'][1]['start_date']);
    $this->assertEquals('2013-02-03', $result['values'][2]['start_date']);

    //check start dates #1 should default to 1 Jan this year, #2 should be as entered
    $this->assertEquals(date('Y-m-d', strtotime('last day of December this year')), $result['values'][1]['end_date']);
    $this->assertEquals(date('Y-m-d', strtotime('last day of December this year')), $result['values'][2]['end_date']);
    $this->assertEquals('2013-12-01', $result['values'][3]['end_date']);

    //check start dates #1 should default to 1 Jan this year, #2 should be as entered
    $this->assertEquals(date('Y-m-d', strtotime('07/22/2013')), $result['values'][1]['join_date']);
    $this->assertEquals(date('Y-m-d', strtotime('07/03/2013')), $result['values'][2]['join_date']);
    $this->assertEquals(date('Y-m-d', strtotime('now')), $result['values'][3]['join_date']);

  }

  /*
   * data provider for test process membership
   */
  function getMembershipData() {

    /*
     Array (

    );
    */
    return array(
      'batch_id' => 4,
      'primary_profiles' => array(1 => NULL, 2 => NULL, 3 => NULL),
      'primary_contact_select_id' => Array (
        1 => $this->_contactID,
        2 => $this->_contactID2,
        3 => $this->_contactID3,
        ),
      'field' => array(
        1 => array(
          'membership_type' => Array (0 => 1, 1 => 1),// (I was unable to determine what these both mean but both are refered to in code
          'join_date' => '07/22/2013',
          'membership_start_date' => NULL,
          'membership_end_date' => NULL,
          'membership_source' => NULL,
          'financial_type' => 2,
          'total_amount' => 1,
          'receive_date' => '07/24/2013',
          'receive_date_time' => NULL,
          'payment_instrument' => 1,
          'check_number' => NULL,
          'contribution_status_id' => 1,
        ),
      2 => array(
        'membership_type' => Array (0 => 1, 1 => 1 ),
        'join_date' => '07/03/2013',
        'membership_start_date' => '02/03/2013',
        'membership_end_date' => NULL,
        'membership_source' => NULL,
        'financial_type' => 2,
        'total_amount' => 1,
        'receive_date' => '07/17/2013',
        'receive_date_time' => NULL,
        'payment_instrument' => NULL,
        'check_number' => NULL,
        'contribution_status_id' => 1,
       ),
        // no join date, coded end date
       3 => array(
         'membership_type' => Array (0 => 1, 1 => 1 ),
         'join_date' => NULL,
         'membership_start_date' => NULL,
         'membership_end_date' => '2013-12-01',
         'membership_source' => NULL,
         'financial_type' => 2,
         'total_amount' => 1,
         'receive_date' => '07/17/2013',
         'receive_date_time' => NULL,
         'payment_instrument' => NULL,
         'check_number' => NULL,
         'contribution_status_id' => 1,
       ),

      ),
      'actualBatchTotal' => 0,

    );
  }
}

