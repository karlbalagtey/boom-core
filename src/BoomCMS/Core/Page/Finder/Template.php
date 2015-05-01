<?php

namespace BoomCMS\Core\Page\Finder;

use Boom\Template\Template;
use Boom\Model\Page as Model;

class Template extends \Boom\Finder\Filter
{
    /**
     *
     * @var \Boom\Template
     */
    protected $template;

    public function __construct(Template $template)
    {
        $this->template = $template;
    }

    public function execute(Model $query)
    {
        return $query->where('template_id', '=', $this->template->getId());
    }
}