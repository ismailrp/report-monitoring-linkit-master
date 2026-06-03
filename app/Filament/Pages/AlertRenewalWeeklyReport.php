<?php

namespace App\Filament\Pages;

use App\Models\Alert;
use App\Models\Country;
use App\Models\Operator;
use App\Models\SummaryStatus;
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

class AlertRenewalWeeklyReport extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bell-snooze';
    protected static string $view = 'filament.pages.alert-renewal-weekly-report';
    protected static ?string $navigationLabel = 'Alert Renewal Weekly';
    protected static ?string $title = 'Alert Renewal Weekly';
    protected static ?string $navigationGroup = "Alerts & Monitoring";

    public array $filters = [
        'start_date' => null,
        'end_date' => null,
        'id_country' => null,
        'id_operator' => null,
        'min_drop' => null,
    ];

    protected $listeners = [
        'refreshTable' => '$refresh',
    ];

    public function mount(): void
    {
        $this->filters['start_date'] = Carbon::now()->subWeek()->startOfWeek()->toDateString();
        $this->filters['end_date'] = Carbon::now()->subWeek()->endOfWeek()->toDateString();
        $this->form->fill($this->filters);
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

                    Select::make('min_drop')
                        ->label('Min Drop %')
                        ->options([
                            '10' => '10%',
                            '20' => '20%',
                            '30' => '30%',
                            '40' => '40%',
                            '50' => '50%',
                        ])
                        ->placeholder('10%'),

                    Actions::make([
                        Action::make('apply')
                            ->label('Search')
                            ->button()
                            ->color('primary')
                            ->action(fn () => $this->dispatch('refreshTable')),

                        Action::make('reset')
                            ->label('Reset')
                            ->button()
                            ->color('secondary')
                            ->action(function () {
                                $this->filters = [
                                    'start_date' => Carbon::now()->subWeek()->startOfWeek()->toDateString(),
                                    'end_date' => Carbon::now()->subWeek()->endOfWeek()->toDateString(),
                                    'id_country' => null,
                                    'id_operator' => null,
                                    'min_drop' => null,
                                ];
                                $this->form->fill($this->filters);
                                $this->dispatch('refreshTable');
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

    protected function buildFilteredQuery(): Builder
    {
        $rawStart = $this->filters['start_date'] ?? null;
        $rawEnd = $this->filters['end_date'] ?? null;
        $countryId = $this->filters['id_country'] ?? null;
        $operatorId = $this->filters['id_operator'] ?? null;

        if (!$rawStart || !$rawEnd) {
            return Alert::query()->whereRaw('1 = 0');
        }

        try {
            $start = Carbon::parse($rawStart)->toDateString();
            $end = Carbon::parse($rawEnd)->toDateString();
        } catch (\Throwable $e) {
            return Alert::query()->whereRaw('1 = 0');
        }

        $mondayExpr = "DATE_SUB(alerts.date, INTERVAL (DAYOFWEEK(alerts.date) + 5) % 7 DAY)";
        $weekStartExpr = $mondayExpr;
        $weekEndExpr = "DATE_ADD({$mondayExpr}, INTERVAL 6 DAY)";
        
        // Use the year of the Monday to determine which year's First Monday to compare against
        $jan1Expr = "STR_TO_DATE(CONCAT(YEAR({$mondayExpr}), '-01-01'), '%Y-%m-%d')";
        $firstMondayExpr = "DATE_ADD({$jan1Expr}, INTERVAL (8 - DAYOFWEEK({$jan1Expr}) + 1) % 7 DAY)";
        $weekNumExpr = "FLOOR(DATEDIFF({$mondayExpr}, {$firstMondayExpr}) / 7) + 1";

        return Alert::query()
            ->join('operators', 'alerts.id_operator', '=', 'operators.id')
            ->join('countries', 'alerts.id_country', '=', 'countries.id')
            ->whereBetween('alerts.date', [$start, $end])
            ->whereIn('alerts.hour', [12, 16, 19])
            ->whereRaw("UPPER(alerts.type) = 'REVENUE'")
            ->when($countryId, fn($q) => $q->where('alerts.id_country', $countryId))
            ->when($operatorId, fn($q) => $q->where('alerts.id_operator', $operatorId))
            ->groupBy([
                DB::raw($weekStartExpr),
                DB::raw($weekEndExpr),
                DB::raw($weekNumExpr),
                'alerts.id_operator',
                'alerts.id_country',
                'alerts.hour',
                'countries.country',
                'operators.operator',
                'countries.convert_usd'
            ])
            ->havingRaw('((SUM(alerts.yesterday) - SUM(alerts.today)) / NULLIF(SUM(alerts.yesterday), 0) * 100) >= ?', [(float) ($this->filters['min_drop'] ?? 10)])
            ->select([
                DB::raw("MAX(alerts.id) as id"),
                DB::raw("{$weekStartExpr} as week_start"),
                DB::raw("{$weekEndExpr} as week_end"),
                DB::raw("{$weekNumExpr} as week_number"),
                'alerts.id_operator',
                'alerts.id_country',
                'alerts.hour',
                'countries.country as country_name',
                'operators.operator as operator_name',
                DB::raw("ROUND(SUM(alerts.today * COALESCE((CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 0)), 0) as total_today"),
                DB::raw("ROUND(SUM(alerts.yesterday * COALESCE((CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 0)), 0) as total_yesterday"),
                DB::raw("ROUND(SUM((alerts.yesterday - alerts.today) * COALESCE((CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 0)), 0) as total_drop_revenue"),
                DB::raw("ROUND(((SUM(alerts.yesterday) - SUM(alerts.today)) / NULLIF(SUM(alerts.yesterday), 0)) * 100, 2) as drop_pct")
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->buildFilteredQuery())
            ->columns([
                TextColumn::make('week_number')->label('Week')->sortable(),
                TextColumn::make('week_start')->label('Start Date')->date()->sortable(),
                TextColumn::make('week_end')->label('End Date')->date()->sortable(),
                TextColumn::make('country_name')->label('Country')->sortable(),
                TextColumn::make('hour')->label('Hour')->sortable(),
                TextColumn::make('operator_name')->label('Operator')->sortable(),
                TextColumn::make('total_today')
                    ->label('Week Now')
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.'))
                    ->alignRight(),
                TextColumn::make('total_yesterday')
                    ->label('Week Before')
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.'))
                    ->alignRight(),
                TextColumn::make('total_drop_revenue')
                    ->label('Drop Revenue')
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.'))
                    ->color('danger')
                    ->alignRight(),
                TextColumn::make('drop_pct')
                    ->label('AVG')
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 2) . '%')
                    ->color(fn ($state) => $state > 10 ? 'danger' : 'gray')
                    ->alignRight()
                    ->sortable(),
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
                                Column::make('week_number')->heading('Week'),
                                Column::make('week_start')->heading('Start Date'),
                                Column::make('week_end')->heading('End Date'),
                                Column::make('country_name')->heading('Country'),
                                Column::make('hour')->heading('Hour'),
                                Column::make('operator_name')->heading('Operator'),
                                Column::make('total_today')->heading('Week Now'),
                                Column::make('total_yesterday')->heading('Week Before'),
                                Column::make('total_drop_revenue')->heading('Drop Revenue'),
                                Column::make('drop_pct')->heading('AVG %'),
                            ]),
                    ]),
            ])
            ->actions([
                TableAction::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => "Alert Details for " . $record->operator_name . " (Week " . $record->week_number . ", Hour " . $record->hour . ")")
                    ->modalSubmitAction(false)
                    ->modalContent(function ($record) {
                        try {
                            // Use the week's range instead of the filter range for modal details
                            $start = $record->week_start;
                            $end = $record->week_end;
                        } catch (\Throwable $e) {
                            return "Invalid date range.";
                        }

                        $services = Alert::query()
                            ->with(['service', 'country', 'operator'])
                            ->where('id_operator', $record->id_operator)
                            ->where('hour', $record->hour)
                            ->whereBetween('date', [$start, $end])
                            ->whereRaw("UPPER(type) = 'REVENUE'")
                            ->whereRaw('((yesterday - today) / NULLIF(yesterday, 0) * 100) >= ?', [(float) ($this->filters['min_drop'] ?? 10)])
                            ->orderBy('date', 'desc')
                            ->orderBy('hour', 'desc')
                            ->select(['id', 'id_service', 'id_country', 'id_operator', 'date', 'hour', 'today', 'yesterday'])
                            ->get();

                        // Operator-level summary: all statuses for operator, date range
                        $allStatuses = SummaryStatus::query()
                            ->where('id_operator', $record->id_operator)
                            ->when($record->id_country, fn($q) => $q->where('id_country', $record->id_country))
                            // Remove hour constraint for weekly report to find whatever data is available for those dates
                            ->whereBetween('date', [$start, $end])
                            ->select([
                                'id_service',
                                'date',
                                'hour',
                                'status',
                                \Illuminate\Support\Facades\DB::raw('SUM(total) as total_status'),
                            ])
                            ->groupBy('id_service', 'date', 'hour', 'status')
                            ->orderBy('date', 'desc')
                            ->orderBy('hour', 'desc')
                            ->get();

                        // For each service and date, we only want the LATEST available hour's status in the whole day
                        $latestHourPerDateAndService = $allStatuses->groupBy(function($item) {
                            $d = $item->date instanceof \Carbon\Carbon ? $item->date->format('Y-m-d') : substr((string)$item->date, 0, 10);
                            return $d . '|' . $item->id_service;
                        })->map(fn($group) => $group->max('hour'));

                        $filteredStatuses = $allStatuses->filter(function($item) use ($latestHourPerDateAndService) {
                            $d = $item->date instanceof \Carbon\Carbon ? $item->date->format('Y-m-d') : substr((string)$item->date, 0, 10);
                            $key = $d . '|' . $item->id_service;
                            return $item->hour == $latestHourPerDateAndService[$key];
                        });

                        // Top summary: per status, summed across entire operator + date range
                        $statuses = $filteredStatuses
                            ->groupBy('status')
                            ->map(function ($rows) {
                                return (object) [
                                    'status'       => $rows->first()->status,
                                    'total_status' => $rows->sum('total_status'),
                                ];
                            })
                            ->sortByDesc('total_status')
                            ->values();

                        // Per-service breakdown keyed by "date|id_service"
                        // Since we are showing the LATEST status of the day for each service
                        $serviceStatuses = $filteredStatuses->groupBy(function ($item) {
                            $d = $item->date instanceof \Carbon\Carbon
                                ? $item->date->format('Y-m-d')
                                : substr((string) $item->date, 0, 10);
                            return $d . '|' . $item->id_service;
                        });

                        return view('filament.pages.alert-renewal-weekly-modal', [
                            'services'         => $services,
                            'statuses'         => $statuses,
                            'serviceStatuses'  => $serviceStatuses,
                        ]);
                    }),
            ])
            ->defaultSort('drop_pct', 'desc');
    }
}
