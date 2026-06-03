<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SummaryAirpayResource\Pages;
use App\Models\Merchant;
use App\Models\Service;
use App\Models\SummaryDaily;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class SummaryAirpayResource extends Resource
{
    protected static ?string $model = SummaryDaily::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationGroup = 'Daily Reports';
    protected static ?string $navigationLabel = 'Daily';
    protected static ?string $modelLabel = 'Daily';
    protected static ?string $pluralModelLabel = 'Daily';
    protected static ?string $slug = 'daily';

    /**
     * Override query: hanya tampilkan summary_daily yang service-nya memiliki merchant.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('service', fn (Builder $q) => $q->whereNotNull('id_merchant'));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('⏱️ Latest data is updated every 30 minutes')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('service.merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('service.service')
                    ->label('Service')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('operator.operator')
                    ->label('Operator')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('mo_reg')
                    ->label('MO Reg')
                    ->numeric(thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('mo_unreg')
                    ->label('MO Unreg')
                    ->numeric(thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('click')
                    ->label('Click')
                    ->numeric(thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('mt_success')
                    ->label('MT Succ')
                    ->numeric(thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('mt_failed')
                    ->label('MT Fail')
                    ->numeric(thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('revenue')
                    ->label('Revenue')
                    ->numeric(thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('revenue_usd')
                    ->label('Revenue USD')
                    ->getStateUsing(function ($record) {
                        $rate = $record->country?->convert_usd ?? 0;
                        if (strtoupper($record->country?->country ?? '') === 'OMAN') {
                            $rate = $rate / 1000;
                        }
                        return round($record->revenue * $rate, 0);
                    })
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sub_active')
                    ->label('Sub Act')
                    ->numeric(thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sr')
                    ->label('SR')
                    ->numeric()
                    ->alignRight()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('merchant')
                    ->label('Merchant')
                    ->options(fn () => Merchant::orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('service', fn (Builder $q) => $q->where('id_merchant', $data['value']));
                        }
                    }),

                SelectFilter::make('id_service')
                    ->label('Service')
                    ->options(fn () => Service::whereNotNull('id_merchant')
                        ->orderBy('service')
                        ->pluck('service', 'id')
                        ->toArray()
                    )
                    ->searchable(),

                Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('date', '<=', $date));
                    }),
            ])
            ->headerActions([
                ExportAction::make('export')
                    ->label('Export Data')
                    ->color('success')
                    ->exports([
                        ExcelExport::make('summary_airpay_export')
                            ->fromTable()
                            ->withColumns([
                                Column::make('date')->heading('Date'),
                                Column::make('service.merchant.name')->heading('Merchant'),
                                Column::make('service.service')->heading('Service'),
                                Column::make('operator.operator')->heading('Operator'),
                                Column::make('mo_reg')->heading('MO Reg'),
                                Column::make('mo_unreg')->heading('MO Unreg'),
                                Column::make('click')->heading('Click'),
                                Column::make('mt_success')->heading('MT Success'),
                                Column::make('mt_failed')->heading('MT Failed'),
                                Column::make('revenue')->heading('Revenue'),
                                Column::make('revenue_usd')->heading('Revenue USD'),
                                Column::make('sub_active')->heading('Sub Active'),
                                Column::make('sr')->heading('SR'),
                            ]),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('date', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50]);
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\SummaryAirpayResource\Widgets\SummaryAirpayStatsWidget::class,
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSummaryAirpays::route('/'),
        ];
    }
}
