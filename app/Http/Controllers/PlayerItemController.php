<?php

namespace App\Http\Controllers;

use App\Models\PlayerItem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlayerItemController extends Controller
{
    
    public function add(Request $request, $id)
    {
        
        $all_id = PlayerItem::query()
        ->where(['playerId' => $id, 'itemId' => $request->input('itemId')]);
        
        $num = $request->input('count');
        
        if ($all_id->exists()) {
            $num += $all_id->value('count');
            $all_id->update(['count'=>$num]);
        }
        else
        {
            PlayerItem::insertGetId(
                ['playerId' => $id,
                'itemId' => $request->input('itemId'),
                'count' => $num]);
        }

        return new Response([
            'itemId' => $request->input('itemId'),
            'count' => $num]);
            
    }
}
