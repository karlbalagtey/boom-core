<?php

namespace BoomCMS\Core\Page\Finder;

use Boom;
use BoomCMS\Core\Editor\Editor;
use BoomCMS\Core\Model\Page as Model;

class Finder extends Boom\Finder\Finder
{
    const TITLE = 'version.title';
    const MANUAL = 'sequence';
    const DATE = 'visible_from';
    const EDITED = 'edited_time';

    public function __construct(Editor $editor = null)
    {
        $editor = $editor ?: Editor::instance();

        $this->_query = \ORM::factory('Page')
            ->where('deleted', '=', false)
            ->with_current_version($editor)
            ->where('page.primary_uri', '!=', null);
    }

    public function find()
    {
        $model = parent::find();

        return new Page($model);
    }

    public function findAll()
    {
        $pages = parent::findAll()->as_array();

        array_walk($pages, function (&$page) {
           $page = new Page($page);
        });

        return $pages;
    }
}
