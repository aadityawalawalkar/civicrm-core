<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

require_once 'CiviTest/CiviSeleniumTestCase.php';
class WebTest_Member_MembershipDetailReport extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   * CRM-12310
   */
  function testMembershipDetailReport() {
    // Log in using webtestLogin() method
    $this->webtestLogin();
    $firstName = 'John_' . substr(sha1(rand()), 0, 7);
    $lastName = 'Anderson_' . substr(sha1(rand()), 0, 7);
    $email = "{$firstName}.{$lastName}@example.com";
    $contactName = "$lastName, $firstName";

    //adding contact for membership sign up
    $this->webtestAddContact($firstName, $lastName, $email);
    $urlElements = $this->parseURL();
    $cid = $urlElements['queryString']['cid'];
    $this->assertType('numeric', $cid);

    // wait for contact saved confirmation
    $this->waitForText('crm-notification-container', "Contact Saved");
    
    // click on membership tab
    $this->click("css=li#tab_member a");

    // wait for Submit Credit Card Membership button
    $this->waitForElementPresent("xpath=//div[@class='action-link']/a[2]/span");

    // click on Add Membership button
    $this->click("xpath=//div[@class='action-link']/a/span/..");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_Membership_cancel-bottom');

    // fill in Membership Organization and Type
    // select Default Organisation
    $this->select('membership_type_id[0]', "value=1");
    $this->select('membership_type_id[1]', "label=General");

    // click on Save and New button
    $this->clickLink('_qf_Membership_upload_new-bottom', '_qf_Membership_cancel-bottom');
    
    // Is status message correct?
    $this->waitForText('crm-notification-container', "General membership for $firstName $lastName has been added.");

    // Add another membership for the same contact

    // fill in Membership Organization and Type
    // select Default Organisation
    $this->select('membership_type_id[0]', "value=1");
    $this->select('membership_type_id[1]', "label=Student");

    // click on Save button
    $this->clickLink('_qf_Membership_upload-bottom');

    // Is status message correct?
    $this->waitForText('crm-notification-container', "Student membership for $firstName $lastName has been added.");

    // go to Membership Detail Report page
    $this->openCiviPage("report/instance/21", "reset=1");
    $this->waitForElementPresent("_qf_Detail_submit_group");
    
    // check firstname & lastname fields as display columns
    $this->check('fields_first_name');
    $this->check('fields_last_name');
    
    // set filter: contact_name - Is Equal To
    $this->select('sort_name_op', "label=Is equal to");
    // fill the sort_name_value of contact created earlier
    $this->type('sort_name_value', $contactName);

    // click on Preview Report button
    $this->clickLink('_qf_Detail_submit', "_qf_Detail_submit_group");

    $this->waitForElementPresent("xpath=//div[@id='crm-container']/form[@id='Detail']/div[3]/table[2]/tbody/tr/td[@class='crm-report-civicrm_contact_sort_name']/a");
    $this->waitForElementPresent("xpath=//div[@id='crm-container']/form[@id='Detail']/div[3]/table[3]/tbody/tr/td");
    
    // check the number of records displayed (should be 2)
    $this->assertEquals(trim($this->getText("xpath=//div[@id='crm-container']/form[@id='Detail']/div[3]/table[3]/tbody/tr/td")), 2);

    // check that first row has Contact Name, First Name & Last Name columns populated
    $this->assertEquals(trim($this->getText("xpath=//div[@id='crm-container']/form[@id='Detail']/div[3]/table[2]/tbody/tr/td[@class='crm-report-civicrm_contact_sort_name']")), $contactName);
    $this->assertEquals(trim($this->getText("xpath=//div[@id='crm-container']/form[@id='Detail']/div[3]/table[2]/tbody/tr/td[@class='crm-report-civicrm_contact_first_name']")), $firstName);
    $this->assertEquals(trim($this->getText("xpath=//div[@id='crm-container']/form[@id='Detail']/div[3]/table[2]/tbody/tr/td[@class='crm-report-civicrm_contact_last_name']")), $lastName);

    // check that second row does not have values set for the columns: Contact Name, First Name & Last Name
    // since this row is for the same contact.
    $this->assertEquals(trim($this->getText("xpath=//div[@id='crm-container']/form[@id='Detail']/div[3]/table[2]/tbody/tr[2]/td[@class='crm-report-civicrm_contact_sort_name']")), '');
    $this->assertEquals(trim($this->getText("xpath=//div[@id='crm-container']/form[@id='Detail']/div[3]/table[2]/tbody/tr[2]/td[@class='crm-report-civicrm_contact_first_name']")), '');
    $this->assertEquals(trim($this->getText("xpath=//div[@id='crm-container']/form[@id='Detail']/div[3]/table[2]/tbody/tr[2]/td[@class='crm-report-civicrm_contact_last_name']")), '');
  }
}