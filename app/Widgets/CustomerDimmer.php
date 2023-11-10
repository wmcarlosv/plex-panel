<?php
  namespace App\Widgets;
  use Illuminate\Support\Facades\Auth;
  use Illuminate\Support\Str;
  use TCG\Voyager\Facades\Voyager;
  use Arrilot\Widgets\AbstractWidget;
  use App\Models\Customer;

  class CustomerDimmer extends AbstractWidget
  {

     protected $config = [];

     public function run()
     {
       if(Auth::user()->role_id == 3 || Auth::user()->role_id == 5 || Auth::user()->role_id == 6){
        $customer = Customer::where('user_id',Auth::user()->id)->where('status','active')->get();
       }else{
        $customer = Customer::where('status','active')->get();
       }
       
       $count = $customer->count();
       $string = trans_choice('Clientes', $count);

       return view('voyager::dimmer', array_merge($this->config, [
        'icon'   => 'voyager-person',
        'title'  => "{$count} {$string}",
        'text'   => __('Clientes', ['count' => $count, 'string' => Str::lower($string)]),
        'button' => [
            'text' => __('Ver todos los Clientes'),
            'link' => route('voyager.customers.index'),
        ],
        'image' => voyager_asset('images/widget-backgrounds/02.jpg'),
      ]));
   }

   public function shouldBeDisplayed()
   {
    return true;
   }
 }