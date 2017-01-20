<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\Rpc;
use Route;

class MathController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function add()
    {
        

        $rules['num1'] = ['required', 'integer'];
        $rules['num2'] = ['required', 'integer'];

        $data = app('request')->only(array_keys($rules));
        $validator = app('validator')->make($data, $rules);

        return \Rpcwsw\Client::instance('serviceB')
            ->api('math/mult', ['num1' => $data['num1'],'num2' => $data['num2']], 'GET');

        if ($validator->fails()) {
            return [
                'code' => 103,
                'msg' => 'params error',
                'data' => $validator->errors(),
            ];
        }

        return [
            'code' => 0,
            'data' => $data['num1'] + $data['num2'],
        ];

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function mult()
    {
        $rules['num1'] = ['required', 'integer'];
        $rules['num2'] = ['required', 'integer'];

        $data = app('request')->only(array_keys($rules));
        $validator = app('validator')->make($data, $rules);

        if ($validator->fails()) {
            return [
                'code' => 103,
                'msg' => 'params error',
                'data' => $validator->errors(),
            ];
        }

        return [
            'data' => $data['num1'] * $data['num2'],
            'code' => 0,
        ];

    }

    public function index() {

        return ['data' => 1, 'code' => 0];
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
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

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
