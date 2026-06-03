<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SummaryAirpayWeeklyResource\Pages;
use App\Models\Merchant;
use App\Models\Operator;
use App\Models\SummaryWeekly;
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

class SummaryAirpayWeeklyResource extends Resource
{
    protected static ?string $model = SummaryWeekly::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationGroup = 'Weekly Reports';
    protected static ?string $navigationLabel = 'Weekly';
    protected static ?string $modelLabel = 'Weekly';
    protected static ?string $pluralModelLabel = 'Weekly';
    protected static ?string $slug = 'weekly';

    /**
     * Override query: hanya tampilkan summary_weekly yang operator-nya terkait dengan service yang punya merchant.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('operator', function (Builder $q) {
                $q->whereHas('Services', function (Builder $sq) {
                    $sq->whereNotNull('id_merchant');
                });
            });
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
                Tables\Columns\TextColumn::make('year')
                    ->label('Year')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('periode')
                    ->label('Periode (Week)')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('End Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('operator.operator')
                    ->label('Operator')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_mt')
                    ->label('Total MT')
                    ->numeric(thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_mo')
                    ->label('Total MO')
                    ->numeric(thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_revenue')
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
                        return round($record->total_revenue * $rate, 0);
                    })
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_sub_active')
                    ->label('Sub Active Total')
                    ->numeric(thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('merchant')
                    ->label('Merchant (Filters by Operator)')
                    ->options(fn () => Merchant::orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('operator', function (Builder $q) use ($data) {
                                $q->whereHas('Services', function (Builder $sq) use ($data) {
                                    $sq->where('id_merchant', $data['value']);
                                });
                            });
                        }
                    }),

                SelectFilter::make('id_operator')
                    ->label('Operator')
                    ->options(fn () => Operator::whereHas('Services', fn ($q) => $q->whereNotNull('id_merchant'))
                        ->orderBy('operator')
                        ->pluck('operator', 'id')
                        ->toArray()
                    )
                    ->searchable(),
            ])
            ->headerActions([
                ExportAction::make('export')
                    ->label('Export Data')
                    ->color('success')
                    ->exports([
                        ExcelExport::make('summary_airpay_weekly_export')
                            ->fromTable()
                            ->withColumns([
                                Column::make('year')->heading('Year'),
                                Column::make('periode')->heading('Periode'),
                                Column::make('start_date')->heading('Start Date'),
                                Column::make('end_date')->heading('End Date'),
                                Column::make('operator.operator')->heading('Operator'),
                                Column::make('total_mt')->heading('Total MT'),
                                Column::make('total_mo')->heading('Total MO'),
                                Column::make('total_revenue')->heading('Revenue'),
                                Column::make('revenue_usd')->heading('Revenue USD'),
                                Column::make('total_sub_active')->heading('Sub Active'),
                            ]),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('year', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50]);
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\SummaryAirpayWeeklyResource\Widgets\SummaryAirpayWeeklyStatsWidget::class,
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSummaryAirpayWeeklies::route('/'),
        ];
    }
}
