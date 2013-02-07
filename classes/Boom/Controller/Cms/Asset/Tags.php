<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Controller for viewing and editing asset tags.
 *
 * @package BoomCMS
 * @category Controllers
 * @author Rob Taylor
 * @copyright	Hoop Associates
 */
class Boom_Controller_Cms_Asset_Tags extends Controller_Cms_Tags
{
	public function before()
	{
		parent::before();

		$this->model = new Model_Asset($this->request->param('id'));

		$this->authorization('manage_assets');
	}
} // End Boom_Controller_Cms_Asset_Tags