<?php

namespace App\Filament\Resources\SummaryDailyResource\Pages;

use App\Filament\Resources\SummaryDailyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSummaryDailies extends ListRecords
{
    protected static string $resource = SummaryDailyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('syncApi')
                ->label('Get Data API')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\Select::make('operator_id')
                        ->label('Operator')
                        ->options(\App\Models\Operator::pluck('operator', 'id'))
                        ->required()
                        ->searchable(),
                    \Filament\Forms\Components\DatePicker::make('start_date')
                        ->label('Start Date')
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('end_date')
                        ->label('End Date')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $operatorId = $data['operator_id'];
                    // Format dates as YYYYMMDD
                    $startDate = \Carbon\Carbon::parse($data['start_date'])->format('Ymd');
                    $endDate = \Carbon\Carbon::parse($data['end_date'])->format('Ymd');

                    $url = "http://149.129.252.221:8028/app/api/rpt/api_airpay.php?acc=datas&req=SummaryDailyByOperator&data={$operatorId}|{$startDate}|{$endDate}";

                    try {
                        $response = \Illuminate\Support\Facades\Http::get($url);

                        if ($response->successful()) {
                            $result = $response->json();

                            if ($result['status'] === 'success' && isset($result['data'])) {
                                $records = $result['data'];
                                $count = 0;

                                foreach ($records as $record) {
                                    // Use carbon to parse the returned Y-m-d date format
                                    $recordDate = $record['date'] ?? null;
                                    if (!$recordDate) continue;

                                    // Mapping total_reg -> mo_reg, total_unreg -> mo_unreg
                                    \App\Models\SummaryDaily::updateOrCreate(
                                        [
                                            'date' => $recordDate,
                                            'id_service' => $record['id_service'],
                                            'id_operator' => $operatorId,
                                            // The API response does not include country/hour. We use defaults:
                                            'hour' => 0, 
                                        ],
                                        [
                                            'mo_reg' => $record['total_reg'] ?? 0,
                                            'mo_unreg' => $record['total_unreg'] ?? 0,
                                            'mt_success' => $record['mt_success'] ?? 0,
                                            'mt_failed' => $record['mt_failed'] ?? 0,
                                            'mt_retry' => $record['mt_retry'] ?? 0,
                                            'mt_retry_success' => $record['mt_retry_success'] ?? 0,
                                            'sub_active' => $record['total_user'] ?? 0,
                                            'revenue' => $record['grev'] ?? 0,
                                            'click' => 0,
                                            'sr' => 0, 
                                        ]
                                    );
                                    $count++;
                                }

                                \Filament\Notifications\Notification::make()
                                    ->title('Success Sync API')
                                    ->body("Successfully synced {$count} records.")
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('API Error')
                                    ->body('Invalid response structure or status.')
                                    ->danger()
                                    ->send();
                            }
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('HTTP Error')
                                ->body('Failed to connect to API.')
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('System Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
