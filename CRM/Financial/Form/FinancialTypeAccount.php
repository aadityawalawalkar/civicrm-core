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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for Financial Type Account
 *
 */
class CRM_Financial_Form_FinancialTypeAccount extends CRM_Contribute_Form {

  /**
   * the financial type id saved to the session for an update
   *
   * @var int
   * @access protected
   */
  protected $_aid;

  /**
   * The financial type accounts id, used when editing the field
   *
   * @var int
   * @access protected
   */
  protected $_id;

  /**
   * The name of the BAO object for this form
   *
   * @var string
   */
  protected $_BAOName;

  /**
   * Flag if its a AR account type
   *
   * @var boolean
   */
  protected $_isARFlag = FALSE;
  
  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $this->_aid = CRM_Utils_Request::retrieve('aid', 'Positive', $this);
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    if (!$this->_id && ($this->_action & CRM_Core_Action::UPDATE)) {
      $this->_id = CRM_Utils_Type::escape($this->_id, 'Positive');
    }
    $url = CRM_Utils_System::url('civicrm/admin/financial/financialType/accounts', 
      "reset=1&action=browse&aid={$this->_aid}"); 
      
    $this->_BAOName = 'CRM_Financial_BAO_FinancialTypeAccount';
    if ($this->_aid && ($this->_action & CRM_Core_Action::ADD)) {
      $this->_title = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', $this->_aid, 'name');
      CRM_Utils_System::setTitle($this->_title . ' - ' . ts('Financial Accounts'));

      $session = CRM_Core_Session::singleton(); 
      $session->pushUserContext($url);
    } 
    // CRM-12492
    if (!($this->_action & CRM_Core_Action::ADD)) { 
      $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Accounts Receivable Account is' "));
      $accountRelationship = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_EntityFinancialAccount', $this->_id, 'account_relationship');
      if ($accountRelationship == $relationTypeId) {
        $this->_isARFlag = TRUE;
        if ($this->_action & CRM_Core_Action::DELETE) {
          CRM_Core_Session::setStatus(ts("Selected financial type account with 'Accounts Receivable Account is' account relationship cannot be deleted."), 
            '', 'error');
          CRM_Utils_System::redirect($url);
        }
      }
    }
    if ($this->_id) {
      $financialAccount = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_EntityFinancialAccount', $this->_id, 'financial_account_id');
      $fieldTitle = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialAccount', $financialAccount, 'name');
      CRM_Utils_System::setTitle($fieldTitle . ' - '. ts('Financial Type Accounts'));
    }

    $breadCrumb = array(
      array('title' => ts('Financial Type Accounts'),
        'url' => $url,
      )
    );
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Delete Financial Account Type'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'))
        )
      );
      return;
    }

    parent::buildQuickForm();

    if (isset($this->_id)) {
      $params = array('id' => $this->_id);
      CRM_Financial_BAO_FinancialTypeAccount::retrieve($params, $defaults);
      $this->setDefaults($defaults);
      $financialAccountTitle = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialAccount', $defaults['financial_account_id'], 'name');
    }

    $this->applyFilter('__ALL__', 'trim');

    if ($this->_action == CRM_Core_Action::UPDATE) {
      $this->assign('aid', $this->_id);
      //hidden field to catch the group id in profile
      $this->add('hidden', 'financial_type_id', $this->_aid);

      //hidden field to catch the field id in profile
      $this->add('hidden', 'account_type_id', $this->_id);
    }
    $AccountTypeRelationship = CRM_Core_PseudoConstant::accountOptionValues('account_relationship');
    if (!empty($AccountTypeRelationship)) {
      $element = $this->add('select',
        'account_relationship',
        ts('Financial Account Relationship'),
        array('select' => '- select -') + $AccountTypeRelationship,
        TRUE
      );
    }

    if ($this->_isARFlag) {
      $element->freeze();
    }

    if ($this->_action == CRM_Core_Action::ADD) {
      if (CRM_Utils_Array::value('account_relationship', $this->_submitValues) || CRM_Utils_Array::value('financial_account_id', $this->_submitValues)) {
        $financialAccountType = array(
           '5' => 5, //expense
           '3' => 1, //AR relation
           '1' => 3, //revenue
           '6' => 1,  //Asset
           '7' => 4, //cost of sales
           '8' => 1, //premium inventory
           '9' => 3 //discount account is
          );

        $financialAccountType = $financialAccountType[$this->_submitValues['account_relationship']];
        $result = CRM_Contribute_PseudoConstant::financialAccount(NULL, $financialAccountType);

        $financialAccountSelect = array('' => ts('- select -')) + $result;
      }
      else {
        $financialAccountSelect = array(
          'select' => ts('- select -')
        ) + CRM_Contribute_PseudoConstant::financialAccount();
      }
    }
    if ($this->_action == CRM_Core_Action::UPDATE) {
      $financialAccountType = array(
        '5' => 5, //expense
        '3' => 1, //AR relation
        '1' => 3, //revenue
        '6' => 1,  //Asset
        '7' => 4, //cost of sales
        '8' => 1, //premium inventory
        '9' => 3 //discount account is
       );

      $financialAccountType = $financialAccountType[$this->_defaultValues['account_relationship']];
      $result = CRM_Contribute_PseudoConstant::financialAccount(NULL, $financialAccountType);

      $financialAccountSelect = array('' => ts('- select -')) + $result;

    }
    $this->add('select',
      'financial_account_id',
      ts('Financial Account'),
      $financialAccountSelect,
      TRUE
    );

    $this->addButtons(array(
      array(
        'type'      => 'next',
        'name'      => ts('Save'),
        'isDefault' => TRUE
      ),
      array(
        'type'      => 'next',
        'name'      => ts('Save and New'),
        'subName'   => 'new'
      ),
      array (
        'type'      => 'cancel',
        'name'      => ts('Cancel')
      ))
    );
    $this->addFormRule(array('CRM_Financial_Form_FinancialTypeAccount', 'formRule'), $this);
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($values, $files, $self) {
    $errorMsg = array();
    $errorFlag = FALSE;
    if ($self->_action == CRM_Core_Action::DELETE) {
      $groupName = 'account_relationship';
      $relationValues = CRM_Core_PseudoConstant::accountOptionValues($groupName);
      if (CRM_Utils_Array::value('financial_account_id', $values) != 'select') {
        if ($relationValues[$values['account_relationship']] == 'Premiums Inventory Account is' || $relationValues[$values['account_relationship']] == 'Cost of Sales Account is') {
          $premiumsProduct = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_PremiumsProduct', $values['financial_type_id'], 'product_id', 'financial_type_id');
          $product = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Product', $values['financial_type_id'], 'name', 'financial_type_id');
          if (!empty($premiumsProduct) || !empty($product)) {
            $errorMsg['account_relationship'] = 'You cannot remove ' . $relationValues[$values['account_relationship']] . ' relationship while the Financial Type is used for a Premium.';
          }
        }
      }
    }
    if (CRM_Utils_Array::value('account_relationship', $values) == 'select') {
      $errorMsg['account_relationship'] = 'Financial Account relationship is a required field.';
    }
    if (CRM_Utils_Array::value('financial_account_id', $values) == 'select') {
      $errorMsg['financial_account_id'] = 'Financial Account is a required field.';
    }
    if (CRM_Utils_Array::value('account_relationship', $values) && CRM_Utils_Array::value('financial_account_id', $values)) {
      $params = array(
        'account_relationship' => $values['account_relationship'],
        'entity_id'            => $self->_aid
      );
      $defaults = array();
      if ($self->_action == CRM_Core_Action::ADD) {
        $result = CRM_Financial_BAO_FinancialTypeAccount::retrieve($params, $defaults);
        if ($result) {
          $errorFlag = TRUE;
        }
      }
      if ($self->_action == CRM_Core_Action::UPDATE) {
        if ($values['account_relationship'] == $self->_defaultValues['account_relationship'] && $values['financial_account_id'] == $self->_defaultValues['financial_account_id']) {
          $errorFlag = FALSE;
        }
        else {
          $params['financial_account_id'] = $values['financial_account_id'];
          $result = CRM_Financial_BAO_FinancialTypeAccount::retrieve($params, $defaults);
          if ($result) {
            $errorFlag = TRUE;
          }
        }
      }

      if ($errorFlag) {
        $errorMsg['account_relationship'] = ts('This account relationship already exits');
      }
    }
    return CRM_Utils_Array::crmIsEmptyArray($errorMsg) ? TRUE : $errorMsg;
  }

  /**
   * Function to process the form
   *
   * @access public
   * @return None
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Financial_BAO_FinancialTypeAccount::del($this->_id, $this->_aid);
      CRM_Core_Session::setStatus(ts('Selected financial type account has been deleted.'));
    }
    else {
      $params = $ids = array();
      // store the submitted values in an array
      $params = $this->exportValues();

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $ids['entityFinancialAccount'] = $this->_id;
      }
      if ($this->_action & CRM_Core_Action::ADD || $this->_action & CRM_Core_Action::UPDATE) {
        $params['financial_account_id'] = $this->_submitValues['financial_account_id'];
      }
      $params['entity_table'] = 'civicrm_financial_type';
      if ($this->_action & CRM_Core_Action::ADD) {
        $params['entity_id'] = $this->_aid;
      }
      $financialTypeAccount = CRM_Financial_BAO_FinancialTypeAccount::add($params, $ids);
      CRM_Core_Session::setStatus(ts('The financial type Account has been saved.'));
    }

    $buttonName = $this->controller->getButtonName();
    $session = CRM_Core_Session::singleton();

    if ($buttonName == $this->getButtonName('next', 'new')) {
      CRM_Core_Session::setStatus(ts(' You can add another Financial Account Type.'));
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/financial/financialType/accounts',
        "reset=1&action=add&aid={$this->_aid}"));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/financial/financialType/accounts',
        "reset=1&action=browse&aid={$this->_aid}"));
    }
  }
}


