<?php

namespace App\Filament\Pages;

use App\Models\SummaryDaily;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Repeater;
use Carbon\Carbon;

class SubActiveRenewalDaily extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static string $view = 'filament.pages.subactive-renewal-daily';
    protected static ?string $navigationLabel = 'SubActive Renewal Daily';
    protected static ?string $title = 'SubActive Renewal Daily';
    protected static ?string $navigationGroup = 'Daily Reports';

    public array $filters = [
        'start_date' => null,
        'end_date' => null,
        'id_country' => null,
        'id_operator' => null,
        'id_service' => null,
    ];

    // Summary totals exposed to Blade
    public int $total_sub_active = 0;
    public int $total_mt = 0;
    public float $avg_percentage = 0;

    protected $listeners = [
        'refreshTable' => 'handleRefreshTable',
    ];

    public function mount(): void
    {
        $this->form->fill($this->filters);
        $this->computeTotals();
    }

    /**
     * Options for the sort column dropdown.
     */
    protected static function getSortColumnOptions(): array
    {
        return [
            'date' => 'Date',
            'id_country' => 'Country',
            'id_operator' => 'Operator',
            'id_service' => 'Service',
            'sub_active' => 'SubActive',
            'total_mt' => 'Total MT',
            'percentage' => 'Percentage',
        ];
    }

    protected function getCountryOptions(): array
    {
        if (Schema::hasTable('countries')) {
            return DB::table('countries')->orderBy('country')->pluck('country', 'id')->toArray();
        }

        return SummaryDaily::query()
            ->where('hour', 24)
            ->distinct()
            ->orderBy('id_country')
            ->pluck('id_country', 'id_country')
            ->toArray();
    }

    protected function getOperatorOptions(): array
    {
        if (Schema::hasTable('operators')) {
            return DB::table('operators')->orderBy('operator')->pluck('operator', 'id')->toArray();
        }

        return SummaryDaily::query()
            ->where('hour', 24)
            ->distinct()
            ->orderBy('id_operator')
            ->pluck('id_operator', 'id_operator')
            ->toArray();
    }

    protected function getServiceOptions(): array
    {
        if (Schema::hasTable('services')) {
            return DB::table('services')->orderBy('service')->pluck('service', 'id')->toArray();
        }

        return SummaryDaily::query()
            ->where('hour', 24)
            ->distinct()
            ->orderBy('id_service')
            ->pluck('id_service', 'id_service')
            ->toArray();
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
                    DatePicker::make('start_date')->label('Start Date'),
                    DatePicker::make('end_date')->label('End Date'),

                    Select::make('id_country')
                        ->label('Country')
                        ->options(fn () => $this->getCountryOptions())
                        ->searchable()
                        ->placeholder('All countries'),

                    Select::make('id_operator')
                        ->label('Operator')
                        ->options(fn () => $this->getOperatorOptions())
                        ->searchable()
                        ->placeholder('All operators'),

                    Select::make('id_service')
                        ->label('Service')
                        ->options(fn () => $this->getServiceOptions())
                        ->searchable()
                        ->placeholder('All services'),

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
                                    'id_country' => null,
                                    'id_operator' => null,
                                    'id_service' => null,
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

    /**
     * Build the filtered query — only rows with hour = 24
     */
    protected function buildFilteredQuery(): Builder
    {
        $rawStart = $this->filters['start_date'] ?? null;
        $rawEnd = $this->filters['end_date'] ?? null;
        $rawCountry = $this->filters['id_country'] ?? null;
        $rawOperator = $this->filters['id_operator'] ?? null;
        $rawService = $this->filters['id_service'] ?? null;

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

        $country = is_scalar($rawCountry) && trim((string) $rawCountry) !== '' ? $rawCountry : null;
        $operator = is_scalar($rawOperator) && trim((string) $rawOperator) !== '' ? $rawOperator : null;
        $service = is_scalar($rawService) && trim((string) $rawService) !== '' ? $rawService : null;

        $query = SummaryDaily::query()->where('hour', 24);

        if (! empty($country)) {
            $query->where('id_country', $country);
        }

        if (! empty($operator)) {
            $query->where('id_operator', $operator);
        }

        if (! empty($service)) {
            $query->where('id_service', $service);
        }

        if (! empty($start)) {
            $query->whereDate('date', '>=', $start);
        }

        if (! empty($end)) {
            $query->whereDate('date', '<=', $end);
        }

        return $query;
    }

    /**
     * Compute totals for summary cards
     */
    protected function computeTotals(): void
    {
        $q = $this->buildFilteredQuery();

        $row = $q->clone()
            ->selectRaw('
                COALESCE(SUM(sub_active), 0) as sum_sub_active,
                COALESCE(SUM(mt_success + mt_failed), 0) as sum_mt
            ')
            ->first();

        $this->total_sub_active = (int) ($row->sum_sub_active ?? 0);
        $this->total_mt = (int) ($row->sum_mt ?? 0);
        $this->avg_percentage = $this->total_sub_active > 0
            ? round(($this->total_mt / $this->total_sub_active) * 100, 2)
            : 0;
    }

    public function handleRefreshTable(): void
    {
        $this->computeTotals();
    }

    /**
     * Table configuration
     */
    public function table(Table $table): Table
    {
        $query = $this->buildFilteredQuery();

        // Eager load relationships
        $query->with(['country', 'operator', 'service']);

        $query->select([
            'summary_daily.id',
            'summary_daily.date',
            'summary_daily.sub_active',
            'summary_daily.mt_success',
            'summary_daily.mt_failed',
            'summary_daily.id_country',
            'summary_daily.id_operator',
            'summary_daily.id_service',
        ])
        ->selectRaw('(mt_success + mt_failed) as total_mt')
        ->selectRaw('CASE WHEN sub_active > 0 THEN ROUND(((mt_success + mt_failed) / sub_active) * 100, 2) ELSE 0 END as percentage');

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->date(),

                TextColumn::make('country.country')
                    ->label('Country'),

                TextColumn::make('operator.operator')
                    ->label('Operator'),

                TextColumn::make('service.service')
                    ->label('Service'),

                TextColumn::make('sub_active')
                    ->label('SubActive')
                    ->numeric(0, ',', '.')
                    ->alignRight(),

                TextColumn::make('total_mt')
                    ->label('Total MT')
                    ->numeric(0, ',', '.')
                    ->alignRight(),

                TextColumn::make('percentage')
                    ->label('Percentage (%)')
                    ->alignRight()
                    ->numeric(2, ',', '.')
                    ->suffix(' %'),
            ])
            ->filters([
                Filter::make('sort')
                    ->label('Sort / Urutkan')
                    ->form([
                        Repeater::make('sorts')
                            ->label('Sort Rules')
                            ->schema([
                                Select::make('column')
                                    ->label('Column')
                                    ->options(static::getSortColumnOptions())
                                    ->required(),

                                Select::make('direction')
                                    ->label('Direction')
                                    ->options([
                                        'asc' => '↑ Ascending (A-Z, Kecil-Besar)',
                                        'desc' => '↓ Descending (Z-A, Besar-Kecil)',
                                    ])
                                    ->default('asc')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('+ Tambah Sort')
                            ->reorderable()
                            ->collapsible(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $sorts = $data['sorts'] ?? [];

                        if (empty($sorts)) {
                            return;
                        }

                        // Remove any existing orders so our sort rules take full control
                        $query->reorder();

                        foreach ($sorts as $sort) {
                            $col = $sort['column'] ?? null;
                            $dir = ($sort['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

                            if (! $col) {
                                continue;
                            }

                            // Map sort key to actual SQL column/expression
                            match ($col) {
                                'date' => $query->orderBy('summary_daily.date', $dir),
                                'id_country' => $query->orderBy('summary_daily.id_country', $dir),
                                'id_operator' => $query->orderBy('summary_daily.id_operator', $dir),
                                'id_service' => $query->orderBy('summary_daily.id_service', $dir),
                                'sub_active' => $query->orderBy('summary_daily.sub_active', $dir),
                                'total_mt' => $query->orderByRaw("(mt_success + mt_failed) {$dir}"),
                                'percentage' => $query->orderByRaw("CASE WHEN sub_active > 0 THEN ((mt_success + mt_failed) / sub_active) ELSE 0 END {$dir}"),
                                default => null,
                            };
                        }
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $sorts = $data['sorts'] ?? [];
                        if (empty($sorts)) {
                            return null;
                        }

                        $labels = static::getSortColumnOptions();
                        $parts = [];

                        foreach ($sorts as $sort) {
                            $col = $sort['column'] ?? null;
                            $dir = $sort['direction'] ?? 'asc';
                            if ($col && isset($labels[$col])) {
                                $arrow = $dir === 'desc' ? '↓' : '↑';
                                $parts[] = $labels[$col] . ' ' . $arrow;
                            }
                        }

                        return empty($parts) ? null : 'Sort: ' . implode(', ', $parts);
                    }),
            ])
            ->defaultSort('date', 'desc')
            ->deferLoading()
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50])
            ->deselectAllRecordsWhenFiltered(false);
    }
}
