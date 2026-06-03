<?php

namespace App\Filament\Pages;

use App\Models\SummaryDaily;
use App\Models\Country;
use App\Models\Operator;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Get;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Carbon\Carbon;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class WeeklySummary extends Page implements HasTable, HasForms
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Weekly Reports';
    use InteractsWithTable;
    use InteractsWithForms;

    protected static string $view = 'filament.pages.weekly-summary';
    protected static ?string $navigationLabel = 'Weekly Summary';
    protected static ?string $title = 'Weekly Summary';

    public array $filters = [
        'start_date' => null,
        'end_date' => null,
        'id_country' => null,
        'id_operator' => null,
    ];

    public float|int $total_revenue = 0;
    public int $total_mt = 0;
    public int $total_mo = 0;

    protected $listeners = [
        'refreshTable' => '$refresh',
    ];

    public function mount(): void
    {
        $this->form->fill($this->filters);
        $this->computeTotals();
    }

    public function getFormSchema(): array
    {
        return [
            Grid::make()
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 4])
                ->schema([
                    DatePicker::make('start_date')
                        ->label('Start Date')
                        ->live()
                        ->required(),
                    DatePicker::make('end_date')
                        ->label('End Date')
                        ->live()
                        ->required(),

                    Select::make('id_country')
                        ->label('Country')
                        ->options(Country::pluck('country', 'id'))
                        ->searchable()
                        ->preload()
                        ->live(),

                    Select::make('id_operator')
                        ->label('Operator')
                        ->options(fn (Get $get) => 
                            Operator::query()
                                ->when($get('id_country'), fn ($q, $cid) => $q->where('id_country', $cid))
                                ->pluck('operator', 'id')
                        )
                        ->searchable()
                        ->preload(),

                    Actions::make([
                        Action::make('apply')
                            ->label('Search')
                            ->button()
                            ->color('primary')
                            ->action(fn () => $this->apply()),

                        Action::make('reset')
                            ->label('Reset')
                            ->button()
                            ->color('secondary')
                            ->action(function () {
                                $this->filters = [
                                    'start_date' => null,
                                    'end_date' => null,
                                    'id_country' => null,
                                    'id_operator' => null,
                                ];
                                $this->form->fill($this->filters);
                                $this->apply();
                            }),
                    ])->columnSpan('full'),
                ]),
        ];
    }

    public function getFormStatePath(): ?string
    {
        return 'filters';
    }

    public function getTitle(): string | Htmlable
    {
        return static::$title;
    }

    public function apply(): void
    {
        $this->computeTotals();
        $this->dispatch('refreshTable');
    }

    protected function buildFilteredQuery(): Builder
    {
        $rawStart = $this->filters['start_date'] ?? null;
        $rawEnd = $this->filters['end_date'] ?? null;
        $countryId = $this->filters['id_country'] ?? null;
        $operatorId = $this->filters['id_operator'] ?? null;

        if (!$rawStart || !$rawEnd) {
            return SummaryDaily::query()->whereRaw('1 = 0');
        }

        try {
            $start = Carbon::parse($rawStart)->toDateString();
            $end = Carbon::parse($rawEnd)->toDateString();
        } catch (\Throwable $e) {
            return SummaryDaily::query()->whereRaw('1 = 0');
        }

        return SummaryDaily::query()
            ->leftJoin('operators', 'summary_daily.id_operator', '=', 'operators.id')
            ->leftJoin('countries', 'summary_daily.id_country', '=', 'countries.id')
            ->whereDate('summary_daily.date', '>=', $start)
            ->whereDate('summary_daily.date', '<=', $end)
            ->when($countryId, fn($q) => $q->where('summary_daily.id_country', $countryId))
            ->when($operatorId, fn($q) => $q->where('summary_daily.id_operator', $operatorId))
            ->groupBy(['summary_daily.id_operator', 'countries.country', 'operators.operator', 'countries.convert_usd'])
            ->select([
                DB::raw("MAX(summary_daily.id) as id"),
                'summary_daily.id_operator',
                'countries.country as country_name',
                'operators.operator as operator_name',
                DB::raw("SUM(summary_daily.mt_success) as total_mt_success"),
                DB::raw("SUM(summary_daily.mt_failed) as total_mt_failed"),
                DB::raw("SUM(summary_daily.mo_reg) as total_reg"),
                DB::raw("SUM(summary_daily.mo_unreg) as total_unreg"),
                DB::raw("SUM(summary_daily.revenue) as total_revenue"),
                DB::raw("ROUND(SUM(summary_daily.revenue * COALESCE((CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 0)), 0) as total_revenue_usd"),
            ]);
    }

    protected function computeTotals(): void
    {
        $rawStart = $this->filters['start_date'] ?? null;
        $rawEnd = $this->filters['end_date'] ?? null;

        if (!$rawStart || !$rawEnd) {
            $this->total_mt = 0;
            $this->total_mo = 0;
            $this->total_revenue = 0;
            return;
        }

        try {
            $start = Carbon::parse($rawStart)->toDateString();
            $end = Carbon::parse($rawEnd)->toDateString();
        } catch (\Throwable $e) {
            return;
        }

        $row = SummaryDaily::query()
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->when($this->filters['id_country'], fn($q, $c) => $q->where('id_country', $c))
            ->when($this->filters['id_operator'], fn($q, $o) => $q->where('id_operator', $o))
            ->selectRaw('SUM(mt_success) as sum_mt, SUM(mo_reg) as sum_mo, SUM(revenue) as sum_revenue')
            ->first();

        $this->total_mt = (int) ($row->sum_mt ?? 0);
        $this->total_mo = (int) ($row->sum_mo ?? 0);
        $this->total_revenue = (float) ($row->sum_revenue ?? 0);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->buildFilteredQuery())
            ->columns([
                TextColumn::make('start_date_display')
                    ->label('Start Date')
                    ->getStateUsing(fn () => $this->filters['start_date'] ?? '-'),
                TextColumn::make('end_date_display')
                    ->label('End Date')
                    ->getStateUsing(fn () => $this->filters['end_date'] ?? '-'),
                TextColumn::make('country_name')->label('Country')->sortable(),
                TextColumn::make('operator_name')->label('Operator')->sortable(),
                TextColumn::make('total_mt_success')->label('MT Success')->numeric(),
                TextColumn::make('total_mt_failed')->label('MT Failed')->numeric(),
                TextColumn::make('total_reg')->label('Total Reg')->numeric(),
                TextColumn::make('total_unreg')->label('Total Unreg')->numeric(),
                TextColumn::make('total_revenue')->label('Revenue')->numeric(decimalPlaces: 2),
                TextColumn::make('total_revenue_usd')
                    ->label('Revenue USD')
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.')),
            ])
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50])
            ->headerActions([
                ExportAction::make()
                    ->label('Export Data')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withColumns([
                                Column::make('country_name')->heading('Country'),
                                Column::make('operator_name')->heading('Operator'),
                                Column::make('total_mt_success')->heading('MT Success'),
                                Column::make('total_mt_failed')->heading('MT Failed'),
                                Column::make('total_reg')->heading('Total Reg'),
                                Column::make('total_unreg')->heading('Total Unreg'),
                                Column::make('total_revenue')->heading('Revenue'),
                                Column::make('total_revenue_usd')->heading('Revenue USD'),
                            ]),
                    ]),
            ])
            ->actions([
                TableAction::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => "Details for " . $record->operator_name)
                    ->modalSubmitAction(false)
                    ->modalContent(function ($record) {
                        try {
                            $start = Carbon::parse($this->filters['start_date'])->toDateString();
                            $end = Carbon::parse($this->filters['end_date'])->toDateString();
                        } catch (\Throwable $e) {
                            return "Invalid date range.";
                        }

                        $details = SummaryDaily::query()
                            ->join('operators', 'summary_daily.id_operator', '=', 'operators.id')
                            ->join('countries', 'summary_daily.id_country', '=', 'countries.id')
                            ->join('services', 'summary_daily.id_service', '=', 'services.id')
                            ->where('summary_daily.id_operator', $record->id_operator)
                            ->whereDate('summary_daily.date', '>=', $start)
                            ->whereDate('summary_daily.date', '<=', $end)
                            ->select([
                                'summary_daily.date',
                                'operators.operator',
                                'services.service',
                                'summary_daily.mt_success',
                                'summary_daily.mt_failed',
                                'summary_daily.mo_reg as reg',
                                'summary_daily.mo_unreg as unreg',
                                'summary_daily.revenue',
                                DB::raw("ROUND(summary_daily.revenue * COALESCE((CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 0), 0) as revenue_usd"),
                            ])
                            ->orderBy('summary_daily.date', 'desc')
                            ->get();

                        return view('filament.pages.summary-weekly-services-modal', [
                            'details' => $details,
                        ]);
                    })
                    ->extraModalActions([
                        TableAction::make('export_details')
                            ->label('Export Excel')
                            ->color('success')
                            ->icon('heroicon-o-document-arrow-down')
                            ->action(function ($record) {
                                try {
                                    $start = Carbon::parse($this->filters['start_date'])->toDateString();
                                    $end = Carbon::parse($this->filters['end_date'])->toDateString();
                                } catch (\Throwable $e) {
                                    return;
                                }

                                return \pxlrbt\FilamentExcel\Actions\Pages\ExportAction::make()
                                    ->exports([
                                        ExcelExport::make()
                                            ->modifyQueryUsing(fn() => SummaryDaily::query()
                                                ->join('operators', 'summary_daily.id_operator', '=', 'operators.id')
                                                ->join('countries', 'summary_daily.id_country', '=', 'countries.id')
                                                ->join('services', 'summary_daily.id_service', '=', 'services.id')
                                                ->where('summary_daily.id_operator', $record->id_operator)
                                                ->whereDate('summary_daily.date', '>=', $start)
                                                ->whereDate('summary_daily.date', '<=', $end)
                                                ->select([
                                                    'summary_daily.date',
                                                    'summary_daily.id_operator',
                                                    'summary_daily.id_service',
                                                    'summary_daily.id_country',
                                                    'summary_daily.mt_success',
                                                    'summary_daily.mt_failed',
                                                    'summary_daily.mo_reg',
                                                    'summary_daily.mo_unreg',
                                                    'summary_daily.revenue',
                                                ])
                                            )
                                            ->withColumns([
                                                Column::make('date')->heading('Date'),
                                                Column::make('operator.operator')->heading('Operator'),
                                                Column::make('service.service')->heading('Service'),
                                                Column::make('mt_success')->heading('MT Success'),
                                                Column::make('mt_failed')->heading('MT Failed'),
                                                Column::make('mo_reg')->heading('Reg'),
                                                Column::make('mo_unreg')->heading('Unreg'),
                                                Column::make('revenue')->heading('Revenue'),
                                                Column::make('revenue_usd')->heading('Revenue USD')
                                                    ->getStateUsing(function ($record) {
                                                        $rate = $record->country?->convert_usd ?? 0;
                                                        if (strtoupper($record->country?->country ?? '') === 'OMAN') {
                                                            $rate = $rate / 1000;
                                                        }
                                                        return round($record->revenue * $rate);
                                                    }),
                                            ]),
                                    ])->handle();
                            }),
                    ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
