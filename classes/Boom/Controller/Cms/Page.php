<?php 

class Boom_Controller_Cms_Page extends Boom_Controller
{
	protected $viewDirectory = 'boom/editor/page';

	/**
	*
	* @var \Boom\Page
	*/
	public $page;

	public function before()
	{
		parent::before();

		$this->page = \Boom\Page\Factory::byId($this->request->param('id'));
	}

	public function action_add()
	{
		$this->_csrf_check() && $this->authorization('add_page', $this->page);

		$creator = new \Boom\Page\Creator($this->page, $this->person);
		$creator->setTemplateId($this->request->post('template_id'));
		$creator->setTitle($this->request->post('title'));
		$new_page = $creator->execute();

		// Add a default URL.
		// This needs to go into a class of some sort, not sure where though.
		// Don't want it as part of Page_Creator because we sometimes want to create the default URLs in this format.
		$prefix = ($this->page->children_url_prefix)? $this->page->children_url_prefix : $this->page->url()->location;
		$url = \Boom\Page\URL::fromTitle($prefix, $new_page->getTitle());
		\Boom\Page\URL::createPrimary($url, $new_page->getId());

		$this->log("Added a new page under " . $this->page->getTitle(), "Page ID: " . $new_page->getId());

		$this->response->body(json_encode(array(
			'url' => URL::site($url, $this->request),
			'id' => $new_page->getId(),
		)));
	}

	public function action_delete()
	{
		if ( ! ($this->page->wasCreatedBy($this->person) || $this->auth->logged_in('delete_page', $this->page) || $this->auth->logged_in('manage_pages')) || $this->page->mptt->is_root())
		{
			throw new HTTP_Exception_403;
		}

		if ($this->request->method() === Request::GET)
		{
			$finder = new \Boom\Finder\Page;
			$finder->addFilter(new \Boom\Finder\Page\Filter\ParentId($this->page->getId()));
			$children = $finder->count();

			// Get request
			// Show a confirmation dialogue warning that child pages will become inaccessible and asking whether to delete the children.
			$this->template = new View("$this->viewDirectory/delete", array(
				'count' => $children,
				'page' =>$this->page,
			));
		}
		else
		{
			$this->_csrf_check();
			$this->log("Deleted page " . $this->page->getTitle() . " (ID: " . $this->page->getId() . ")");

			// Redirect to the parent page after we've finished.
			$this->response->body($this->page->parent()->url());

			$commander = new \Boom\Page\Commander($this->page);
			$commander
				->addCommand(new \Boom\Page\Delete\FromFeatureBoxes)
				->addCommand(new \Boom\Page\Delete\FromLinksets);

			($this->request->post('with_children') == 1) && $commander->addCommand(new \Boom\Page\Delete\Children);

			$commander->addCommand(new \Boom\Page\Delete\FlagDeleted());
			$commander->execute();
		}
	}

	public function action_discard()
	{
		$this->page->remove_drafts();
	}

	/**
	 * Reverts the current page to the last published version.
	 *
	 * @uses	Model_Page::stash()
	 */
	public function action_stash()
	{
		$this->page->stash();
	}

	public function action_urls()
	{
		$this->template = new View("$this->viewDirectory/urls", array(
			'page' => $this->page,
			'urls' => $this->page->getUrls(),
		));
	}
}