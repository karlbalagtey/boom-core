<?php

namespace BoomCMS\Core\Controllers\Asset;

class MSWord extends Processor
{
    public function thumbnail($width = null, $height = null)
    {
        return $this->response
            ->headers('Content-type', 'image/jpg')
            ->body(readfile(__DIR__.'/../../../../public/boom/img/icons/ms_word.jpg'));
    }
}