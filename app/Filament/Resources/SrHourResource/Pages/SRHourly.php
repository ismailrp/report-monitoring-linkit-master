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
use Illuminate\Support\Facades\Schema;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Forms\Get;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class SRHourly extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static string $view = 'filament.resources.s-r-hour-resource.pages.s-r-hour-page';
    protected static ?string $navigationLabel = 'SR Hourly';
    protected static ?string $title = 'SR Hourly';
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

                    // Operator: ambil dari tabel operators jika ada, fallback ke distinct sr_hours.operator
                    Select::make('operator')
                        ->label('Operator')
                        ->options(function () {
                            if (Schema::hasTable('operators')) {
                                return DB::table('operators')
                                    ->orderBy('operator')
                                    ->pluck('operator', 'operator')
                                    ->toArray();
                            }

                            return SrHour::query()
                                ->distinct()
                                ->orderBy('operator')
                                ->pluck('operator', 'operator')
                                ->toArray();
                        })
                        ->searchable()
                        ->reactive()
                        ->preload()
                        ->afterStateUpdated(function (callable $set) {
                            // kosongkan service saat operator berubah
                            $set('service', null);
                        }),

                    // Service: ambil dari tabel services yang terkait ke operator (tidak dari sr_hours)
                    Select::make('service')
                        ->label('Service')
                        ->options(fn (Get $get) => $this->getServicesOptionsByOperator($get('operator')))
                        ->searchable()
                        ->reactive(),

                    Actions::make([
                        Action::make('search_action')
                            ->label('Search')
                            ->button()
                            ->color('primary')
                            ->action(function () {
                                // sinkronkan filters ke form state sudah otomatis via getFormStatePath
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
        $isFiltered = Arr::where($this->filters, fn ($value) => filled($value));

        $query = SrHour::query()
            ->when($isFiltered, function (Builder $query) {
                return $query
                    ->when($this->filters['start_date'], fn (Builder $query, $date) => $query->whereDate('date', '>=', $date))
                    ->when($this->filters['end_date'], fn (Builder $query, $date) => $query->whereDate('date', '<=', $date))
                    ->when($this->filters['operator'], fn (Builder $query, $operator) => $query->where('operator', $operator))
                    ->when($this->filters['service'], fn (Builder $query, $service) => $query->where('service', $service))
                    ->groupBy('date', 'operator', 'service')
                    ->select(array_merge([
                        'date',
                        'operator',
                        'service',
                        // DB::raw('COALESCE(AVG(sr), 0) AS total_sr'),
                        DB::raw("MD5(CONCAT(date, operator, service)) as id")
                    ], $this->getHourlyTransactionColumns()));
            }, function (Builder $query) {
                // jika tidak ada filter terisi, jangan tampilkan data
                return $query->whereRaw('0 = 1');
            });

        return $table
            ->description('⏱️ Latest data is updated every 30 minutes')
            ->query($query)
            ->columns(array_merge([
                TextColumn::make('date')
                    ->date()
                    ->sortable()
                    ->label('Date'),
                TextColumn::make('operator')
                    ->sortable()
                    ->label('Operator'),
                TextColumn::make('service')
                    ->sortable()
                    ->label('Service'),
                // TextColumn::make('total_sr')
                //     ->label('Total Avg SR (%)')
                //     ->sortable()
                //     ->suffix('%')
                //     ->alignRight()
                //     ->numeric(decimalPlaces: 2),
            ], $this->getHourlyTransactionTableColumns()))
            ->defaultSort('date', 'asc')
            ->defaultSort('operator')
            ->defaultSort('service')
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
                            ->withColumns(array_merge([
                                Column::make('date'),
                                Column::make('operator'),
                                Column::make('service'),
                                // Column::make('total_sr')->heading('Total Avg SR'),
                            ], $this->getHourlyExportColumns()))
                    ])
            ])
            ->deselectAllRecordsWhenFiltered(false);
    }

    /**
     * Ambil opsi service dari tabel services (hubungkan ke operator jika mungkin).
     * Mengembalikan array ['label' => 'label'] atau [].
     */
    protected function getServicesOptionsByOperator($operator): array
    {
        // if no services table -> return empty
        if (! Schema::hasTable('services')) {
            return [];
        }

        // determine service display column
        $svcCol = Schema::hasColumn('services', 'service')
            ? 'service'
            : (Schema::hasColumn('services', 'name') ? 'name' : null);

        if (! $svcCol) {
            return [];
        }

        // no operator selected -> return all services (label => label)
        if (! $operator) {
            $services = DB::table('services')
                ->distinct()
                ->orderBy($svcCol)
                ->pluck($svcCol)
                ->toArray();

            return array_combine($services, $services) ?: [];
        }

        $operator = trim((string) $operator);

        // try resolve operator id if operators table exists
        $opId = null;
        if (Schema::hasTable('operators')) {
            $opRow = DB::table('operators')->where('operator', $operator)->first();
            if (! $opRow && is_numeric($operator)) {
                $opRow = DB::table('operators')->where('id', (int) $operator)->first();
            }
            if ($opRow) {
                $opId = $opRow->id;
            }
        }

        // CASE A: services.id_operator exists -> simple FK
        if ($opId !== null && Schema::hasColumn('services', 'id_operator')) {
            $services = DB::table('services')
                ->where('services.id_operator', $opId)
                ->distinct()
                ->orderBy($svcCol)
                ->pluck($svcCol)
                ->toArray();

            return array_combine($services, $services) ?: [];
        }

        // CASE B: pivot table between operators and services
        if ($opId !== null) {
            $pivot = null;
            foreach (['operator_services','operators_services','operator_service','operator_service_pivot'] as $p) {
                if (Schema::hasTable($p)) {
                    $pivot = $p;
                    break;
                }
            }

            if ($pivot) {
                // guess pivot column names
                $opCol = Schema::hasColumn($pivot, 'operator_id') ? 'operator_id' :
                         (Schema::hasColumn($pivot, 'id_operator') ? 'id_operator' : null);
                $svcColPivot = Schema::hasColumn($pivot, 'service_id') ? 'service_id' :
                               (Schema::hasColumn($pivot, 'id_service') ? 'id_service' : null);

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

        // CASE C: services.operator column (nama atau id disimpan di services)
        if (Schema::hasColumn('services', 'operator')) {
            $q = DB::table('services')->distinct()->orderBy($svcCol);
            if (is_numeric($operator)) {
                $q->where(function ($q2) use ($operator) {
                    $q2->where('services.operator', (int) $operator)
                       ->orWhere('services.operator', $operator);
                });
            } else {
                $q->where('services.operator', $operator);
            }

            $services = $q->pluck($svcCol)->toArray();
            return array_combine($services, $services) ?: [];
        }

        // default: return all services (UX-friendly). Ubah ke [] jika ingin ketat.
        $services = DB::table('services')
            ->distinct()
            ->orderBy($svcCol)
            ->pluck($svcCol)
            ->toArray();

        return array_combine($services, $services) ?: [];
    }

    /**
     * Buat kolom SELECT untuk hourly SR (hanya 0..24) - digunakan di SELECT SQL.
     */
    private function getHourlyTransactionColumns(): array
    {
        $columns = [];
        // jam 0..24
        for ($i = 0; $i <= 24; $i++) {
            $columns[] = DB::raw("
                COALESCE(AVG(CASE WHEN hour = {$i} THEN sr ELSE NULL END), 0) AS sr_{$i}
            ");
        }
        return $columns;
    }

    /**
     * Buat kolom Table untuk jam 0..24 (sesuai alias sr_0 .. sr_24)
     */
    private function getHourlyTransactionTableColumns(): array
    {
        $columns = [];
        // tampilkan urut dari jam 0 ke 24
        for ($i = 0; $i <= 24; $i++) {
            $columns[] = TextColumn::make("sr_{$i}")
                ->label("SR {$i} (%)")
                ->sortable()
                ->suffix('%')
                ->alignRight()
                ->numeric(decimalPlaces: 2);
        }
        return $columns;
    }

    /**
     * Kolom export sesuai sr_0..sr_24
     */
    private function getHourlyExportColumns(): array
    {
        $columns = [];
        for ($i = 0; $i <= 24; $i++) {
            $columns[] = Column::make("sr_{$i}")->heading("SR {$i}");
        }
        return $columns;
    }
}
