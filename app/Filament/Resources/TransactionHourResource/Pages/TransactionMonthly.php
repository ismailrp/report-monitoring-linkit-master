<?php

namespace App\Filament\Resources\TransactionHourResource\Pages;

use App\Filament\Resources\TransactionHourResource;
use App\Models\TransactionHour;
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
use Filament\Forms\Get;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Carbon\Carbon;

class TransactionMonthly extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    protected static string $resource = TransactionHourResource::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static string $view = 'filament.resources.transaction-hour-resource.pages.transaction-monthly';
    protected static ?string $navigationLabel = 'Transaction Monthly';
    protected static ?string $title = 'Transaction Monthly';
    protected static ?string $navigationGroup = 'Transaction';

    public array $filters = [
        'start_date' => null,
        'end_date'   => null,
        'operator'   => null,
        'service'    => null,
    ];

    protected $listeners = ['refreshTable' => '$refresh'];

    public function mount(): void
    {
        $this->filters['start_date'] = Carbon::today()->startOfMonth()->toDateString();
        $this->form->fill($this->filters);
    }

    /* ================= ROLE ================= */

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

    /* ================= OPERATOR OPTIONS ================= */

    protected function getOperatorOptionsForUser(): array
    {
        $user = auth()->user();
        $isSuper = $this->isSuperAdminUser();

        if ($isSuper) {
            $ops = TransactionHour::query()
                ->distinct()
                ->orderBy('operator')
                ->pluck('operator')
                ->toArray();

            return array_combine($ops, $ops) ?: [];
        }

        if (! $user || ! Schema::hasTable('user_has_operators')) {
            return [];
        }

        if (Schema::hasTable('operators')) {
            $ops = DB::table('user_has_operators')
                ->join('operators', 'operators.id', '=', 'user_has_operators.id_operator')
                ->where('user_has_operators.id_user', $user->id)
                ->distinct()
                ->orderBy('operators.operator')
                ->pluck('operators.operator')
                ->toArray();

            return array_combine($ops, $ops) ?: [];
        }

        return [];
    }

    /* ================= FORM ================= */

    public function getFormSchema(): array
    {
        return [
            Grid::make()
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 4])
                ->schema([
                    DatePicker::make('start_date')->label('Start Date'),
                    DatePicker::make('end_date')->label('End Date'),

                    Select::make('operator')
                        ->label('Operator')
                        ->options($this->getOperatorOptionsForUser())
                        ->searchable()
                        ->preload(),

                    Select::make('service')
                        ->label('Service')
                        ->options(fn (Get $get) =>
                            TransactionHour::query()
                                ->when($get('operator'), fn ($q, $op) => $q->where('operator', $op))
                                ->distinct()
                                ->orderBy('service')
                                ->pluck('service', 'service')
                                ->toArray()
                        )
                        ->searchable()
                        ->preload(),

                    Actions::make([
                        Action::make('search')
                            ->label('Search')
                            ->button()
                            ->color('primary')
                            ->action(fn () => $this->dispatch('refreshTable')),
                    ])->columnSpan('full'),
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

    /* ================= TABLE ================= */

    public function table(Table $table): Table
    {
        $start    = $this->filters['start_date'];
        $end      = $this->filters['end_date'];
        $operator = $this->filters['operator'];
        $service  = $this->filters['service'];

        $isSuper = $this->isSuperAdminUser();
        $allowedOperators = array_keys($this->getOperatorOptionsForUser());

        /**
         * =====================================
         * INNER QUERY
         * - daily (hour = 24)
         * - compute month_key HERE
         * =====================================
         */
        $daily = TransactionHour::query()
            ->leftJoin('operators', 'transaction_hours.id_operator', '=', 'operators.id')
            ->leftJoin('countries', 'operators.id_country', '=', 'countries.id')
            ->where('hour', 24)
            ->when($start, fn ($q) =>
                $q->whereDate('transaction_hours.date', '>=', Carbon::parse($start)->toDateString())
            )
            ->when($end, fn ($q) =>
                $q->whereDate('transaction_hours.date', '<=', Carbon::parse($end)->toDateString())
            )
            ->when($service, fn ($q) => $q->where('transaction_hours.service', $service))
            ->when(
                $operator,
                fn ($q) => $q->where('transaction_hours.operator', $operator),
                fn ($q) => $isSuper
                    ? $q
                    : $q->whereIn('transaction_hours.operator', $allowedOperators ?: ['__none__'])
            )
            ->selectRaw("
                DATE_FORMAT(transaction_hours.date, '%Y-%m') AS month_key,
                transaction_hours.operator,
                transaction_hours.service,
                ROUND(SUM(transaction_hours.revenue * COALESCE((CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 1)), 0) AS daily_revenue
            ")
            ->groupBy('month_key', 'transaction_hours.operator', 'transaction_hours.service', 'countries.convert_usd');

        /**
         * =====================================
         * OUTER QUERY (MONTHLY)
         * - NO date
         * - NO YEAR()/MONTH()
         * =====================================
         */
        $monthly = TransactionHour::query()
            ->fromSub($daily->getQuery(), 'm')
            ->groupBy('m.month_key', 'm.operator', 'm.service')
            ->selectRaw("
                MD5(CONCAT(m.month_key, m.operator, m.service)) AS id,
                m.month_key AS month,
                m.operator,
                m.service,
                SUM(m.daily_revenue) AS total_revenue
            ");

        return $table
            ->description('⏱️ Latest data is updated every 30 minutes')
            ->query($monthly)
            ->columns([
                TextColumn::make('month')->label('Month')->sortable(),
                TextColumn::make('operator')->sortable(),
                TextColumn::make('service')->sortable(),
                TextColumn::make('total_revenue')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.')),
            ])
            ->defaultSort('month', 'desc')
            ->deferLoading()
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50]);
    }
}
