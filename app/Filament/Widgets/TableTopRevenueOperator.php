<?php

namespace App\Filament\Widgets;

use App\Models\TransactionHour;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TableTopRevenueOperator extends BaseWidget
{
    protected static ?string $heading = 'TOP REVENUE OPERATOR';
    // protected int|string|array $columnSpan = '4';

    public function table(Table $table): Table
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        return $table
            ->query(
                TransactionHour::query()
                    ->select('operator',
                    DB::raw('SUM(revenue) as total_revenue'),
    DB::raw('MIN(id) as id') )// tambahkan supaya Filament punya primary key)
                    ->where('hour', 23)
                    ->where('date', $yesterday)
                    ->groupBy('operator') // Mengelompokkan berdasarkan operator_id yang ada di tabel
                    ->orderByRaw('SUM(revenue) DESC')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('operator')->label('Operator'), 
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Total Revenue')
                    ->numeric(0, ',', '.')
                    ->alignRight(),
            ]);
    }
}
