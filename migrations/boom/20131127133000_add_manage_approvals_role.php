<?php

class Migration_Boom_20131127133000 extends Minion_Migration_Base
{

	public function up(Kohana_Database $db)
	{
		$db->query(null, "insert ignore into roles (name, description) values ('manage_approvals', 'View the list of pages pending approval')");
	}

	public function down(Kohana_Database $db)
	{
		$db->query(null, "delete from roles where name = 'manage_approvals'");
	}
}