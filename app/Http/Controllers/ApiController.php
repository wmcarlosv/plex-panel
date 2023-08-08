<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Duration;

class ApiController extends Controller
{
    public function get_months_duration($duration_id){
        $data = Duration::findorfail($duration_id);
        return response()->json($data);
    }
}
