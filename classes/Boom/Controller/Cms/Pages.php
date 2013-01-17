<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Controller for the page manager.
 * Allows viewing a list of the pages in the CMS and, in the future, doing stuff with that list.
 *
 * Not to be confused with [Controller_Cms_Page] which is used for editing a single page.
 *
 * @package	BoomCMS
 * @category	Controllers
 * @author	Rob Tayor
 *
 */
class Boom_Controller_Cms_Pages extends Boom_Controller
{
	/**
	 * Check that they can manage templates.
	 */
	public function before()
	{
		parent::before();

		// Permissions check
		$this->authorization('manage_pages');
	}

	/**
	 * Display a list of all the pages in the CMS.
	 */
	public function action_index()
	{
		$pages = ORM::factory('Page')
			->join('page_mptt', 'inner')
			->on('page.id', '=', 'page_mptt.id')
			->order_by('lft', 'asc')
			->find_all();

		$this->template = View::factory('boom/pages/index', array(
			'pages'	=>	$pages,
		));
	}
}
