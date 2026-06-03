<?php

namespace App\Filament\Resources\SrHourResource\Pages;

use App\Models\SrHour;
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
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Forms\Get;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class SrHourDaily extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static string $view = 'filament.resources.s-r-hour-resource.pages.s-r-hour-page';
    protected static ?string $navigationLabel = 'SR Daily';
    protected static ?string $title = 'SR Daily';
    protected static ?string $navigationGroup = "SR";

    public array $filters = [
        'start_date' => null,
        'end_date' => null,
        'operator' => null,
        'service' => null,
    ];

    protected $listeners = ['refreshTable' => '$refresh'];

    public function mount(): void
    {
        $this->filters['start_date'] = Carbon::today()->toDateString();
        $this->form->fill($this->filters);
    }

    protected function isSuperAdminUser(): bool
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'hasRole')) {
            return false;
        }
        foreach (['super_admin', 'super-admin', 'superadmin', 'super admin'] as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Ambil operator untuk dropdown:
     * - super-admin => semua operator (dari tabel operators jika ada, fallback ke sr_hours.operator)
     * - non-super => operator dari pivot user_has_operators (join operators jika ada), fallback ke sr_hours
     *
     * Mengembalikan array ['label' => 'label'].
     */
    protected function getOperatorOptionsForUser(): array
    {
        $user = auth()->user();
        $isSuper = $this->isSuperAdminUser();

        if ($isSuper) {
            if (Schema::hasTable('operators')) {
                $ops = DB::table('operators')->distinct()->orderBy('operators.operator')->pluck('operators.operator')->toArray();
            } else {
                $ops = SrHour::query()->distinct()->orderBy('operator')->pluck('operator')->toArray();
            }

            return array_combine($ops, $ops) ?: [];
        }

        if (! $user || ! Schema::hasTable('user_has_operators')) {
            return [];
        }

        if (Schema::hasTable('operators')) {
            $ops = DB::table('user_has_operators')
                ->join('operators', 'user_has_operators.id_operator', '=', 'operators.id')
                ->where('user_has_operators.id_user', $user->id)
                ->distinct()
                ->orderBy('operators.operator')
                ->pluck('operators.operator')
                ->toArray();

            return array_combine($ops, $ops) ?: [];
        }

        // pivot menyimpan nama operator langsung
        $ops = DB::table('user_has_operators')
            ->where('user_has_operators.id_user', $user->id)
            ->distinct()
            ->orderBy('user_has_operators.operator')
            ->pluck('operator')
            ->toArray();

        return array_combine($ops, $ops) ?: [];
    }

    /**
     * Ambil service sesuai operator (mirip MODaily).
     * Mengembalikan array ['label' => 'label'] atau [].
     */
    /**
 * Ambil service berdasarkan relasi yang benar: services <-> operators
 * Tidak mengambil dari sr_hours.
 *
 * Mengembalikan array ['label' => 'label'] atau [].
 */
protected function getServiceOptionsForOperator($operator): array
{
    if (! $operator || ! is_string($operator) && ! is_numeric($operator)) {
        return [];
    }

    $operator = trim((string) $operator);

    $hasOperatorsTable = Schema::hasTable('operators');
    $hasServicesTable = Schema::hasTable('services');

    if (! $hasServicesTable) {
        // Tidak ada tabel services -> kosongkan
        return [];
    }

    // determine service display column in services table
    $svcCol = Schema::hasColumn('services', 'service')
        ? 'service'
        : (Schema::hasColumn('services', 'name') ? 'name' : null);

    if (! $svcCol) {
        // tidak ada kolom namanya di services
        return [];
    }

    $opId = null;
    // Jika ada table operators, coba resolve operator id dulu
    if ($hasOperatorsTable) {
        // coba match by operator name column
        $opRow = DB::table('operators')
            ->where('operators.operator', $operator)
            ->first();

        // jika tidak ketemu dan $operator numeric => cari by id
        if (! $opRow && is_numeric($operator)) {
            $opRow = DB::table('operators')->where('operators.id', (int) $operator)->first();
        }

        if ($opRow) {
            $opId = $opRow->id;
        }
    }

    // CASE 1: services.id_operator exists -> langsung ambil services untuk opId
    if ($opId !== null && Schema::hasColumn('services', 'id_operator')) {
        $services = DB::table('services')
            ->where('services.id_operator', $opId)
            ->distinct()
            ->orderBy("services.{$svcCol}")
            ->pluck("services.{$svcCol}")
            ->toArray();

        return array_combine($services, $services) ?: [];
    }

    // CASE 2: pivot table operator_services / operators_services
    if ($opId !== null) {
        $pivot = null;
        if (Schema::hasTable('operator_services')) {
            $pivot = 'operator_services';
        } elseif (Schema::hasTable('operators_services')) {
            $pivot = 'operators_services';
        } elseif (Schema::hasTable('operator_service')) {
            $pivot = 'operator_service';
        }

        if ($pivot) {
            // try common pivot column names
            $opCol = null;
            $svcColPivot = null;
            // guess pivot columns
            foreach (['operator_id', 'id_operator'] as $c) {
                if (Schema::hasColumn($pivot, $c)) {
                    $opCol = $c;
                    break;
                }
            }
            foreach (['service_id', 'id_service'] as $c) {
                if (Schema::hasColumn($pivot, $c)) {
                    $svcColPivot = $c;
                    break;
                }
            }

            if ($opCol && $svcColPivot) {
                $services = DB::table('services')
                    ->join($pivot, "services.id", '=', "{$pivot}.{$svcColPivot}")
                    ->where("{$pivot}.{$opCol}", $opId)
                    ->distinct()
                    ->orderBy("services.{$svcCol}")
                    ->pluck("services.{$svcCol}")
                    ->toArray();

                return array_combine($services, $services) ?: [];
            }
        }
    }

    // CASE 3: services.operator (nama operator dicatat di services)
    if (Schema::hasColumn('services', 'operator')) {
        // accept both name match and numeric operator if numeric
        if (is_numeric($operator)) {
            // jika operator numeric, mungkin services.operator menyimpan id dalam bentuk string
            $services = DB::table('services')
                ->where('services.operator', (int) $operator)
                ->orWhere('services.operator', $operator)
                ->distinct()
                ->orderBy("services.{$svcCol}")
                ->pluck("services.{$svcCol}")
                ->toArray();
        } else {
            $services = DB::table('services')
                ->where('services.operator', $operator)
                ->distinct()
                ->orderBy("services.{$svcCol}")
                ->pluck("services.{$svcCol}")
                ->toArray();
        }

        return array_combine($services, $services) ?: [];
    }

    // jika kita sampai sini => tidak ada cara langsung dari services -> operator yang cocok
    // sesuai permintaan Anda, jangan fallback ke sr_hours; kembalikan []
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
                    DatePicker::make('start_date')->label('Start Date'),
                    DatePicker::make('end_date')->label('End Date'),

                    Select::make('operator')
                        ->label('Operator')
                        ->options(fn () => $this->getOperatorOptionsForUser())
                        ->searchable()
                        ->preload(),

                    Select::make('service')
                        ->label('Service')
                        ->options(fn (Get $get) => $this->getServiceOptionsForOperator($get('operator')))
                        ->searchable()
                        ->preload(),

                    Actions::make([
                        Action::make('search_action')
                            ->label('Search')
                            ->button()
                            ->color('primary')
                            ->action(function () {
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

    public function table(Table $table): Table
    {
        // --- normalize filters ---
        $rawStart = $this->filters['start_date'] ?? null;
        $rawEnd = $this->filters['end_date'] ?? null;
        $rawService = $this->filters['service'] ?? null;
        $rawOperator = $this->filters['operator'] ?? null;

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

        $service = is_string($rawService) && trim($rawService) !== '' ? trim($rawService) : null;
        $operator = is_string($rawOperator) && trim($rawOperator) !== '' ? trim($rawOperator) : null;
        // -----------------------------

        // detect relation columns/tables
        $hasIdOperator = Schema::hasColumn('sr_hours', 'id_operator');
        $hasIdService = Schema::hasColumn('sr_hours', 'id_service');
        $hasOperatorCol = Schema::hasColumn('sr_hours', 'operator');
        $hasServiceCol = Schema::hasColumn('sr_hours', 'service');

        $hasOperatorsTable = Schema::hasTable('operators');
        $hasServicesTable = Schema::hasTable('services');

        // determine expressions for operator & service
        if ($hasIdOperator && $hasOperatorsTable) {
            $operatorSelect = 'operators.operator';
            $operatorJoin = true;
        } elseif ($hasOperatorCol) {
            $operatorSelect = 'sr_hours.operator';
            $operatorJoin = false;
        } else {
            $operatorSelect = "''";
            $operatorJoin = false;
        }

        if ($hasIdService && $hasServicesTable) {
            $serviceColName = Schema::hasColumn('services', 'service') ? 'service' : (Schema::hasColumn('services', 'name') ? 'name' : 'service');
            $serviceSelect = "services.{$serviceColName}";
            $serviceJoin = true;
        } elseif ($hasServiceCol) {
            $serviceSelect = 'sr_hours.service';
            $serviceJoin = false;
        } else {
            $serviceSelect = "''";
            $serviceJoin = false;
        }

        $isSuper = $this->isSuperAdminUser();

        // operator options for non-super default
        $operatorOptions = array_keys($this->getOperatorOptionsForUser());

        // base query
        $query = SrHour::query()->from('sr_hours');

        if ($operatorJoin) {
            $query->leftJoin('operators', 'sr_hours.id_operator', '=', 'operators.id');
        }
        if ($serviceJoin) {
            $query->leftJoin('services', 'sr_hours.id_service', '=', 'services.id');
        }

        $query->when($start, fn (Builder $q) => $q->whereDate('sr_hours.date', '>=', $start));
        $query->when($end, fn (Builder $q) => $q->whereDate('sr_hours.date', '<=', $end));

        // only hour 24 rows are counted
        $query->where('sr_hours.hour', 24);

        // operator/service filtering logic
        if ($operator) {
            // operator explicitly selected
            if ($operatorJoin || $hasOperatorCol) {
                $query->where(DB::raw($operatorSelect), $operator);
            } else {
                if ($hasOperatorCol) {
                    $query->where('sr_hours.operator', $operator);
                } else {
                    $query->whereRaw('0 = 1');
                }
            }
        } else {
            // no operator selected
            if (! $isSuper) {
                if (! empty($operatorOptions)) {
                    $query->whereIn(DB::raw($operatorSelect), $operatorOptions);
                } else {
                    $query->whereRaw('0 = 1');
                }
            }
        }

        if ($service) {
            if ($serviceJoin || $hasServiceCol) {
                $query->where(DB::raw($serviceSelect), $service);
            } else {
                if ($hasServiceCol) {
                    $query->where('sr_hours.service', $service);
                } else {
                    $query->whereRaw('0 = 1');
                }
            }
        }

        // group by matching non-aggregates
        $groupBys = [
            'sr_hours.date',
            DB::raw($operatorSelect),
            DB::raw($serviceSelect),
        ];
        $query->groupBy($groupBys);

        // select: alias operator/service to operator_name/service_name so Filament can read them
        $selects = [
            DB::raw('sr_hours.date as date'),
            DB::raw("{$operatorSelect} as operator_name"),
            DB::raw("{$serviceSelect} as service_name"),

            // daily totals
            DB::raw("SUM(sr_hours.total_click) AS total_click"),
            DB::raw("SUM(sr_hours.total_mo) AS total_mo"),
            DB::raw("COALESCE(SUM(sr_hours.total_mo) / NULLIF(SUM(sr_hours.total_click), 0), 0) * 100 AS sr"),

            DB::raw("MD5(CONCAT(sr_hours.date, {$operatorSelect}, {$serviceSelect})) as id"),
        ];
        $query->select($selects);

        return $table
            ->description('⏱️ Latest data is updated every 30 minutes')
            ->query($query)
            ->columns([
                TextColumn::make('date')->date()->sortable()->label('DATE'),
                TextColumn::make('operator_name')->label('Operator')->sortable(),
                TextColumn::make('service_name')->label('Service')->sortable(),
                TextColumn::make('total_click')->label('Total Click')->sortable()->alignRight()->numeric(),
                TextColumn::make('total_mo')->label('Total MO')->sortable()->alignRight()->numeric(),
                TextColumn::make('sr')->label('SR (%)')->sortable()->suffix('%')->alignRight()->numeric(decimalPlaces: 2),
            ])
            ->defaultSort('date', 'desc')
            ->deferLoading()
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50])
            ->headerActions([
                ExportAction::make('export')
                    ->label('Export Data')
                    ->color('success')
                    ->exports([
                        ExcelExport::make('report')
                            ->fromTable()
                            ->withColumns([
                                Column::make('date'),
                                Column::make('operator_name')->heading('Operator'),
                                Column::make('service_name')->heading('Service'),
                                Column::make('total_click')->heading('Total Click'),
                                Column::make('total_mo')->heading('Total MO'),
                                Column::make('sr')->heading('SR (%)'),
                            ])
                    ])
            ])
            ->deselectAllRecordsWhenFiltered(false);
    }
}
