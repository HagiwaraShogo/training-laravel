<?php

namespace App\Http\Controllers;

use App\Models\PlayerItem;
use App\Models\Player;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlayerItemController extends Controller
{
    
    public function add(Request $request, $id)
    {
        
        $all_id = PlayerItem::query()
        ->where(['player_id' => $id, 'item_id' => $request->input('itemId')]);
        
        $num = $request->input('count');
        
        if ($all_id->exists()) {
            $num += $all_id->value('count');
            $all_id->update(['count'=>$num]);
        }
        else
        {
            PlayerItem::insertGetId(
                ['player_id' => $id,
                'item_id' => $request->input('itemId'),
                'count' => $num]);
        }

        return new Response([
            'itemId' => $request->input('itemId'),
            'count' => $num]);
            
    }

    public function use(Request $request, $id)
    {
        //playerとitemのid
        $all_id = PlayerItem::query()
        ->where(['player_id' => $id, 'item_id' => $request->input('itemId')]);
        // プレーヤー情報
        $player = Player::query()->where('id', $all_id->value('player_id'));    // プレイヤーのid
        $playerHp = $player->value('hp');   // プレイヤーのhp
        $playerMp = $player->value('mp');   // プレイヤーのmp
        // Hp,Mpの上限
        $MAX_HP = 200;
        $MAX_MP = 200;
        // エラーコード
        $ERRCODE = 400;

        // データがないもしくはアイテムがない場合エラーレスポンスを返す
        if($all_id->doesntExist())
        {
            return new Response('データがありません', $ERRCODE);
        }
        if($all_id->value('count') <= 0)
        {
            return new Response('アイテムがありません', $ERRCODE);
        }

        // アイテムの回復量
        $itemValue = Item::query()->where('id', $all_id->value('item_id'))->value('value');
        // アイテムの所持個数
        $itemNum = $all_id->value('count');
        // アイテムの使用個数
        $useNum = $request->input('count');

        if($itemNum < $useNum)
        {
            return new Response('アイテムが足りません', $ERRCODE);
        }

        // HPかいふく薬
        if($all_id->value('item_id') == 1)
        {
            // HPが上限の場合
            if($playerHp >= $MAX_HP)
            {
                return new Response('HPがMAXのため使用できません', $ERRCODE);
            }

            // アイテムを一個ずつ加算していく
            for($i = 0; $i < $useNum; $i++)
            {
                $itemNum--;
                if($playerHp + $itemValue < $MAX_HP)
                {
                    $playerHp += $itemValue;
                }
                else
                {
                    $playerHp =  $MAX_HP;
                    break;
                }
            }
        }
        // MPかいふく薬
        if($all_id->value('item_id') == 2)
        {
            // MPが上限の場合
            if($playerMp >= $MAX_MP)
            {
                return new Response('MPがMAXのため使用できません', $ERRCODE);
            }

             // アイテムを一個ずつ加算していく
            for($i = 0; $i < $useNum; $i++)
            {
                $itemNum--;
                if($playerMp + $itemValue < $MAX_MP)
                {
                    $playerMp += $itemValue;
                }
                else
                {
                    $playerMp =  $MAX_MP;
                    break;
                }
            }
        }

        // データ更新処理
        $all_id->update(["count" => $itemNum]);
        $player->update(['hp' => $playerHp, 'mp' => $playerMp]);

        return new Response(['itemId' => $all_id->value('item_id'),
        'count' => $itemNum,
        'player' => ['id' => $player->value('id'),
        'hp' => $playerHp,
        'mp' => $playerMp,]]);
    }
}
