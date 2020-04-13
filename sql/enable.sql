update civicrm_contact_type set is_active = 1 where name = 'Site';
update civicrm_contact_type set is_active = 1 where name = 'Department';

update civicrm_contact_type set label = 'Member' where id = 1;
update civicrm_contact_type set is_active = 0 where id = 2;
update civicrm_contact_type set label = 'Branch' where id =  3;

update civicrm_relationship_type set is_active = 0 where name_a_b not in ('Site of', 'Department of', 'Member of');
update civicrm_relationship_type set is_active = 1 where name_a_b in ('Site of', 'Department of', 'Member of');
