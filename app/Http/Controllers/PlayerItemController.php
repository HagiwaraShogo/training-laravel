<?php

namespace App\Http\Controllers;

use App\Models\PlayerItem;
use App\Models\Player;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

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
        try
        {
            // トランザクション開始
            DB::beginTransaction();

            //playerとitemのidの取得とロック
            $all_id = PlayerItem::query()
            ->where(['player_id' => $id, 'item_id' => $request->input('itemId')])->lockForUpdate();
        
            // 追加するアイテムの個数を取得
            $num = $request->input('count');
        
            // 既にデータがある場合、個数分追加
            if ($all_id->exists()) {
                $num += $all_id->value('count');
                $all_id->update(['count'=>$num]);
            }
            // データがない場合新しく追加
            else
            {
                PlayerItem::insertGetId(
                    ['player_id' => $id,
                    'item_id' => $request->input('itemId'),
                    'count' => $num]);
            }

            // コミットしてトランザクション終了
            DB::commit();

            // 結果を返す
            return new Response([
                'itemId' => $request->input('itemId'),
                'count' => $num]);

        } 
        catch (\Exception $e ) {
            // ロールバックしてトランザクション終了
            DB::rollBack();
            return new Response(
                $e->getMessage(), self::ERRCODE);
        }
    }

    // アイテムの使用
    public function use(Request $request, $id)
    {
        try
        {
            DB::beginTransaction();

            //playerとitemのidの取得とロック
            $idData = PlayerItem::query()
            ->where(['player_id' => $id, 'item_id' => $request->input('itemId')])->lockForUpdate();
            // プレーヤー情報
            $player = Player::query()->where('id', $idData->value('player_id'));    // プレイヤーのid
            $playerHp = $player->value('hp');   // プレイヤーのhp
            $playerMp = $player->value('mp');   // プレイヤーのmp
        

            // データがないもしくはアイテムがない場合エラーレスポンスを返す
            if($idData->doesntExist())
            {
                throw new \Exception('データがありません');
            }
            if($idData->value('count') <= 0)
            {
                throw new \Exception('アイテムがありません');
            }

            // アイテムの回復量
            $itemValue = Item::query()->where('id', $idData->value('item_id'))->value('value');
            // アイテムの所持個数
            $itemNum = $idData->value('count');
            // アイテムの使用個数
            $useNum = $request->input('count');

            if($itemNum < $useNum)
            {
                throw new \Exception('アイテムが足りません');
            }

            // HPかいふく薬
            if($idData->value('item_id') == 1)
            {
                // HPが上限の場合
                if($playerHp >= self::MAX_HP)
                {
                    throw new \Exception('HPがMAXのため使用できません');
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
                    throw new \Exception('MPがMAXのため使用できません');
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

            // コミットしてトランザクション終了
            DB::commit();

            // 結果を返す
            return new Response(['itemId' => $idData->value('item_id'),
            'count' => $itemNum,
            'player' => ['id' => $player->value('id'),
            'hp' => $playerHp,
            'mp' => $playerMp,]]);
        } 
        catch(\exception $e)
        {
            // ロールバックしてトランザクション終了
            DB::rollBack();
            return new Response(
                $e->getMessage(), self::ERRCODE);
        }
        
    }

    public function gacha(Request $request, $id)
    {
        try
        {
            DB::beginTransaction();

            // プレイヤーのidを取得し、ロックする
            $playerId = Player::query() ->where('id', $id)->lockForUpdate();

            if($playerId->doesntExist())
            {
                throw new \Exception('プレイヤーデータがありません');
            }
            // ガチャ回数
            $count = $request->input('count');
            // 所持金額
            $money = $playerId->value('money');

            // 残金確認
            if($money < $count * self::GACHA_PRICE)
            {
                throw new \Exception('お金が足りません');
            }

            // 残金から回数分の金額を引く
            $money -= $count * self::GACHA_PRICE;
            $playerId->update(['money' => $money]);

            // percentのカラムを取得
            $itemPercents = Item::query()->pluck('percent');
            // item_idのカラムを取得
            $itemId = PlayerItem::query()->pluck('item_id');
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
                        // データがある場合
                        if(array_key_exists($itemId[$j], $result))
                        {
                            $result[$itemId[$j]] += 1;
                        }
                        // データがない場合
                        else
                        {
                            $result[$itemId[$j]] = 1;
                        }
                        break;
                    }
                }
            }

            $resultResponse = [];
            // データの更新
            for($i = 0; $i < $itemPercents->count(); $i++)
            {
                //playerとitemのid
                $idData = PlayerItem::query()
                ->where(['player_id' => $id, 'item_id' => $itemId[$i]]);

                // データがある場合は更新、ない場合はデータ追加
                if($idData->exists())
                {
                    if(array_key_exists($itemId[$i], $result))
                    {
                        $idData->update(['count'=>$idData->value('count') + $result[$itemId[$i]]]);
                    }
                }
                else
                {
                    PlayerItem::insert(
                        ['player_id' => $id,
                        'item_id' => $itemId[$i],
                        'count' => $result[$i]]);
                }

                // レスポンスで返す用
                if(array_key_exists($itemId[$i], $result))
                {
                    $data = 
                    [
                        'itemId'=> $itemId[$i],
                        'count' => $result[$itemId[$i]]
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

            // コミットしてトランザクション終了
            DB::commit();

            return new Response([
                'results' => $resultResponse,
                'player' => [
                    'money' => $money,
                    'items' => $itemResponse
                ]
            ]);
        }
        catch(\Exception $e)
        {
            // ロールバックしてトランザクション終了
            DB::rollBack();
            return new Response(
                $e->getMessage(), self::ERRCODE);
        }
        
    }
}
