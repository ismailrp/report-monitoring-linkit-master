<?php

namespace App\Filament\Pages;

use App\Models\Alert; // ganti jika model berbeda
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
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
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Illuminate\Support\Facades\View;
use Illuminate\Contracts\View\View as ViewContract;

class AlertReport extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Alerts & Monitoring';

    protected static string $view = 'filament.pages.alert-report';
    protected static ?string $navigationLabel = 'Alerts';
    protected static ?string $title = 'Alerts Report';

    /**
     * Form state only contains requested filters:
     * start_date, end_date, operator, service, type
     */
    public array $filters = [
        'start_date' => null,
        'end_date' => null,
        'start_hour' => null,
        'end_hour' => null,
        'operator' => null,
        'type' => null,
        'min_today' => null,
        'min_yesterday' => null,
        'min_drop' => null,
        'sort_date' => 'desc',
        'sort_hour' => 'desc',
        'sort_today' => null,
        'sort_yesterday' => null,
        'sort_drop' => 'desc',
    ];

    // totals exposed to Blade
    public int|float $total_today = 0;
    public int|float $total_yesterday = 0;
    public int|float $total_drop = 0;

    // listen to dispatch('refreshTable')
    protected $listeners = [
        'refreshTable' => 'handleRefreshTable',
    ];

    public function mount(): void
    {
        $this->form->fill($this->filters);
        $this->computeTotals();
    }

    protected function isSuperAdminUser(): bool
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'hasRole')) {
            return false;
        }
        foreach (['super_admin', 'super-admin', 'superadmin', 'super admin', 'admin'] as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    protected function getOperatorOptions(): array
    {
        if (Schema::hasTable('operators')) {
            return DB::table('operators')->orderBy('operator')->pluck('operator', 'id')->toArray();
        }

        if (Schema::hasColumn('alerts', 'operator')) {
            $rows = Alert::query()->distinct()->orderBy('operator')->pluck('operator')->toArray();
            return array_combine($rows, $rows) ?: [];
        }

        return [];
    }

    protected function getServiceOptions(): array
    {
        if (Schema::hasTable('services')) {
            return DB::table('services')->orderBy('service')->pluck('service', 'id')->toArray();
        }

        if (Schema::hasColumn('alerts', 'service')) {
            $rows = Alert::query()->distinct()->orderBy('service')->pluck('service')->toArray();
            return array_combine($rows, $rows) ?: [];
        }

        if (Schema::hasColumn('alerts', 'id_service')) {
            $rows = Alert::query()->distinct()->orderBy('id_service')->pluck('id_service')->toArray();
            return array_combine($rows, $rows) ?: [];
        }

        return [];
    }

    protected function getTypeOptions(): array
    {
        if (Schema::hasColumn('alerts', 'type')) {
            $rows = Alert::query()->distinct()->orderBy('type')->pluck('type')->toArray();
            return array_combine($rows, $rows) ?: [];
        }
        return [];
    }

    public function getFormSchema(): array
    {
        return [
            Grid::make()
                ->columns([
                    'sm' => 1,
                    'md' => 2,
                    'lg' => 4,
                ])
                ->schema([
                    DatePicker::make('start_date')->label('Start date'),
                    DatePicker::make('end_date')->label('End date'),
                    Select::make('start_hour')
                        ->label('Start Hour')
                        ->options(array_combine(range(0, 23), range(0, 23)))
                        ->placeholder('0'),
                    Select::make('end_hour')
                        ->label('End Hour')
                        ->options(array_combine(range(0, 23), range(0, 23)))
                        ->placeholder('23'),

                    Select::make('operator')
                        ->label('Operator')
                        ->options(fn (Get $get) => $this->getOperatorOptions())
                        ->searchable()
                        ->placeholder('All operators'),

                    Select::make('type')
                        ->label('Type')
                        ->options(fn () => $this->getTypeOptions())
                        ->searchable()
                        ->placeholder('All types'),

                    TextInput::make('min_today')
                        ->label('Min Today')
                        ->numeric(),

                    TextInput::make('min_yesterday')
                        ->label('Min Yesterday')
                        ->numeric(),

                    TextInput::make('min_drop')
                        ->label('Min Drop')
                        ->numeric(),

                    Section::make('Sorting')
                        ->columns(5)
                        ->compact()
                        ->schema([
                            Select::make('sort_date')->label('Sort Date')->options(['' => 'No Sort', 'asc' => 'Asc', 'desc' => 'Desc']),
                            Select::make('sort_hour')->label('Sort Hour')->options(['' => 'No Sort', 'asc' => 'Asc', 'desc' => 'Desc']),
                            Select::make('sort_today')->label('Sort Today')->options(['' => 'No Sort', 'asc' => 'Asc', 'desc' => 'Desc']),
                            Select::make('sort_yesterday')->label('Sort Yesterday')->options(['' => 'No Sort', 'asc' => 'Asc', 'desc' => 'Desc']),
                            Select::make('sort_drop')->label('Sort Drop')->options(['' => 'No Sort', 'asc' => 'Asc', 'desc' => 'Desc']),
                        ]),

                    Actions::make([
                        Action::make('apply')
                            ->label('Search')
                            ->button()
                            ->color('primary')
                            ->action(function () {
                                $this->dispatch('refreshTable');
                            }),

                        Action::make('reset')
                            ->label('Reset')
                            ->button()
                            ->color('secondary')
                            ->action(function () {
                                $this->filters = [
                                    'start_date' => null,
                                    'end_date' => null,
                                    'start_hour' => null,
                                    'end_hour' => null,
                                    'operator' => null,
                                    'type' => null,
                                    'min_today' => null,
                                    'min_yesterday' => null,
                                    'min_drop' => null,
                                    'sort_date' => 'desc',
                                    'sort_hour' => 'desc',
                                    'sort_today' => null,
                                    'sort_yesterday' => null,
                                    'sort_drop' => 'desc',
                                ];
                                $this->form->fill($this->filters);
                                $this->resetTableFilters();
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

    /**
     * Build filtered query used by table and totals
     */
    protected function buildFilteredQuery(): Builder
    {
        $rawStart = $this->filters['start_date'] ?? null;
        $rawEnd = $this->filters['end_date'] ?? null;
        $operator = $this->filters['operator'] ?? null;
        $type = $this->filters['type'] ?? null;

        $start = null;
        if ($rawStart instanceof Carbon) {
            $start = $rawStart->toDateString();
        } elseif (is_string($rawStart) && trim($rawStart) !== '') {
            try {
                $start = Carbon::parse($rawStart)->toDateString();
            } catch (\Throwable $e) {
                $start = null;
            }
        }

        $end = null;
        if ($rawEnd instanceof Carbon) {
            $end = $rawEnd->toDateString();
        } elseif (is_string($rawEnd) && trim($rawEnd) !== '') {
            try {
                $end = Carbon::parse($rawEnd)->toDateString();
            } catch (\Throwable $e) {
                $end = null;
            }
        }

        $query = Alert::query()
            ->join('countries', 'alerts.id_country', '=', 'countries.id')
            ->select([
                'alerts.date',
                'alerts.hour',
                'alerts.id_operator',
                'alerts.id_country',
                'alerts.type',
                DB::raw('MAX(alerts.id) as id'),
                DB::raw('MAX(alerts.created_at) as created_at'),
                DB::raw("SUM(CASE WHEN UPPER(alerts.type) = 'REVENUE' THEN ROUND(alerts.today * (CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 0) ELSE alerts.today END) as today"),
                DB::raw("SUM(CASE WHEN UPPER(alerts.type) = 'REVENUE' THEN ROUND(alerts.yesterday * (CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 0) ELSE alerts.yesterday END) as yesterday"),
                DB::raw("ROUND(AVG(alerts.drop), 2) as avg_drop")
            ])
            ->groupBy([
                'alerts.date',
                'alerts.hour',
                'alerts.id_operator',
                'alerts.id_country',
                'alerts.type',
            ]);

        if (! empty($operator)) {
            if (Schema::hasColumn('alerts', 'id_operator') && is_numeric($operator)) {
                $query->where('id_operator', $operator);
            } elseif (Schema::hasColumn('alerts', 'operator')) {
                $query->where('operator', $operator);
            }
        } else {
            // optionally restrict non-super users to user's operators
            if (! $this->isSuperAdminUser() && Schema::hasTable('user_has_operators')) {
                $user = auth()->user();
                if ($user) {
                    $ids = DB::table('user_has_operators')->where('id_user', $user->id)->pluck('id_operator')->filter()->unique()->values()->toArray();
                    if (! empty($ids) && Schema::hasColumn('alerts', 'id_operator')) {
                        $query->whereIn('id_operator', $ids);
                    } elseif (! empty($ids) && Schema::hasColumn('alerts', 'operator') && Schema::hasTable('operators')) {
                        $names = DB::table('operators')->whereIn('id', $ids)->pluck('operator')->toArray();
                        if (! empty($names)) {
                            $query->whereIn('operator', $names);
                        }
                    } else {
                        $query->whereRaw('0 = 1');
                    }
                }
            }
        }

        if (! empty($type) && Schema::hasColumn('alerts', 'type')) {
            $query->where('alerts.type', $type);
        }

        if (isset($this->filters['start_hour']) && $this->filters['start_hour'] !== '' && Schema::hasColumn('alerts', 'hour')) {
            $query->where('hour', '>=', (int) $this->filters['start_hour']);
        }

        if (isset($this->filters['end_hour']) && $this->filters['end_hour'] !== '' && Schema::hasColumn('alerts', 'hour')) {
            $query->where('hour', '<=', (int) $this->filters['end_hour']);
        }

        // date filter: prefer 'date' column else use created_at
        if (! empty($start) && Schema::hasColumn('alerts', 'date')) {
            $query->whereDate('alerts.date', '>=', $start);
        } elseif (! empty($start) && Schema::hasColumn('alerts', 'created_at')) {
            $query->whereDate('alerts.created_at', '>=', $start);
        }

        if (! empty($end) && Schema::hasColumn('alerts', 'date')) {
            $query->whereDate('alerts.date', '<=', $end);
        } elseif (! empty($end) && Schema::hasColumn('alerts', 'created_at')) {
            $query->whereDate('alerts.created_at', '<=', $end);
        }

        if (! empty($this->filters['min_today'])) {
            $query->having('today', '>=', (float) $this->filters['min_today']);
        }

        if (! empty($this->filters['min_yesterday'])) {
            $query->having('yesterday', '>=', (float) $this->filters['min_yesterday']);
        }

        if (! empty($this->filters['min_drop'])) {
            $query->havingRaw("((SUM(CASE WHEN UPPER(alerts.type) = 'REVENUE' THEN ROUND(alerts.yesterday * (CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 0) ELSE alerts.yesterday END) - SUM(CASE WHEN UPPER(alerts.type) = 'REVENUE' THEN ROUND(alerts.today * (CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 0) ELSE alerts.today END)) / NULLIF(SUM(CASE WHEN UPPER(alerts.type) = 'REVENUE' THEN ROUND(alerts.yesterday * (CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 0) ELSE alerts.yesterday END), 0) * 100) >= ?", [(float) $this->filters['min_drop']]);
        } else {
            $query->havingRaw("((SUM(CASE WHEN UPPER(alerts.type) = 'REVENUE' THEN ROUND(alerts.yesterday * (CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 0) ELSE alerts.yesterday END) - SUM(CASE WHEN UPPER(alerts.type) = 'REVENUE' THEN ROUND(alerts.today * (CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 0) ELSE alerts.today END)) / NULLIF(SUM(CASE WHEN UPPER(alerts.type) = 'REVENUE' THEN ROUND(alerts.yesterday * (CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 0) ELSE alerts.yesterday END), 0) * 100) >= 10");
        }

        // Apply Manual Sorting
        $hasSort = false;
        $orders = [
            'date' => $this->filters['sort_date'] ?? null,
            'hour' => $this->filters['sort_hour'] ?? null,
            'today' => $this->filters['sort_today'] ?? null,
            'yesterday' => $this->filters['sort_yesterday'] ?? null,
            'drop' => $this->filters['sort_drop'] ?? null,
        ];

        foreach ($orders as $col => $dir) {
            if ($dir) {
                if (!$hasSort) {
                    $query->reorder();
                    $hasSort = true;
                }
                
                if ($col === 'drop') {
                    $query->orderByRaw("((SUM(CASE WHEN UPPER(alerts.type) = 'REVENUE' THEN ROUND(alerts.yesterday * (CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 0) ELSE alerts.yesterday END) - SUM(CASE WHEN UPPER(alerts.type) = 'REVENUE' THEN ROUND(alerts.today * (CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 0) ELSE alerts.today END)) / NULLIF(SUM(CASE WHEN UPPER(alerts.type) = 'REVENUE' THEN ROUND(alerts.yesterday * (CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 0) ELSE alerts.yesterday END), 0)) $dir");
                } else {
                    $query->orderBy($col, $dir);
                }
            }
        }

        return $query;
    }

    /**
     * Compute totals for today, yesterday, and drop columns
     */
    protected function computeTotals(): void
    {
        $q = $this->buildFilteredQuery();

        $row = DB::table(DB::raw("({$q->toSql()}) as sub"))
            ->mergeBindings($q->getQuery())
            ->selectRaw('COALESCE(SUM(today), 0) as sum_today, COALESCE(SUM(yesterday), 0) as sum_yesterday')
            ->first();

        $this->total_today = (int) ($row->sum_today ?? 0);
        $this->total_yesterday = (int) ($row->sum_yesterday ?? 0);
        
        if ($this->total_yesterday > 0) {
            $this->total_drop = round((($this->total_yesterday - $this->total_today) / $this->total_yesterday) * 100, 2);
        } else {
            $this->total_drop = 0;
        }
    }

    /**
     * Handler for refreshTable event
     */
    public function handleRefreshTable(): void
    {
        $this->computeTotals();
        $this->resetTable();
    }

    public function clearFilter(string $field): void
    {
        $this->filters[$field] = null;
        $this->form->fill($this->filters);
        $this->handleRefreshTable();
    }

    /**
     * Build Filament table
     */
    /**
     * Build Filament table
     */
    public function table(Table $table): Table
    {
        $query = $this->buildFilteredQuery();

        // Eager load relationships to avoid N+1 if columns exist
        if (Schema::hasColumn('alerts', 'id_operator')) {
             $query->with(['operator']);
        }
        if (Schema::hasColumn('alerts', 'id_service')) {
             $query->with(['service']);
        }

        // We don't need dynamic selects anymore since we use aggregate selects in buildFilteredQuery

        $tableColumns = [];

        $tableColumns[] = TextColumn::make('date')->label('Date')->date('Y-m-d')->sortable();
        $tableColumns[] = TextColumn::make('hour')->label('Hour')->sortable();
        
        $tableColumns[] = TextColumn::make('operator.operator')
            ->label('Operator')
            ->sortable();

        $tableColumns[] = TextColumn::make('type')->label('Type')->sortable()->badge();

        $tableColumns[] = TextColumn::make('today')
            ->label('Today')
            ->formatStateUsing(function ($record, $state) {
                if (strtoupper($record->type) === 'REVENUE') {
                    return number_format((float) $state, 0, ',', '.');
                }
                return number_format((float) $state, 0, ',', '.');
            })
            ->alignRight()
            ->sortable();

        $tableColumns[] = TextColumn::make('yesterday')
            ->label('Yesterday')
            ->formatStateUsing(function ($record, $state) {
                if (strtoupper($record->type) === 'REVENUE') {
                    return number_format((float) $state, 0, ',', '.');
                }
                return number_format((float) $state, 0, ',', '.');
            })
            ->alignRight()
            ->sortable();

        $tableColumns[] = TextColumn::make('drop')
            ->label('Drop')
            ->getStateUsing(function ($record) {
                $today = (float) ($record->today ?? 0);
                $yesterday = (float) ($record->yesterday ?? 0);
                
                if ($yesterday > 0) {
                    $revDrop = $yesterday - $today;
                    return round(($revDrop / $yesterday) * 100, 2);
                }
                return 0;
            })
            ->formatStateUsing(fn ($state) => $state . '%')
            ->alignRight()
            ->sortable()
            ->color(fn ($state) => $state > 0 ? 'danger' : 'success');

        $tableColumns[] = TextColumn::make('avg_drop')
            ->label('Avg Drop')
            ->formatStateUsing(fn ($state) => $state . '%')
            ->alignRight()
            ->sortable()
            ->color('danger');

        return $table
            ->query($query)
            ->columns($tableColumns)
            ->filters([
                // Native filter logic handled in buildFilteredQuery
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => "Discontinued Services ({$record->date} - Hour {$record->hour} - {$record->type})")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(function ($record): ViewContract {
                        $services = Alert::with(['service', 'country', 'operator'])
                            ->whereDate('date', $record->date)
                            ->where('hour', $record->hour)
                            ->where('id_operator', $record->id_operator)
                            ->where('type', $record->type)
                            ->select(['id_service', 'id_country', 'id_operator', 'today', 'yesterday', 'type', 'date', 'hour', 'drop', 'id'])
                            ->orderBy('today', 'desc')
                            ->get();

                        $allStatuses = collect();
                        $targetHour = null;
                        
                        // Only show status breakdown for REVENUE type
                        if (strtoupper($record->type ?? '') === 'REVENUE') {
                            $allStatuses = \App\Models\SummaryStatus::query()
                                ->where('id_operator', $record->id_operator)
                                ->when($record->id_country, fn($q) => $q->where('id_country', $record->id_country))
                                ->whereDate('date', $record->date)
                                ->where('hour', '<=', $record->hour)
                                ->select([
                                    'date',
                                    'hour',
                                    'id_service',
                                    'status',
                                    DB::raw('SUM(total) as total')
                                ])
                                ->groupBy('date', 'hour', 'id_service', 'status')
                                ->get();
                        }

                        // Operator-level summary: group by status across all services for this date/hour range
                        $statuses = $allStatuses
                            ->groupBy('status')
                            ->map(function ($rows) {
                                return (object) [
                                    'status'       => $rows->first()->status,
                                    'total_status' => $rows->sum('total'),
                                ];
                            })
                            ->sortByDesc('total_status')
                            ->values();

                        // Per-service breakdown: keyed by hour|id_service
                        $serviceStatuses = $allStatuses->groupBy(function ($item) {
                            return $item->hour . '|' . $item->id_service;
                        });

                        $operatorName = optional($services->first()?->operator)->operator ?? '-';

                        return view('filament.pages.alert-services-modal', [
                            'services'        => $services,
                            'statuses'        => $statuses,
                            'serviceStatuses' => $serviceStatuses,
                            'operatorName'    => $operatorName,
                            'date'            => $record->date,
                            'hour'            => $record->hour,
                            'statusHour'      => $targetHour,
                        ]);
                    }),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->deselectAllRecordsWhenFiltered(false);
    }
}
