<?php

namespace App\Http\Controllers\API;

use App\Models\Country;
use App\Models\Operator;
use App\Models\Service;
use App\Models\SrHour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SRController
{
    public function store(Request $r)
    {

        // $r->validate([
        //     'operator'=>'string',
        //     'service'=>'string',
        //     'subkeyword'=>'string',
        //     'sdc'=>'integer',
        //     'landing'=>'integer',
        //     'mo'=>'integer',
        //     'type'=>'string'
        // ]);

        $validate = Validator::make($r->all(), [
            'operator'   => 'required|string',
            'service'    => 'required|string',
            'subkeyword' => 'required|string',
            'sdc'        => 'required|integer',
            'landing'    => 'required|integer',
            'mo'         => 'required|integer',
            'type'       => 'required|string|in:daily,hourly',
        ], [
            'required' => 'The :attribute field is required.',
            'string'   => 'The :attribute must be a string.',
            'integer'  => 'The :attribute must be an integer.',
            'in'       => 'The :attribute must be either daily or hourly.',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'error' => $validate->errors(),
            ], 400);
        }

        $get_operator 	= trim($r['operator']);
        $get_operator	= str_replace(' ', '', $get_operator);

        $get_service 	= trim($r['service']);
        $get_subkeyword = trim($r['subkeyword']);
        $sdc = trim($r['sdc']);
        $sdc = trim($r['sdc']);
        $total_click = $r['landing'];
        $total_mo = $r['mo'];

        if ( strtolower($get_subkeyword) == 'normal' ) {
            $all_keyword  	= strtolower($get_service);
        } else {
            $all_keyword  	= strtolower($get_service.' '.$get_subkeyword);
        }

        $operator = Operator::where('alias',$get_operator)
                    ->orWhere('operator', $get_operator)
                    ->first();
        // $operator = Operator::where('alias',$get_operator)->first();
        if(!$operator)
        {
            return response([
                'msg'=>'operator not found'
            ],404);
        }

        $service = Service::where('service',$all_keyword)->where('sdc',$sdc)->first();
        if(!$service)
        {
            return response([
                'msg'=>'service not found'
            ],404);
        }

        $date = now()->format('Y-m-d');
        $hour = now()->format('H');

        if ($r['type'] == 'daily') {
            $hour = 24;
            $date = \Carbon\Carbon::yesterday()->format('Y-m-d');
        }


        $country = Country::where('id',$operator->id_country)->first();

        // Menghitung Success Rate (SR)
        // Pastikan total_click tidak nol untuk menghindari "division by zero"
        $sr = ($total_click > 0) ? ($total_mo / $total_click) * 100 : 0;

        SrHour::updateOrCreate(
            [
                'date' => $date,
                'hour' => $hour,
                'id_operator' => $operator->id,
                'id_service' => $service->id,
                'country' => $country['country'] ?? 'ID' // Asumsi: tambahkan 'country' pada request
            ],
            [
                'operator' => $operator->operator,
                'service' => $service->service,
                'total_click' => $total_click,
                'total_mo' => $total_mo,
                'sr' => $sr,
            ]
        );

        return response([
            'msg' => 'send data success '. $date . '(Hour '. $hour .')',
            'sr'=> $sr,
        ], 200);
    }
}
