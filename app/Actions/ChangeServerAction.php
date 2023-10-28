<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class ChangeServerAction extends AbstractAction
{
    private $current_row;
    public function getTitle()
    {
        return 'Change Server';
    }

    public function getIcon()
    {
        return 'voyager-pen';
    }

    public function getPolicy()
    {
        return 'read';
    }

    public function getAttributes()
    {
        return [
            'class' => 'btn btn-sm btn-primary pull-right change-server-modal',
            'data-id'=>$this->current_row->id,
            'data-server-id'=>$this->current_row->server_id
        ];
    }

    public function shouldActionDisplayOnRow($row)
    {
        $this->current_row = $row;
        return $row;
    }

    public function getDefaultRoute()
    {
        return "#";
    }

    public function shouldActionDisplayOnDataType(){
        return $this->dataType->slug == 'customers';
    }
}