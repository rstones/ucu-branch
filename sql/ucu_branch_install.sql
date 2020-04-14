insert ignore into civicrm_contact_type (name, label, parent_id, is_active)
values ('Site', 'Site', 3, 1);

insert ignore into civicrm_contact_type (name, label, parent_id, is_active)
values ('Department', 'Department', 3, 1);

update civicrm_contact_type set label = 'Member' where id = 1;
update civicrm_contact_type set is_active = 0 where id = 2;
update civicrm_contact_type set label = 'Branch' where id = 3;

update civicrm_relationship_type set is_active = 0 where is_active = 1;

insert ignore into civicrm_relationship_type (name_a_b, label_a_b, name_b_a, label_b_a, description, contact_type_a, contact_type_b, contact_sub_type_a) values ('Site of', 'Site of', 'Branch of', 'Branch of', 'Branch/site relationship', 'Organization', 'Organization', 'Site');

insert ignore into civicrm_relationship_type (name_a_b, label_a_b, name_b_a, label_b_a, description, contact_type_a, contact_type_b, contact_sub_type_a, contact_sub_type_b) values ('Department of', 'Department of', 'Site of', 'Site of', 'Site/department relationship', 'Organization', 'Organization', 'Department', 'Site');

insert ignore into civicrm_relationship_type (name_a_b, label_a_b, name_b_a, label_b_a, description, contact_type_a, contact_type_b, contact_sub_type_b) values ('Member of', 'Member of', 'Department of', 'Department of', 'Department/member relationship', 'Individual', 'Organization', 'Department');

insert ignore into civicrm_option_value (option_group_id, label, value, name, weight) values (3, 'Unknown', '4', 'Unknown', 4);
