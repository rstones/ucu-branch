<?php
use CRM_UcuBranch_ExtensionUtil as E;

class CRM_UcuBranch_Page_ViewMembers extends CRM_Core_Page {

  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(E::ts('View Members'));

    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));
    $this->assign('loggedInMemberID', CRM_Core_Session::singleton()->getLoggedInContactID());

    parent::run();
  }

}
