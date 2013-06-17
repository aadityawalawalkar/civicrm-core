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
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

require_once 'CiviTest/CiviSeleniumTestCase.php';
class WebTest_Contact_AddressBlockDeleteTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testAddressBlockDelete() {
    // Logging in.
    $this->webtestLogin();

    // Go to the URL to create an Individual contact.
    $this->openCiviPage('contact/add', array('reset' => 1, 'ct' => "Individual"));

    //contact details section
    $firstName = "John" . substr(sha1(rand()), 0, 7);
    $lastName = "Smith" . substr(sha1(rand()), 0, 7);

    //fill in name
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    //address section
    $this->click("addressBlock");
    $this->waitForElementPresent("address_1_street_address");

    //fill in address 1
    $this->click("//div[@id='addressBlockId']/div[1]");
    $this->select("address_1_location_type_id", "value=1");
    $this->type("address_1_street_address", "121A Sherman St. Apt. 12");
    $this->type("address_1_city", "Dumfries");
    $this->type("address_1_postal_code", "1234");
    $this->select("address_1_state_province_id", "value=1019");
    $this->type("address_1_geo_code_1", "1234");
    $this->type("address_1_geo_code_2", "5678");

    //fill in address 2
    $this->click("//div[@id='addMoreAddress1']/a/span");
    $this->select("address_2_location_type_id", "value=2");
    $this->waitForElementPresent("address_2_street_address");
    $this->type("address_2_street_address", "121 Sherman Street #15");
    $this->type("address_2_city", "Birmingham");
    $this->type("address_2_postal_code", "3456");
    $this->select("address_2_state_province_id", "value=1002");
    $this->type("address_2_geo_code_1", "2678");
    $this->type("address_2_geo_code_2", "1456");

    //fill in address 3
    $this->click("//div[@id='addMoreAddress2']/a/span");
    $this->select("address_3_location_type_id", "value=3");
    $this->waitForElementPresent("address_3_street_address");
    $this->type("address_3_street_address", "121 Sherman Rd Unit 155");
    $this->type("address_3_city", "Birmingham");
    $this->type("address_3_postal_code", "3456");
    $this->select("address_3_state_province_id", "value=1002");

    //fill in address 4
    $this->click("//div[@id='addMoreAddress3']/a/span");
    $this->select("address_4_location_type_id", "value=4");
    $this->waitForElementPresent("address_4_street_address");
    $this->type("address_4_street_address", "121 SW Sherman Way Suite 15");
    $this->type("address_4_city", "Birmingham");
    $this->type("address_4_postal_code", "5491");
    $this->assertTrue($this->isTextPresent("- select - United States"));
    $this->select("address_4_state_province_id", "value=1002");

    // Store location type of each address
    for ($i = 1; $i <= 4; ++$i) {
      $location[$this->getSelectedLabel("address_{$i}_location_type_id")] = $i;
    }

    // Submit form
    $this->clickLink("_qf_Contact_upload_view");
    $this->waitForText('crm-notification-container', "{$firstName} {$lastName}");

    //Get the ids of newly created contact
    $contactId = $this->urlArg('cid');

    //Go to the url of edit contact
    $this->openCiviPage('contact/add', array('reset' => 1, 'action' => 'update', 'cid' => $contactId), 'addressBlock');
    $this->click("addressBlock");
    $this->click("//div[@id='addressBlockId']/div[1]");
    
    $this->waitForElementPresent("//div[@id='addressBlock']/div[7]/fieldset/table/tbody/tr/td[2]/a");
    // Click on Delete Address link
    $this->click("//div[@id='addressBlock']/div[7]/fieldset/table/tbody/tr/td[2]/a");

    $this->waitForElementPresent("//div[@class='ui-dialog ui-widget ui-widget-content ui-corner-all ui-draggable ui-dialog-buttons']/div[3]/div/button[2]");
    // Click on Continue button in the dialog box.
    $this->click("//div[@class='ui-dialog ui-widget ui-widget-content ui-corner-all ui-draggable ui-dialog-buttons']/div[3]/div/button[2]/span");
    $this->waitForText('crm-notification-container', 'Address Deleted');

    $this->waitForElementPresent("//div[@id='addressBlock']/div[9]/fieldset/div/a");
    // click on Add Adress
    $this->click("//div[@id='addressBlock']/div[9]/fieldset/div/a");
    
    // Check for blank data in 4th Address Block
    $this->waitForElementPresent("//div[@id='addressBlock']/div[11]/fieldset/div/a");
    $this->assertEquals('', $this->getValue('address_4_location_type_id'));
    $this->assertEquals('', $this->getValue('address_4_street_address'));
    $this->assertEquals('', $this->getValue('address_4_city'));
    $this->assertEquals('', $this->getValue('address_4_postal_code'));
    $this->assertEquals('', $this->getValue('address_4_state_province_id'));

    // Reset First Name & Last Name
    $this->type("first_name", '');
    $this->type("last_name", '');

    // Submit form
    $this->clickLink("_qf_Contact_upload_view");
    //$this->waitForText('crm-notification-container', "{$firstName} {$lastName}");

    // Check for blank data in 4th Address Block
    $this->waitForElementPresent("_qf_Contact_upload_view");
    $this->assertEquals('', $this->getValue('address_4_location_type_id'));
    $this->assertEquals('', $this->getValue('address_4_street_address'));
    $this->assertEquals('', $this->getValue('address_4_city'));
    $this->assertEquals('', $this->getValue('address_4_postal_code'));
    $this->assertEquals('', $this->getValue('address_4_state_province_id'));
  }
}
