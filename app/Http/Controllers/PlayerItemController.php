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
    // ガチャ一回分のお金
    const GACHA_PRICE = 10;

    // アイテムの所持
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

    // アイテムの使用
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

    public function gacha(Request $request, $id)
    {
        // プレイヤーのid
        $playerId = Player::query() ->where('id', $id);
        // ガチャ回数
        $count = $request->input('count');
        // 所持金額
        $money = $playerId->value('money');

        // 残金確認
        if($money < $count * self::GACHA_PRICE)
        {
            return new Response('お金が足りません', self::ERRCODE);
        }

        // 残金から回数分の金額を引く
        $money -= $count * self::GACHA_PRICE;
        $playerId->update(['money' => $money]);

        // percentのカラムを取得
        $itemPercents = Item::query()->pluck('percent');
        // アイテムのid
        $itemId = 0;
        // 結果格納用
        $result = [];
        // 抽選を行いresultに結果を格納する
        for($i = 0; $i < $count; $i++)
        {
            // 1～100の中からランダムで取得
            $random = mt_rand(1,100);
            $totalPercent = 0;
            for($j = 0; $j < $itemPercents->count(); $j++)
            {
                $totalPercent += $itemPercents[$j];
                if($random <= $totalPercent)
                {
                    $itemId = $j+1;
                    // データがある場合
                    if(array_key_exists($itemId, $result))
                    {
                        $result[$itemId] += 1;
                    }
                    // データがない場合
                    else
                    {
                        $result[$itemId] = 1;
                    }
                    break;
                }
            }
        }

        $resultResponse = [];
        // データの更新
        for($i = 1; $i <= $itemPercents->count(); $i++)
        {
            //playerとitemのid
            $idData = PlayerItem::query()
            ->where(['player_id' => $id, 'item_id' => $i]);

            // データがある場合は更新、ない場合はデータ追加
            if($idData->exists())
            {
                if(array_key_exists($i, $result))
                {
                    $idData->update(['count'=>$idData->value('count') + $result[$i]]);
                }
            }
            else
            {
                PlayerItem::insert(
                    ['player_id' => $id,
                    'item_id' => $i,
                    'count' => $result[$i]]);
            }

            // レスポンスで返す用
            if(array_key_exists($i, $result))
            {
                $data = 
                [
                    'itemId'=> $i,
                    'count' => $result[$i]
                ];
                array_push($resultResponse, $data);
            }
        }
        
        // レスポンスで返す用
        $itemResponse = [];
        $getPlayerItem = PlayerItem::query()->where('player_id',$id)->get();
        foreach($getPlayerItem as $playerItem)
        {
            $data = 
            [
                'itemId'=> $playerItem->item_id,
                'count' => $playerItem->count
            ];
            array_push($itemResponse,$data);
        }

        return new Response([
            'results' => $resultResponse,
            'player' => [
                'money' => $money,
                'items' => $itemResponse
            ]
        ]);
    }
}
