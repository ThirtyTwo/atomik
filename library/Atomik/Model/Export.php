<?php

class Atomik_Model_Export
{
	public function export(Atomik_Model_Descriptor $descriptor)
	{
		$definition = new Atomik_Db_Definition($descriptor->getSession()->getDbInstance());
		$definition->dropBeforeCreate();
		
		$tableName = $descriptor->getTableName();
		$table = $definition->table($tableName);
		
		foreach ($descriptor->getFields() as $field) {
			$column = $table->createColumn($field->getColumnName(), $field->getType());
			
			if ($descriptor->getPrimaryKeyField() == $field) {
				$table->primaryKey($field->getColumnName());
				$column->options['auto-increment'] = true;
			}
			
			if ($descriptor->isFieldPartOfAssociation($field)) {
				$table->index($field->getColumnName());
			}
		}
		
		$descriptor->getSession()->notify('BeforeExport', $descriptor, $definition);
		$sql = $definition->toSql();
		$descriptor->getSession()->notify('AfterExport', $descriptor, $sql);
		
		return $sql;
	}
}