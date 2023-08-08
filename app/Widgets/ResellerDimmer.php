<?php
  namespace App\Widgets;
  use Illuminate\Support\Facades\Auth;
  use Illuminate\Support\Str;
  use TCG\Voyager\Facades\Voyager;
  use Arrilot\Widgets\AbstractWidget;
  use App\Models\User;

  class ResellerDimmer extends AbstractWidget
  {

     protected $config = [];

     public function run()
     {
       $users = User::where('role_id',3)->get();
       $count = $users->count();
       $string = trans_choice('Vendedores', $count);

       return view('voyager::dimmer', array_merge($this->config, [
        'icon'   => 'voyager-person',
        'title'  => "{$count} {$string}",
        'text'   => __('Vendedores', ['count' => $count, 'string' => Str::lower($string)]),
        'button' => [
            'text' => __('Ver todos los Vendedores'),
            'link' => route('voyager.users.index'),
        ],
        'image' => voyager_asset('images/widget-backgrounds/02.jpg'),
      ]));
   }

   public function shouldBeDisplayed()
   {
    if(Auth::user()->role_id == 4 || Auth::user()->role_id == 1){
        return true;
    }else{
        return false;
    }
   }
 }