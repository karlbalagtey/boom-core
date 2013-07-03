<?php defined('SYSPATH') or die('No direct script access.');

class Migration_Boom_20130703141100 extends Minion_Migration_Base
{
	public function up(Kohana_Database $db)
	{
		$db->query(NULL, "alter table assets drop encoded, drop views");
	}

	public function down(Kohana_Database $db)
	{
		$db->query(NULL, "alter table assets add encoded boolean default false, add views int unsigned default 0");
	}
}
