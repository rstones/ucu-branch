<?php

require_once 'ucu_branch.civix.php';
use CRM_UcuBranch_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/ 
 */
function ucu_branch_civicrm_config(&$config) {
  _ucu_branch_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function ucu_branch_civicrm_xmlMenu(&$files) {
  _ucu_branch_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function ucu_branch_civicrm_install() {
  _ucu_branch_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function ucu_branch_civicrm_postInstall() {
  _ucu_branch_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function ucu_branch_civicrm_uninstall() {
  _ucu_branch_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function ucu_branch_civicrm_enable() {
  _ucu_branch_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function ucu_branch_civicrm_disable() {
   _ucu_branch_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function ucu_branch_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _ucu_branch_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function ucu_branch_civicrm_managed(&$entities) {
  _ucu_branch_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_import()
 */
function ucu_branch_civicrm_import($object, $usage, &$objectRef, &$params) {
    Civi::log()->debug('ucu_branch_civicrm_import was called!');
    Civi::log()->debug(print_r($objectRef->_contactType, 1));
    Civi::log()->debug(print_r($objectRef->_contactSubType, 1));
    //Civi::log()->debug(print_r($params, 1));

    // the contact type and sub-type are in $objectRef
    // $objectRef->_contactType and $objectRef->_contactSubType
    //
    // the data being imported is in $params
    //
    // hook_civicrm_import is called once for each non-duplicate record
    // being import so don't need to loop over multiple Sites/Depts etc


    // if importing Contact subtype Site construct relationships to Branch
    if ($objectRef->_contactType == 'Organization') {
        if ($objectRef->_contactSubType == 'Site') {
            // set up 'Site of' relationships to Branch
            // create Members and Reps group + ACLs
            Civi::log()->debug('Processing Site...');

            $contactID = $params['contactID'];
            $relID = \Civi\Api4\RelationshipType::get()
                ->addWhere('name_a_b', '=', 'Site of')
                ->execute()
                ->first()['id'];
            
            $siteFieldIdx = array_search('organization_name', $params['fieldHeaders']);
            $site = $params['fields'][$siteFieldIdx]->_value;

            $branchID = \Civi\Api4\Contact::get()
                ->addWhere('contact_type', '=', 'Organization')
                ->addWhere('contact_sub_type', 'IS NULL')
                ->execute()
                ->first()['id'];

            $results = \Civi\Api4\Relationship::create()
              ->addValue('contact_id_a', $contactID)
              ->addValue('contact_id_b', $branchID)
              ->addValue('relationship_type_id', $relID)
              ->execute();
            
            // create groups for access control
            $membersGroup = \Civi\Api4\Group::create()
                ->addValue('name', $site . ' Members')
                ->addValue('title', $site . ' Members')
                ->addValue('group_type', 1)
                ->execute();
            $membersGroupID = \Civi\Api4\Group::get()
                ->addWhere('name', '=', $site . ' Members')
                ->execute()
                ->first()['id'];

            $repsGroup = \Civi\Api4\Group::create()
                ->addValue('name', $site . ' Reps')
                ->addValue('title', $site . ' Reps')
                ->addValue('group_type', 1)
                ->execute();
            $repsGroupID = \Civi\Api4\Group::get()
                ->addWhere('name', '=', $site . ' Reps')
                ->execute()
                ->first()['id'];
            
            // Create ACL...
            $aclRoleID = \Civi\Api4\OptionGroup::get()
                ->addWhere('name', '=', 'acl_role')
                ->execute()
                ->first()['id'];
            $prevOptVal = \Civi\Api4\OptionValue::get()
                ->addWhere('option_group_id', '=', $aclRoleID)
                ->addOrderBy('id', 'ASC')
                ->execute()
                ->last()['value'];
            $result = \Civi\Api4\OptionValue::create()
                ->addValue('option_group_id', $aclRoleID)
                ->addValue('label', $site . ' Rep')
                ->addValue('name', $site . ' Rep')
                ->addValue('value', $prevOptVal+1)
                ->addValue('weight', $prevOptVal+1)
                ->execute();

            // then create ACL entity role for the Reps group
            // by inserting into civicrm_acl_entity_role
            $results = \Civi\Api4\EntityRole::create()
                ->addValue('acl_role_id', $prevOptVal+1)
                ->addValue('entity_table', 'civicrm_group')
                ->addValue('entity_id', $repsGroupID)
                ->addValue('is_active', 1)
                ->execute();
            
            // finally insert into civicrm_acl to give Reps group
            // edit permissions on Members group
            $results = \Civi\Api4\ACL::create()
                ->addValue('entity_table', 'civicrm_acl_role')
                ->addValue('entity_id', $prevOptVal+1)
                ->addValue('operation', 'Edit')
                ->addValue('object_table', 'civicrm_saved_search')
                ->addValue('object_id', $membersGroupID)
                ->addValue('name', 'Edit ' . $site . ' members')
                ->execute();

        }
        else if ($objectRef->_contactSubType == 'Department') {
            // set up 'Department of' relationships to Sites
            // will need to access the do_not_import field in $params for this
            // create Members and Reps groups + ACLs
            Civi::log()->debug('Processing Department...');
            
            $contactID = $params['contactID'];
            $relID = \Civi\Api4\RelationshipType::get()
                ->addWhere('name_a_b', '=', 'Department of')
                ->execute()
                ->first()['id'];
            
            $deptFieldIdx = array_search('organization_name', $params['fieldHeaders']);
            $dept = $params['fields'][$deptFieldIdx]->_value;

            $siteFieldIdx = array_search('do_not_import', $params['fieldHeaders']);
            $site = $params['fields'][$siteFieldIdx]->_value;

            $siteID = \Civi\Api4\Contact::get()
                ->addWhere('organization_name' ,'=', $site)
                ->execute()
                ->first()['id'];

            $results = \Civi\Api4\Relationship::create()
              ->addValue('contact_id_a', $contactID)
              ->addValue('contact_id_b', $siteID)
              ->addValue('relationship_type_id', $relID)
              ->execute();

            // create groups for access control
            $membersGroup = \Civi\Api4\Group::create()
                ->addValue('name', $dept . ' Members')
                ->addValue('title', $dept . ' Members')
                ->addValue('group_type', 1)
                ->execute();
            $membersGroupID = \Civi\Api4\Group::get()
                ->addWhere('name', '=', $dept . ' Members')
                ->execute()
                ->first()['id'];

            $repsGroup = \Civi\Api4\Group::create()
                ->addValue('name', $dept . ' Reps')
                ->addValue('title', $dept . ' Reps')
                ->addValue('group_type', 1)
                ->execute();
            $repsGroupID = \Civi\Api4\Group::get()
                ->addWhere('name', '=', $dept . ' Reps')
                ->execute()
                ->first()['id'];

            // now create ACLs...
            //
            // 1st create and ACL role by inserting into
            // civicrm_option_value linking to the acl_role option group
            $aclRoleID = \Civi\Api4\OptionGroup::get()
                ->addWhere('name', '=', 'acl_role')
                ->execute()
                ->first()['id'];
            $prevOptVal = \Civi\Api4\OptionValue::get()
                ->addWhere('option_group_id', '=', $aclRoleID)
                ->addOrderBy('id', 'ASC')
                ->execute()
                ->last()['value'];
            $result = \Civi\Api4\OptionValue::create()
                ->addValue('option_group_id', $aclRoleID)
                ->addValue('label', $dept . ' Rep')
                ->addValue('name', $dept . ' Rep')
                ->addValue('value', $prevOptVal+1)
                ->addValue('weight', $prevOptVal+1)
                ->execute();

            // then create ACL entity role for the Reps group
            // by inserting into civicrm_acl_entity_role
            $results = \Civi\Api4\EntityRole::create()
                ->addValue('acl_role_id', $prevOptVal+1)
                ->addValue('entity_table', 'civicrm_group')
                ->addValue('entity_id', $repsGroupID)
                ->addValue('is_active', 1)
                ->execute();
            
            // finally insert into civicrm_acl to give Reps group
            // edit permissions on Members group
            $results = \Civi\Api4\ACL::create()
                ->addValue('entity_table', 'civicrm_acl_role')
                ->addValue('entity_id', $prevOptVal+1)
                ->addValue('operation', 'Edit')
                ->addValue('object_table', 'civicrm_saved_search')
                ->addValue('object_id', $membersGroupID)
                ->addValue('name', 'Edit ' . $dept . ' members')
                ->execute();
        }
    }
    else if ($objectRef->_contactType == 'Individual') {
        // find Site + Department from address fields
        // create 'Member of' relationship with department
        // add to <Department> Members group
        
    }

}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function ucu_branch_civicrm_caseTypes(&$caseTypes) {
  _ucu_branch_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function ucu_branch_civicrm_angularModules(&$angularModules) {
  _ucu_branch_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function ucu_branch_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _ucu_branch_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function ucu_branch_civicrm_entityTypes(&$entityTypes) {
  _ucu_branch_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function ucu_branch_civicrm_themes(&$themes) {
  _ucu_branch_civix_civicrm_themes($themes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 *
function ucu_branch_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 *
function ucu_branch_civicrm_navigationMenu(&$menu) {
  _ucu_branch_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _ucu_branch_civix_navigationMenu($menu);
} // */
