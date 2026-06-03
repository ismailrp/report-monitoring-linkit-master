<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\SummaryDaily;
use Illuminate\Support\Carbon;

class TableTopDropSr extends BaseWidget
{
    protected static ?string $heading = 'TOP DROP SR';
    protected int|string|array $columnSpan = '4';

    public function table(Table $table): Table
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        return $table
            ->query(
                SummaryDaily::query()
                    ->join('services', 'summary_daily.id_service', '=', 'services.id')
                    ->join('operators', 'summary_daily.id_operator', '=', 'operators.id')
                    ->select('summary_daily.*', 'services.service as service', 'operators.operator as operator')
                    ->where('summary_daily.hour', 23)
                    ->where('summary_daily.date', $yesterday)
                    ->orderByDesc('summary_daily.sr')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('service')->label('Service'),
                Tables\Columns\TextColumn::make('operator')->label('Operator'),
                Tables\Columns\TextColumn::make('click')
                    ->label('Total Click')
                    ->numeric(0, ',', '.')
                    ->alignRight(),
                Tables\Columns\TextColumn::make('mo_reg')
                    ->label('Total MO')
                    ->numeric(0, ',', '.')
                    ->alignRight(),
                Tables\Columns\TextColumn::make('sr')
                    ->label('SR (%)')
                    ->numeric(2, ',', '.')
                    ->suffix(' %')
                    ->alignRight(),
            ])
            ->defaultSort('sr', 'desc');
    }
}
