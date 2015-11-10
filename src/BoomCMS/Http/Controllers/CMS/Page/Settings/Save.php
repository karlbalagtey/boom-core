<?php

namespace BoomCMS\Http\Controllers\CMS\Page\Settings;

use BoomCMS\Events\PageSearchSettingsWereUpdated;
use BoomCMS\Events\PageWasMadeVisible;
use BoomCMS\Support\Facades\Auth;
use BoomCMS\Support\Facades\Page;
use DateTime;
use Illuminate\Support\Facades\Event;

class Save extends Settings
{
    public function admin()
    {
        parent::admin();

        $this->page->setInternalName($this->request->input('internal_name'));

        if (Auth::loggedIn('edit_disable_delete', $this->page)) {
            $this->page->setDisableDelete($this->request->input('disable_delete') == '1');
        }

        Page::save($this->page);
    }

    public function children()
    {
        parent::children();

        $post = $this->request->input();

        $this->page->setChildTemplateId($this->request->input('children_template_id'));

        if ($this->allowAdvanced) {
            $this->page
                ->setChildrenUrlPrefix($this->request->input('children_url_prefix'))
                ->setChildrenVisibleInNav($this->request->input('children_visible_in_nav') == 1)
                ->setChildrenVisibleInNavCms($this->request->input('children_visible_in_nav_cms') == 1)
                ->setGrandchildTemplateId($this->request->input('grandchild_template_id'));
        }

        if (isset($post['children_ordering_policy']) && isset($post['children_ordering_direction'])) {
            $this->page->setChildOrderingPolicy($post['children_ordering_policy'], $post['children_ordering_direction']);
        }

        Page::save($this->page);
    }

    public function feature()
    {
        parent::feature();

        $this->page->setFeatureImageId($this->request->input('feature_image_id'));
        Page::save($this->page);
    }

    public function navigation()
    {
        parent::navigation();

        if ($this->allowAdvanced) {
            $this->page->setParentId($this->request->input('parent_id'));
        }

        $this->page
            ->setVisibleInNav($this->request->input('visible_in_nav'))
            ->setVisibleInCmsNav($this->request->input('visible_in_nav_cms'));

        Page::save($this->page);
    }

    public function search()
    {
        parent::search();

        $this->page
            ->setDescription($this->request->input('description'))
            ->setKeywords($this->request->input('keywords'));

        if ($this->allowAdvanced) {
            $this->page
                ->setExternalIndexing($this->request->input('external_indexing'))
                ->setInternalIndexing($this->request->input('internal_indexing'));
        }

        Page::save($this->page);
        Event::fire(new PageSearchSettingsWereUpdated($this->page));
    }

    public function sort_children()
    {
        parent::children();

        $this->page->updateChildSequences($this->request->input('sequences'));
    }

    public function visibility()
    {
        parent::visibility();

        $wasVisible = $this->page->isVisible();

        $this->page->setVisibleAtAnyTime($this->request->input('visible') == 1);

        if ($this->page->isVisibleAtAnyTime()) {
            $visibleTo = ($this->request->input('toggle_visible_to') == 1) ? new DateTime($this->request->input('visible_to')) : null;

            $this->page
                ->setVisibleFrom(new DateTime($this->request->input('visible_from')))
                ->setVisibleTo($visibleTo);
        }

        Page::save($this->page);

        if (!$wasVisible && $this->page->isVisible()) {
            Event::fire(new PageWasMadeVisible($this->page, Auth::getPerson()));
        }

        return (int) $this->page->isVisible();
    }
}
