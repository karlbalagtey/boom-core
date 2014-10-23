<?php

namespace Boom\Page\Command\Delete;

use \Boom\Page\Page as Page;
use \DB as DB;

class FromLinkSets extends \Boom\Page\Command
{
	public function execute(Page $page)
	{
		DB::delete('chunk_linkset_links')
			->where('target_page_id', '=', $page->getId())
			->execute();
	}
}