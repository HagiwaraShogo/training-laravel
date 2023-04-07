<?php

namespace App\Http\Controllers;

use App\Models\PlayerItem;
use App\Models\Player;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlayerItemController extends Controller
{
    // Hp,Mpの上限
    const MAX_HP = 200;
    const MAX_MP = 200;
    // エラーコード
    const ERRCODE = 400;

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
        $idData = PlayerItem::query()
        ->where(['player_id' => $id, 'item_id' => $request->input('itemId')]);
        // プレーヤー情報
        $player = Player::query()->where('id', $idData->value('player_id'));    // プレイヤーのid
        $playerHp = $player->value('hp');   // プレイヤーのhp
        $playerMp = $player->value('mp');   // プレイヤーのmp
        

        // データがないもしくはアイテムがない場合エラーレスポンスを返す
        if($idData->doesntExist())
        {
            return new Response('データがありません', self::ERRCODE);
        }
        if($idData->value('count') <= 0)
        {
            return new Response('アイテムがありません', self::ERRCODE);
        }

        // アイテムの回復量
        $itemValue = Item::query()->where('id', $idData->value('item_id'))->value('value');
        // アイテムの所持個数
        $itemNum = $idData->value('count');
        // アイテムの使用個数
        $useNum = $request->input('count');

        if($itemNum < $useNum)
        {
            return new Response('アイテムが足りません', self::ERRCODE);
        }

        // HPかいふく薬
        if($idData->value('item_id') == 1)
        {
            // HPが上限の場合
            if($playerHp >= self::MAX_HP)
            {
                return new Response('HPがMAXのため使用できません', self::ERRCODE);
            }

            // アイテムを一個ずつ加算していく
            for($i = 0; $i < $useNum; $i++)
            {
                $itemNum--;
                if($playerHp + $itemValue < self::MAX_HP)
                {
                    $playerHp += $itemValue;
                }
                else
                {
                    $playerHp =  self::MAX_HP;
                    break;
                }
            }
        }
        // MPかいふく薬
        if($idData->value('item_id') == 2)
        {
            // MPが上限の場合
            if($playerMp >= self::MAX_MP)
            {
                return new Response('MPがMAXのため使用できません', self::ERRCODE);
            }

             // アイテムを一個ずつ加算していく
            for($i = 0; $i < $useNum; $i++)
            {
                $itemNum--;
                if($playerMp + $itemValue < self::MAX_MP)
                {
                    $playerMp += $itemValue;
                }
                else
                {
                    $playerMp =  self::MAX_MP;
                    break;
                }
            }
        }

        // データ更新処理
        $idData->update(["count" => $itemNum]);
        $player->update(['hp' => $playerHp, 'mp' => $playerMp]);

        return new Response(['itemId' => $idData->value('item_id'),
        'count' => $itemNum,
        'player' => ['id' => $player->value('id'),
        'hp' => $playerHp,
        'mp' => $playerMp,]]);
    }
}
