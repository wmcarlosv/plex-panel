<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class ViewActiveSessionsAction extends AbstractAction
{
    public function getTitle()
    {
        return 'Sesiones Activas';
    }

    public function getIcon()
    {
        return 'voyager-tv';
    }

    public function shouldActionDisplayOnDataType()
    {
        // show or hide the action button, in this case will show for posts model
        return $this->dataType->slug == 'servers';
    }

    public function getPolicy()
    {
        return 'read';
    }

    public function getAttributes()
    {
        return [
            'class' => 'btn btn-sm btn-primary pull-right view-active-sessions',
            'data-id'=>$this->data->id
        ];
    }

    public function getDefaultRoute()
    {
        return "#";
    }
}