SELECT id INTO @mapping_id from civicrm_mapping where name = 'UCU Member Import Mapping';

DELETE FROM civicrm_mapping_field WHERE mapping_id = @mapping_id;

DELETE FROM civicrm_mapping WHERE id = @mapping_id;
