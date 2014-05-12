<?php

namespace Boom\Finder\Page\Filter;

use \Boom\Finder as Finder;

class RelatedByTags extends Finder\Filter
{
	/**
	 *
	 * @var array
	 */
	protected $_tagIds;

	/**
	 *
	 * @var \Boom\Page
	 */
	protected $_page;

	public function __construct(\Boom\Page $page)
	{
		$this->_page = $page;
		$this->_tagIds = $this->_getTagIds();
	}

	public function execute(\ORM $query)
	{
		return $query
			->select(array(DB::expr('count(pages_tags.tag_id)'), 'tag_count'))
			->join('pages_tags', 'inner')
			->on('page.id', '=', 'pages_tags.page_id')
			->where('tag_id', 'in', $tag_ids)
			->where('page.id', '!=', $this->_page->id)
			->order_by('tag_count', 'desc')
			->order_by(DB::expr('rand()'))
			->group_by('page.id');
	}

	/**
	 * TODO: This should probably be in a \Boom\Page\Tags class
	 */
	protected function _getTagIds()
	{
		$results = \DB::select('tag_id')
			->from('pages_tags')
			->where('page_id', '=', $this->_page->id)
			->execute();

		return \Arr::pluck($results, 'tag_id');
	}

	public function shouldBeApplied()
	{
		return \count($this->_tagIds) > 0;
	}
}