<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class ActiveInactiveAction extends AbstractAction
{
    private $current_row;
    public function getTitle()
    {
        $title = "";
        if($this->current_row->status == "active"){
            $title = "Inhabilitar";
        }else{
            if(strtotime(date('Y-m-d')) > strtotime($this->current_row->end_date)){
                $title = "Habilitar";
            }
        }
        return $title;
    }

    public function getIcon()
    {
        $icon = "";
        if($this->current_row->status == "active"){
            $icon = "voyager-x";
        }else{
            if(strtotime(date('Y-m-d')) > strtotime($this->current_row->end_date)){
                $icon = "voyager-check";
            }
        }

        return $icon;
    }

    public function getPolicy()
    {
        return 'read';
    }

    public function getAttributes()
    {

        $class = "";
        if($this->current_row->status == "active"){
            $class = "btn btn-sm btn-danger pull-right change-status-modal";
        }else{
            if(strtotime(date('Y-m-d')) > strtotime($this->current_row->end_date)){
                $class = "btn btn-sm btn-success pull-right change-status-modal";
            }
        }

        return [
            'class' => $class,
            'data-id'=>$this->current_row->id,
            'data-server-id'=>$this->current_row->server_id
        ];
    }

    public function shouldActionDisplayOnRow($row)
    {
        $this->current_row = $row;

        if($this->current_row->status == "active"){
            return $row;
        }else{
            if(strtotime(date('Y-m-d')) > strtotime($this->current_row->end_date)){
                return $row;
            }
        }
    }

    public function getDefaultRoute()
    {
        return "#";
    }

    public function shouldActionDisplayOnDataType(){
        return $this->dataType->slug == 'customers';
    }
}