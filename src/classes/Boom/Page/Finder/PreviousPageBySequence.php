<?php

namespace Boom\Page\Finder;

use Boom\Page\Page;
use ORM;

class PreviousPageBySequence extends \Boom\Finder\Filter
{
    /**
	 *
	 * @var \Boom\Page
	 */
    protected $currentPage;

    public function __construct(Page $currentPage)
    {
        $this->currentPage = $currentPage;
    }

    public function execute(ORM $query)
    {
        return $query
            ->where('sequence', '<', $this->currentPage->getManualOrderPosition())
            ->order_by('sequence', 'desc');
    }
}