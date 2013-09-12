<?php defined('SYSPATH') OR die('No direct script access.');

/**
  * @package	BoomCMS
  * @category	Assets
  * @category	Controllers
  */
class Boom_Controller_Cms_Assets extends Boom_Controller
{
	/**
	 *
	 * @var	string	Directory where the view files used in this class are stored.
	 */
	protected $_view_directory = 'boom/assets';

	/**
	 *
	 * @var Model_Asset
	 */
	public $asset;

	/**
	 * Check that they can manage assets.
	 */
	public function before()
	{
		parent::before();

		// Permissions check.
		$this->authorization('manage_assets');

		// Instantiate an asset model.
		$this->asset = new Model_Asset($this->request->param('id'));
	}

	/**
	 * Delete multiple assets at a time.
	 *
	 * Takes an array if asset IDs and calls [Model_Asset::delete()] on each one.
	 *
	 * @uses	Model_Asset::delete()
	 * @uses	Boom_Controller::log()
	 */
	public function action_delete()
	{
		$this->_csrf_check();

		if ($this->asset->loaded())
		{
			$this->asset->delete();
		}

		$asset_ids = array_unique((array) $this->request->post('assets'));

		foreach ($asset_ids as $asset_id)
		{
			$this->asset
				->where('id', '=', $asset_id)
				->find();

			if ( ! $this->asset->loaded())
			{
				// Move along, nothing to see here.
				continue;
			}

			$this->log("Deleted asset $this->asset->title (ID: $this->asset->id)");

			$this->asset
				->delete()
				->clear();
		}
	}

	/**
	 * Display the asset manager.
	 *
	 */
	public function action_index()
	{
		$this->template = View::factory("$this->_view_directory/index", array(
			'manager'	=>	Request::factory('cms/assets/manager')->execute()->body(),
			'person'	=>	$this->person,
		));
	}

	public function action_list()
	{
		// Get the query data.
		$query_data = $this->request->query();

		// Load the query data into variables.
		$page		=	Arr::get($query_data, 'page', 1);
		$perpage		=	Arr::get($query_data, 'perpage', 30);
		$tag		=	Arr::get($query_data, 'tag');
		$type		=	Arr::get($query_data, 'type');
		$sortby		=	Arr::get($query_data, 'sortby');
		$title			=	Arr::get($query_data, 'title');

		// Prepare the database query.
		$query = DB::select()
			->from('assets');

		// If a tag paramater was given in the query data then turn it into an array of tag IDs.
		if ($tag)
		{
			$tags = explode("-", Arr::get($query_data, 'tag'));
		}

		// If a valid tag was given then filter the results by tag..
		if (isset($tags) AND ! empty($tags))
		{
			$query
				->join(array('assets_tags', 't1'), 'inner')
				->on('assets.id', '=', 't1.asset_id')
				->distinct(TRUE);

			if (($tag_count = count($tags)) > 1)
			{
				// Get assets which are assigned to all of the given tags.
				$query
					->join(array('assets_tags', 't2'), 'inner')
					->on("t1.asset_id", '=', "t2.asset_id")
					->where('t2.tag_id', 'IN', $tags)
					->group_by("t1.asset_id")
					->having(DB::expr('count(distinct t2.tag_id)'), '>=', $tag_count);
			}
			else
			{
				// Filter by a single tag.
				$query->where('t1.tag_id', '=', $tags[0]);
			}
		}

		// Filtering by title?
		if ($title)
		{
			$query->where('title', 'like', "%$title%");
		}

		$column = 'last_modified';
		$order = 'desc';

		if ( strpos( $sortby, '-' ) > 1 ){
			$sort_params = explode( '-', $sortby );
			$column = $sort_params[0];
			$order = $sort_params[1];
		}

		if (($column == 'last_modified' OR $column == 'title' OR $column == 'filesize') AND ($order == 'desc' OR $order == 'asc'))
		{
			$query->order_by($column, $order);
		}
		else
		{
			$query->order_by('title', 'asc');
		}

		// Apply an asset type filter.
		if ($type)
		{
			// Filtering by asset type.
			$query->where('assets.type', '=', constant('Boom_Asset::' . strtoupper($type)));
		}

		// Clone the query to count the number of matching assets and their total size.
		$query2 = clone $query;
		$result = $query2
			->select(array(DB::expr('sum(filesize)'), 'filesize'))
			->select(array(DB::expr('count(*)'), 'total'))
			->execute();

		// Get the asset count and total size from the result
		$size = $result->get('filesize');
		$total = $result->get('total');

		// Were any assets found?
		if ($total === 0)
		{
			// Nope, show a message explaining that we couldn't find anything.
			$this->template = View::factory("$this->_view_directory/none_found");
		}
		else
		{
			// Retrieve the results and load Model_Asset classes
			$assets = $query
				->select('assets.*')
				->limit($perpage)
				->offset(($page - 1) * $perpage)
				->as_object('Model_Asset')
				->execute();

			// Put everthing in the views.
			$this->template = View::factory("$this->_view_directory/list", array(
				'assets'		=>	$assets,
				'total_size'	=>	$size,
				'total'		=>	$total,
				'order'		=>	$order,
			));

			// How many pages are there?
			$pages = ceil($total / $perpage);

			if ($pages > 1)
			{
				// More than one page - generate pagination links.
				$url = '';
				$pagination = View::factory('pagination/query', array(
					'current_page'		=>	$page,
					'total_pages'		=>	$pages,
					'base_url'			=>	$url,
					'previous_page'		=>	$page - 1,
					'next_page'		=>	($page == $pages) ? 0 : ($page + 1),
				));

				// Add the pagination view to the main view.
				$this->template->set('pagination', $pagination);
			}
		}
	}

	/**
	 * Display the asset manager without topbar etc.
	 *
	 */
	public function action_manager()
	{
		$this->template = View::factory("$this->_view_directory/manager");
	}

	public function action_restore()
	{
		$timestamp = $this->request->query('timestamp');

		if (file_exists($this->asset->get_filename().".".$timestamp.".bak"))
		{
			// Backup the current active file.
			@rename($this->asset->get_filename(), $this->asset->get_filename().".".$_SERVER['REQUEST_TIME'].".bak");

			// Restore the old file.
			@copy($this->asset->get_filename().".".$timestamp.".bak", $this->asset->get_filename());
		}

		$this->asset
			->delete_cache_files()
			->set('last_modified', $_SERVER['REQUEST_TIME'])
			->update();

		// Go back to viewing the asset.
		$this->redirect('/cms/assets/#asset/'.$this->asset->id);
	}

	public function action_save()
	{
		$this->_csrf_check();

		if ( ! $this->asset->loaded())
		{
			throw new HTTP_Exception_404;
		}

		$this->asset
			->values($this->request->post(), array('title','description','visible_from', 'thumbnail_asset_id', 'credits'))
			->update();
	}

	public function action_view()
	{
		if ( ! $this->asset->loaded())
		{
			throw new HTTP_Exception_404;
		}

		$this->template = View::factory("$this->_view_directory/view", array(
			'asset'	=>	$this->asset,
			'tags'	=>	$this->asset
				->tags
				->order_by('name', 'asc')
				->find_all()
		));
	}
}