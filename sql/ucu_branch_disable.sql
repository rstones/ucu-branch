update civicrm_contact_type set is_active = 0 where name = 'Site';
update civicrm_contact_type set is_active = 0 where name = 'Department';

update civicrm_contact_type set label = 'Individual' where id = 1;
update civicrm_contact_type set is_active = 1 where id = 2;
update civicrm_contact_type set label = 'Organization' where id = 3;

update civicrm_relationship_type set is_active = 1 where name_a_b not in ('Site of', 'Department of', 'Member of');
update civicrm_relationship_type set is_active = 0 where name_a_b in ('Site of', 'Department of', 'Member of');

update civicrm_custom_group set is_active = 0 where name = 'UCU_Branch';

update civicrm_option_value set is_active = 0 where option_group_id = 3 and name = 'Unknown';

update civicrm_tag set is_selectable = 0 where name = 'Missing Site/Dept info';
