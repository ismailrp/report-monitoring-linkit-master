<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class GetDataFromFrController extends Controller
{
    protected $client;
    protected $apiUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiUrl = "http://149.129.252.221:8028/app/api/rpt/third.php";
    }

    public function getOperator(Request $request)
    {
        try {
            $response = $this->client->get($this->apiUrl, [
                'query' => [
                    'req' => 'listoperator',
                    'acc' => 'datas',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (is_array($data)) {
                foreach ($data as $op) {
                    Operator::updateOrCreate(
                        ['id_operator' => $op['id_operator']], // kunci unik
                        [
                            'operator_name' => $op['operator_name'] ?? null,
                            'country'       => $op['country'] ?? null,
                            'country_code'  => $op['country_code'] ?? null,
                            'alias'         => $op['alias'] ?? null,
                        ]
                    );
                }
            }

            return response()->json([
                'message' => 'Operators synced successfully',
                'count'   => is_array($data) ? count($data) : 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Failed to fetch or save operator data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getService(Request $request)
    {
        try {
            $response = $this->client->post($this->apiUrl, [
                'form_params' => [
                    'action' => 'getService',
                    'operator_id' => $request->input('operator_id'),
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch service data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getTransaction()
    {

    }

    public function getSr()
    {

    }

    public function getMo()
    {

    }

    public function getSubActive()
    {

    }
}
