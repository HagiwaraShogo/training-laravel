<?php

namespace App\Http\Controllers;

use App\Models\PlayerItem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
class PlayerItemController extends Controller
{
    //
    public function add(Request $request, $id)
    {
        $all_id = PlayerItem::query()
        ->where('player_id' , $id)
        ->where('item_id' , $request->input('item_id'));
        //Log::debug($all_id);
        
        $num = $request->input('num');
        
        if ($all_id->exists()) {
            $num += $all_id->value('num');
            $all_id->update(['num'=>$num]);
        }
        else
        {
            PlayerItem::insertGetId(
                ['player_id' => $id,
                'item_id' => $request->input('item_id'),
                'num' => $num]);
        }

        return new Response([
            'itemId' => $request->input('item_id'),
            'num' => $num]);
    }
}
