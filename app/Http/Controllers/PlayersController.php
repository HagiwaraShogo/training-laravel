<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlayerResource;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PlayersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return new Response(
            Player::query()->
            select(['id', 'name','hp','mp','money'])->
            get());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return new Response(
            Player::find($id)
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $id = Player::insertGetId(
            ['name' => $request->input('name'),
            'hp' => $request->input('hp'),
            'mp' => $request->input('mp'),
            'money' => $request->input('money')]);
        return new Response(['id' => $id]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $id = Player::where('id', $id);
        $status = [];

        if($request->has('name'))
            {
                $status['name'] = $request->input('name');
            }

        if($request->has('hp'))
            {
                $status['hp'] = $request->input('hp');
            }
            
        if($request->has('mp'))
            {
                $status['mp'] = $request->input('mp');
            }

        if($request->has('money'))
            {
                $status['money'] = $request->input('money');
            }

        $id->update($status);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Player::where('id', $id) -> delete();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }
}
