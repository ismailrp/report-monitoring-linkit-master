<?php

namespace App\Filament\Resources\MoHourResource\Pages;

use App\Filament\Resources\MoHourResource;
use App\Models\MoHour;
use Carbon\Carbon;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
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

class MOHourly extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    protected static string $resource = MoHourResource::class;
    protected static string $view = 'filament.resources.m-o-hour-resource.pages.m-o-hourly';
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'MO Hourly';
    protected static ?string $title = 'MO Hourly';
    protected static ?string $navigationGroup = 'MO';

    public array $filters = [
        'start_date' => null,
        'end_date'   => null,
        'operator'   => null,
        'service'    => null,
    ];

    protected $listeners = ['refreshTable' => '$refresh'];

    /* =====================================================
     |  WAJIB: Record Key untuk GROUP BY
     ===================================================== */
    public function getTableRecordKey($record): string
    {
        return (string) ($record->id ?? md5(
            ($record->date ?? '') .
            ($record->operator_name ?? '') .
            ($record->service_name ?? '')
        ));
    }

    public function mount(): void
    {
        $this->filters['start_date'] = Carbon::today()->toDateString();
        $this->form->fill($this->filters);
    }

    /* =====================================================
     |  ROLE CHECK
     ===================================================== */
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

    /* =====================================================
     |  OPERATOR OPTIONS (ROLE AWARE)
     ===================================================== */
    protected function getOperatorOptionsForUser(): array
    {
        $user = auth()->user();

        if ($this->isSuperAdminUser()) {
            return DB::table('operators')
                ->orderBy('operator')
                ->pluck('operator', 'operator')
                ->toArray();
        }

        if (! $user || ! Schema::hasTable('user_has_operators')) {
            return [];
        }

        return DB::table('user_has_operators')
            ->join('operators', 'user_has_operators.id_operator', '=', 'operators.id')
            ->where('user_has_operators.id_user', $user->id)
            ->orderBy('operators.operator')
            ->pluck('operators.operator', 'operators.operator')
            ->toArray();
    }

    /* =====================================================
     |  SERVICE OPTIONS (DEPEND ON OPERATOR)
     ===================================================== */
    protected function getServiceOptionsForOperator(?string $operator): array
    {
        if (! $operator) {
            return [];
        }

        return DB::table('mo_hours')
            ->join('services', 'mo_hours.id_service', '=', 'services.id')
            ->join('operators', 'mo_hours.id_operator', '=', 'operators.id')
            ->where('operators.operator', $operator)
            ->distinct()
            ->orderBy('services.service')
            ->pluck('services.service', 'services.service')
            ->toArray();
    }

    /* =====================================================
     |  FILTER FORM
     ===================================================== */
    public function getFormSchema(): array
    {
        return [
            Grid::make()->columns(4)->schema([
                DatePicker::make('start_date')->label('Start Date'),
                DatePicker::make('end_date')->label('End Date'),

                Select::make('operator')
                    ->label('Operator')
                    ->options(fn () => $this->getOperatorOptionsForUser())
                    ->searchable()
                    ->preload(),

                Select::make('service')
                    ->label('Service')
                    ->options(fn (Get $get) =>
                        $this->getServiceOptionsForOperator($get('operator'))
                    )
                    ->searchable()
                    ->preload(),

                Actions::make([
                    Action::make('search')
                        ->label('Search')
                        ->button()
                        ->color('primary')
                        ->action(fn () => $this->dispatch('refreshTable')),
                ])->columnSpanFull(),
            ]),
        ];
    }

    public function getFormStatePath(): ?string
    {
        return 'filters';
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title;
    }

    /* =====================================================
     |  TABLE QUERY (FINAL – AMAN only_full_group_by)
     ===================================================== */
    public function table(Table $table): Table
    {
        $start    = $this->filters['start_date']
            ? Carbon::parse($this->filters['start_date'])->toDateString()
            : null;

        $end      = $this->filters['end_date']
            ? Carbon::parse($this->filters['end_date'])->toDateString()
            : null;

        $operator = $this->filters['operator'] ?: null;
        $service  = $this->filters['service'] ?: null;

        $query = MoHour::query()
            // JOIN DIMENSIONS (WAJIB)
            ->leftJoin('operators', 'mo_hours.id_operator', '=', 'operators.id')
            ->leftJoin('services', 'mo_hours.id_service', '=', 'services.id')

            // FILTER DATE
            ->when($start, fn (Builder $q) =>
                $q->whereDate('mo_hours.date', '>=', $start)
            )
            ->when($end, fn (Builder $q) =>
                $q->whereDate('mo_hours.date', '<=', $end)
            )

            // FILTER OPERATOR & SERVICE
            ->when($operator, fn (Builder $q) =>
                $q->where('operators.operator', $operator)
            )
            ->when($service, fn (Builder $q) =>
                $q->where('services.service', $service)
            )

            // ROLE RESTRICTION
            ->when(! $this->isSuperAdminUser(), function (Builder $q) {
                $allowedOperators = array_keys($this->getOperatorOptionsForUser());

                if (empty($allowedOperators)) {
                    $q->whereRaw('0 = 1');
                } else {
                    $q->whereIn('operators.operator', $allowedOperators);
                }
            })

            // GROUP BY = DIMENSIONS
            ->groupBy(
                'mo_hours.date',
                'operators.operator',
                'services.service'
            )

            ->select(array_merge([
                DB::raw('mo_hours.date AS date'),
                DB::raw('operators.operator AS operator_name'),
                DB::raw('services.service AS service_name'),

                DB::raw("
                    MD5(CONCAT(
                        mo_hours.date,
                        operators.operator,
                        services.service
                    )) AS id
                "),
            ], $this->getHourlyTransactionColumns()));

        return $table
            ->description('⏱️ Latest data is updated every 30 minutes')
            ->query($query)
            ->columns(array_merge([
                TextColumn::make('date')->label('Date')->date()->sortable(),
                TextColumn::make('operator_name')->label('Operator')->sortable(),
                TextColumn::make('service_name')->label('Service')->sortable(),
            ], $this->getHourlyTransactionTableColumns()))
            ->defaultSort('date', 'desc')
            ->deferLoading()
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50])
            ->headerActions([
                ExportAction::make()
                    ->label('Export')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withColumns(array_merge([
                                Column::make('date'),
                                Column::make('operator_name')->heading('Operator'),
                                Column::make('service_name')->heading('Service'),
                            ], $this->getHourlyExportColumns())),
                    ]),
            ]);
    }

    /* =====================================================
     |  HOURLY AGGREGATES
     ===================================================== */
    private function getHourlyTransactionColumns(): array
    {
        $cols = [];

        for ($i = 24; $i >= 0; $i--) {
            $cols[] = DB::raw("
                COALESCE(
                    SUM(CASE WHEN mo_hours.hour = {$i}
                        THEN mo_hours.total_reg ELSE 0 END),
                    0
                ) AS reg_{$i}
            ");

            $cols[] = DB::raw("
                COALESCE(
                    SUM(CASE WHEN mo_hours.hour = {$i}
                        THEN mo_hours.total_unreg ELSE 0 END),
                    0
                ) AS unreg_{$i}
            ");
        }

        return $cols;
    }

    private function getHourlyTransactionTableColumns(): array
    {
        $columns = [];

        for ($i = 24; $i >= 0; $i--) {
            $columns[] = TextColumn::make("reg_{$i}")
                ->label("Reg {$i}")
                ->numeric()
                ->sortable()
                ->alignRight();

            $columns[] = TextColumn::make("unreg_{$i}")
                ->label("Unreg {$i}")
                ->numeric()
                ->sortable()
                ->alignRight();
        }

        return $columns;
    }

    private function getHourlyExportColumns(): array
    {
        $columns = [];

        for ($i = 24; $i >= 0; $i--) {
            $columns[] = Column::make("reg_{$i}")->heading("Reg {$i}");
            $columns[] = Column::make("unreg_{$i}")->heading("Unreg {$i}");
        }

        return $columns;
    }
}
