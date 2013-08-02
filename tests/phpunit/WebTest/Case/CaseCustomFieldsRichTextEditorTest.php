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
class WebTest_Case_CaseCustomFieldsRichTextEditorTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testCaseCustomRichEditor() {
    $this->webtestLogin('admin');

    // Enable CiviCase module if necessary
    $this->enableComponents("CiviCase");

    $customGrp1 = "CaseCustom_Data1_" . substr(sha1(rand()), 0, 7);

    // create custom group1
    $this->openCiviPage('admin/custom/group', 'reset=1');
    $this->clickLink("newCustomDataGroup");
    $this->type("title", $customGrp1);
    $this->select("extends[0]", "value=Case");
    $this->select("extends_1", "value=2");
    $this->clickLink("_qf_Group_next-bottom");

    // get custom group id
    $customGrpId1 = $this->urlArg('gid');

    $customId = $this->_testGetCustomFieldId($customGrpId1);
    $cusId_1 = 'custom_' . $customId[0] . '_-1';
    $cusId_2 = 'custom_' . $customId[1] . '_-1';

    // let's give full CiviCase permissions.
    $permission = array('edit-2-access-all-cases-and-activities', 'edit-2-access-my-cases-and-activities', 'edit-2-administer-civicase', 'edit-2-delete-in-civicase');
    $this->changePermissions($permission);

    // Log in as normal user
    $this->webtestLogin();

    // Go to reserved New Individual Profile to set value for logged in user's contact name (we'll need that later)
    $this->openCiviPage('profile/edit', 'reset=1&gid=4', '_qf_Edit_next');
    $testUserFirstName = "Testuserfirst";
    $testUserLastName = "Testuserlast";
    $this->type("first_name", $testUserFirstName);
    $this->type("last_name", $testUserLastName);
    $this->clickLink("_qf_Edit_next", "profilewrap4");

    $this->openCiviPage('case/add', 'reset=1&action=add&atype=13&context=standalone', '_qf_Case_upload-bottom');

    // Try submitting the form without creating or selecting a contact (test for CRM-7971)
    $this->clickLink("_qf_Case_upload-bottom", "css=span.crm-error");

    // Adding contact with randomized first name (so we can then select that contact when creating case)
    // We're using pop-up New Contact dialog
    $firstName = substr(sha1(rand()), 0, 7);
    $lastName = "Fraser";
    $contactName = "{$lastName}, {$firstName}";
    $displayName = "{$firstName} {$lastName}";
    $email = "{$lastName}.{$firstName}@example.org";
    $custFname = "Mike" . substr(sha1(rand()), 0, 7);
    $custLname = "Krist" . substr(sha1(rand()), 0, 7);
    $this->webtestNewDialogContact($firstName, $lastName, $email, $type = 4);

    // Fill in other form values. We'll use a case type which is included in CiviCase sample data / xml files.
    $caseTypeLabel = "Adult Day Care Referral";

    // activity types we expect for this case type
    $activityTypes = array("ADC referral", "Follow up", "Medical evaluation", "Mental health evaluation");
    $caseRoles = array("Senior Services Coordinator", "Health Services Coordinator", "Benefits Specialist", "Client");
    $caseStatusLabel = "Ongoing";
    $subject = "Safe daytime setting - senior female";
    $this->select("medium_id", "value=1");
    $location = "Main offices";
    $this->type("activity_location", $location);
    $details = "65 year old female needs safe location during the day for herself and her dog. She is in good health but somewhat disoriented.";
    $this->fillRichTextField("activity_details", $details, 'CKEditor');
    $this->type("activity_subject", $subject);

    $this->select("case_type_id", "label={$caseTypeLabel}");
    $this->select("status_id", "label={$caseStatusLabel}");

    // Choose Case Start Date.
    // Using helper webtestFillDate function.
    $this->webtestFillDate('start_date', 'now');
    $today = date('F jS, Y', strtotime('now'));

    $this->type("duration", "20");
    $this->type("{$cusId_1}", $custFname);
    $this->type("{$cusId_2}", $custLname);
    $this->clickLink("_qf_Case_upload-bottom", "_qf_CaseView_cancel-bottom");

    // Is status message correct?
    $this->assertTextPresent("Case opened successfully.", "Save successful status message didn't show up after saving!");
    $this->clickLink("_qf_CaseView_cancel-bottom");

    $this->openCiviPage('case', 'reset=1');
    $this->waitForElementPresent("xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$contactName}']/../../td[8]/a[text()='Open Case']");    

    $this->click("xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$contactName}']/../../td[8]/a[text()='Open Case']");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Done']");

    $openCaseData = array(
      "Client" => $displayName,
      "Activity Type" => "Open Case",
      "Subject" => $subject,
      "Created By" => "{$testUserFirstName} {$testUserLastName}",
      "Reported By" => "{$testUserFirstName} {$testUserLastName}",
      "Medium" => "In Person",
      "Location" => $location,
      "Date and Time" => $today,
      "Status" => "Completed",
      "Priority" => "Normal",
    );
    // wait for elements to load
    foreach ($openCaseData as $label => $value) {
      $this->waitForElementPresent("xpath=//table/tbody/tr/td[text()='{$label}']/following-sibling::td");
    }
    $this->webtestVerifyTabularData($openCaseData);
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Done']");

    // verify if custom data is present
    $this->openCiviPage('case', 'reset=1');
    $this->clickLink("xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$contactName}']/../../td[9]/span/a[text()='Manage']", "css=#{$customGrp1} .crm-accordion-header");

    $this->click("css=#{$customGrp1} .crm-accordion-header");

    $cusId_1 = 'custom_' . $customId[0] . '_1';
    $cusId_2 = 'custom_' . $customId[1] . '_1';
    $this->click("css=#{$customGrp1} a.button");

    // wait for dialog element
    $this->waitForElementPresent("css=div.ui-dialog div.ui-dialog-titlebar");
    // check for dialog box Title
    $this->assertElementContainsText("css=div.ui-dialog div.ui-dialog-titlebar", 'Update Case Information');

    $custFname = "Miky" . substr(sha1(rand()), 0, 7);
    $custLname = "Kristy" . substr(sha1(rand()), 0, 7);
    $this->type("{$cusId_1}", $custFname);

    // Wait for rich text editor element
    $this->waitForElementPresent("css=div#cke_{$cusId_2}");

    $this->fillRichTextField("{$cusId_2}", $custLname, 'CKEditor');
    $this->click("_qf_CustomData_upload");
    // delete custom data
    $this->_testDeleteCustomData($customGrpId1, $customId);
  }

  function _testGetCustomFieldId($customGrpId1) {
    $customId = array();
    
    // Create a custom data to add in profile
    $field1 = "Note_Textarea" . substr(sha1(rand()), 0, 7);
    $field2 = "Note_Richtexteditor" . substr(sha1(rand()), 0, 7);

    // add custom fields for group 1
    $this->openCiviPage('admin/custom/group/field/add', array('reset' => 1, 'action' => 'add', 'gid' => $customGrpId1));
    $this->type("label", $field1);
    $this->select("data_type_0", "value=4");
    $this->select("data_type_1", "value=TextArea");
    $this->check("is_searchable");
    $this->clickLink("_qf_Field_next_new-bottom");

    $this->type("label", $field2);
    $this->select("data_type_0", "value=4");
    //$this->select("data_type_1", "value=TextArea");
    $this->select("data_type_1", "value=RichTextEditor");
    $this->check("is_searchable");
    $this->clickLink("_qf_Field_next_new-bottom");

    // get id of custom fields
    $this->openCiviPage("admin/custom/group/field", array('reset' => 1, 'action' => 'browse', 'gid' => $customGrpId1));
    $custom1 = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr[1]/td[8]/span/a[text()='Edit Field']/@href"));
    $custom1 = $custom1[1];
    array_push($customId, $custom1);
    $custom2 = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr[2]/td[8]/span/a[text()='Edit Field']/@href"));
    $custom2 = $custom2[1];
    array_push($customId, $custom2);

    return $customId;
  }  
  
  function _testDeleteCustomData($customGrpId1, $customId) {
    // delete all custom data
    $this->openCiviPage("admin/custom/group/field", array('action' => 'delete', 'reset' => '1', 'gid' => $customGrpId1, 'id' => $customId[0]));
    $this->clickLink("_qf_DeleteField_next-bottom");

    $this->openCiviPage("admin/custom/group/field", array('action' => 'delete', 'reset' => '1', 'gid' => $customGrpId1, 'id' => $customId[1]));
    $this->clickLink("_qf_DeleteField_next-bottom");

    $this->openCiviPage("admin/custom/group", "action=delete&reset=1&id=" . $customGrpId1);
    $this->clickLink("_qf_DeleteGroup_next-bottom");
  }
}

