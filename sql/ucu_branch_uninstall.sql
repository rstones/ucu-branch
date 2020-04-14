delete from civicrm_contact_type where name = 'Site';
delete from civicrm_contact_type where name = 'Department';

update civicrm_contact_type set label = 'Individual' where id = 1;
update civicrm_contact_type set is_active = 1 where id = 2;
update civicrm_contact_type set label = 'Organization' where id = 3;

delete from civicrm_relationship_type where name_a_b in ('Site of', 'Department of', 'Member of');
update civicrm_relationship_type set is_active = 1;

delete from civicrm_custom_group where name = 'UCU_Branch';
drop table if exists civicrm_value_ucu_branch_1;

delete from civicrm_option_value where option_group_id = 3 and name = 'Unknown';
