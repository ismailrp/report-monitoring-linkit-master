<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SummaryDailyResource\Pages;
use App\Models\SummaryDaily;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Actions;

class SummaryDailyResource extends Resource
{
    protected static ?string $model = SummaryDaily::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Daily Reports';

    protected static ?string $navigationLabel = 'Summary Daily';
    protected static ?string $modelLabel = 'Summary Daily';
    protected static ?string $pluralModelLabel = 'Summary Daily';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Summary Info')
                    ->schema([
                        Forms\Components\TextInput::make('date')
                            ->label('Date')
                            ->type('date')
                            ->required(),

                        Forms\Components\TextInput::make('hour')
                            ->label('Hour')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(23)
                            ->required(),

                        Forms\Components\TextInput::make('id_service')
                            ->label('Service ID')
                            ->required(),

                        Forms\Components\TextInput::make('id_operator')
                            ->label('Operator ID')
                            ->required(),

                        Forms\Components\TextInput::make('id_country')
                            ->label('Country ID')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Metrics')
                    ->schema([
                        Forms\Components\TextInput::make('mo_reg')
                            ->label('MO REG')
                            ->numeric()
                            ->default(0),

                        Forms\Components\TextInput::make('mo_unreg')
                            ->label('MO UNREG')
                            ->numeric()
                            ->default(0),

                        Forms\Components\TextInput::make('click')
                            ->label('Click')
                            ->numeric()
                            ->default(0),

                        Forms\Components\TextInput::make('mt_success')
                            ->label('MT Success')
                            ->numeric()
                            ->default(0),

                        Forms\Components\TextInput::make('mt_failed')
                            ->label('MT Failed')
                            ->numeric()
                            ->default(0),

                        Forms\Components\TextInput::make('mt_retry')
                            ->label('MT Retry')
                            ->numeric()
                            ->default(0),

                        Forms\Components\TextInput::make('revenue')
                            ->label('Revenue')
                            ->numeric()
                            ->default(0),

                        Forms\Components\TextInput::make('sub_active')
                            ->label('Sub Active')
                            ->numeric()
                            ->default(0),

                        Forms\Components\TextInput::make('sr')
                            ->label('SR')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('⏱️ Latest data is updated every 30 minutes')
            ->columns([
                // Tables\Columns\TextColumn::make('id')
                //     ->label('ID')
                //     ->sortable()
                //     ->searchable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->searchable(),

                // Tables\Columns\TextColumn::make('hour')
                //     ->label('Hour')
                //     ->sortable(),

                Tables\Columns\TextColumn::make('country.country')
                    ->label('Country')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('operator.operator')
                    ->label('Operator')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('service.service')
                    ->label('Service')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('mo_reg')
                    ->label('MO Reg')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('mo_unreg')
                    ->label('MO Unreg')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('click')
                    ->label('Click')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('mt_success')
                    ->label('MT Success')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('mt_failed')
                    ->label('MT Failed')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('mt_retry')
                    ->label('MT Retry')
                    ->numeric()
                    ->sortable(),

                // Tables\Columns\TextColumn::make('revenue')
                //     ->label('Revenue')
                //     ->numeric()
                //     ->sortable(),
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('sub_active')
                    ->label('Sub Active')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sr')
                    ->label('SR')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quality_index')
                    ->label('Quality Index')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state . '%'),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->placeholder('Start Date'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->placeholder('End Date'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['start_date'] ?? null, fn ($q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['end_date'] ?? null, fn ($q, $date) => $q->whereDate('date', '<=', $date));
                    }),

                Tables\Filters\SelectFilter::make('id_country')
                    ->label('Country')
                    ->relationship('country', 'country')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('id_operator')
                    ->label('Operator')
                    ->relationship('operator', 'operator')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('id_service')
                    ->label('Service')
                    ->relationship('service', 'service')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export Data')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\Select::make('operator_id')
                            ->label('Operator')
                            ->options(\App\Models\Operator::pluck('operator', 'id'))
                            ->searchable(),
                        \Filament\Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date'),
                        \Filament\Forms\Components\DatePicker::make('end_date')
                            ->label('End Date'),
                    ])
                    ->action(function (array $data) {
                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\SummaryDailyExport(
                                $data['start_date'],
                                $data['end_date'],
                                null, // countryId
                                $data['operator_id'],
                                null // serviceId
                            ),
                            'summary_daily.xlsx'
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            // ->bulkActions([
            //     Tables\Actions\BulkActionGroup::make([
            //         Tables\Actions\DeleteBulkAction::make(),
            //     ]),
            // ])
            ->defaultSort('date', 'desc')
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(5)
            ->deferLoading()
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [
            // Kalau tabel ini punya relasi ke service/operator/country,
            // nanti bisa ditambah RelationManager di sini
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSummaryDailies::route('/'),
            // 'create' => Pages\CreateSummaryDaily::route('/create'),
            // 'edit' => Pages\EditSummaryDaily::route('/{record}/edit'),
        ];
    }
}
