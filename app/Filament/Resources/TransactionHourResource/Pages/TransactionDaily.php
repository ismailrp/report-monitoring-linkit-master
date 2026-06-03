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
use Illuminate\Support\Arr;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Carbon\Carbon;

class TransactionDaily extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    protected static string $resource = TransactionHourResource::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static string $view = 'filament.resources.transaction-hour-resource.pages.transaction-daily';
    protected static ?string $navigationLabel = 'Transaction Daily';
    protected static ?string $title = 'Transaction Daily';
    protected static ?string $navigationGroup = "Transaction";

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
        foreach (['super_admin', 'super-admin', 'superadmin', 'super admin'] as $roleName) {
            if ($user->hasRole($roleName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Ambil daftar operator untuk dropdown berdasarkan user.
     * Mengembalikan array ['label' => 'label'].
     */
    protected function getOperatorOptionsForUser(): array
    {
        $user = auth()->user();
        $isSuperAdmin = $this->isSuperAdminUser();

        if ($isSuperAdmin) {
            if (Schema::hasTable('operators')) {
                $ops = DB::table('operators')->distinct()->orderBy('operator')->pluck('operator')->toArray();
            } elseif (Schema::hasTable('transaction_hours')) {
                $ops = TransactionHour::query()->distinct()->orderBy('operator')->pluck('operator')->toArray();
            } else {
                $ops = [];
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

        $ops = DB::table('user_has_operators')
            ->where('id_user', $user->id)
            ->distinct()
            ->orderBy('operator')
            ->pluck('operator')
            ->toArray();

        return array_combine($ops, $ops) ?: [];
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
                        ->options(fn (Get $get) =>
                            TransactionHour::query()
                                ->when($get('operator'), fn (Builder $query, $operator) => $query->where('operator', $operator))
                                ->distinct()
                                ->orderBy('service')
                                ->pluck('service', 'service')
                                ->toArray()
                        )
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
        // --- NORMALIZE FILTERS ---
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
        // ----------------------------

        $hasIdUser = Schema::hasColumn('transaction_hours', 'id_user');
        $hasUserId = Schema::hasColumn('transaction_hours', 'user_id');
        $hasMsisdn = Schema::hasColumn('transaction_hours', 'msisdn') || Schema::hasColumn('transaction_hours', 'phone');

        $isSuperAdmin = $this->isSuperAdminUser();

        // prepare operator options for non-super-admin default
        $operatorOptions = array_keys($this->getOperatorOptionsForUser());

        // base query
        $query = TransactionHour::query()
            ->leftJoin('operators', 'transaction_hours.id_operator', '=', 'operators.id')
            ->leftJoin('countries', 'operators.id_country', '=', 'countries.id')
            ->where('hour', 24)
            ->when($start, fn (Builder $q) => $q->whereDate('transaction_hours.date', '>=', $start))
            ->when($end, fn (Builder $q) => $q->whereDate('transaction_hours.date', '<=', $end))
            ->when($service, fn (Builder $q, $s) => $q->where('transaction_hours.service', $s));

        // operator filter logic
        if ($operator) {
            if ($isSuperAdmin) {
                // super-admin: simple filter
                $query->where('transaction_hours.operator', $operator);
            } else {
                // non-super-admin: attempt strict mapping if possible, else fallback to where('operator', ...)
                if ($hasIdUser || $hasUserId) {
                    $userCol = $hasIdUser ? 'id_user' : 'user_id';

                    $query->whereExists(function ($sub) use ($operator, $userCol) {
                        $sub->select(DB::raw(1))
                            ->from('user_has_operators')
                            ->whereColumn('user_has_operators.' . $userCol, DB::raw("transaction_hours.{$userCol}"))
                            ->whereExists(function ($sq) use ($operator) {
                                $sq->select(DB::raw(1))
                                   ->from('operators')
                                   ->whereColumn('operators.id', 'user_has_operators.id_operator')
                                   ->where('operators.operator', $operator);
                            });
                    })
                    ->whereRaw('(
                        SELECT COUNT(*) FROM user_has_operators uho_total
                        WHERE uho_total.' . $userCol . ' = transaction_hours.' . $userCol . '
                    ) = (
                        SELECT COUNT(*) FROM user_has_operators uho_match
                        JOIN operators o ON o.id = uho_match.id_operator
                        WHERE uho_match.' . $userCol . ' = transaction_hours.' . $userCol . '
                          AND o.operator = ?
                    )', [$operator]);
                } elseif ($hasMsisdn && (Schema::hasColumn('users', 'msisdn') || Schema::hasColumn('users', 'phone'))) {
                    $userPhoneCol = Schema::hasColumn('users', 'msisdn') ? 'msisdn' : 'phone';
                    $transPhoneCol = Schema::hasColumn('transaction_hours', 'msisdn') ? 'msisdn' : 'phone';

                    $query->whereExists(function ($sub) use ($operator, $userPhoneCol, $transPhoneCol) {
                        $sub->select(DB::raw(1))
                            ->from('users')
                            ->join('user_has_operators', 'users.id', '=', 'user_has_operators.id_user')
                            ->join('operators', 'user_has_operators.id_operator', '=', 'operators.id')
                            ->whereColumn("users.{$userPhoneCol}", "transaction_hours.{$transPhoneCol}")
                            ->where('operators.operator', $operator);
                    })
                    ->whereRaw('(
                        SELECT COUNT(*) FROM user_has_operators uho_total
                        JOIN users uu ON uu.id = uho_total.id_user
                        WHERE uu.' . $userPhoneCol . ' = transaction_hours.' . $transPhoneCol . '
                    ) = (
                        SELECT COUNT(*) FROM user_has_operators uho_match
                        JOIN users uu2 ON uu2.id = uho_match.id_user
                        JOIN operators o ON o.id = uho_match.id_operator
                        WHERE uu2.' . $userPhoneCol . ' = transaction_hours.' . $transPhoneCol . '
                          AND o.operator = ?
                    )', [$operator]);
                } else {
                    // fallback: simple operator filter
                    $query->where('operator', $operator);
                }
            }
        } else {
            // operator not selected
            if (! $isSuperAdmin) {
                if (! empty($operatorOptions)) {
                    $query->whereIn('transaction_hours.operator', $operatorOptions);
                } else {
                    // user has no operator -> empty
                    $query->whereRaw('0 = 1');
                }
            }
        }

        // ensure GROUP BY for ONLY_FULL_GROUP_BY
        $query->groupBy(['transaction_hours.date', 'transaction_hours.operator', 'transaction_hours.service', 'countries.convert_usd']);

        // select aggregated daily revenue
        $query->select([
            DB::raw("MD5(CONCAT(transaction_hours.date, transaction_hours.operator, transaction_hours.service)) AS id"),
            'transaction_hours.date',
            'transaction_hours.operator',
            'transaction_hours.service',
            DB::raw("ROUND(SUM(transaction_hours.revenue * COALESCE((CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END), 1)), 0) as revenue"),
        ]);

        return $table
            ->description('⏱️ Latest data is updated every 30 minutes')
            ->query($query)
            ->columns([
                TextColumn::make('date')->date()->sortable()->label('DATE'),
                TextColumn::make('operator')->sortable()->label('Operator'),
                TextColumn::make('service')->sortable()->label('Service'),
                TextColumn::make('revenue')
                    ->label('Total Revenue')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.')),
            ])
            ->defaultSort('date', 'desc')
            ->deferLoading()
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50]);
    }

    // helper columns (tetap)
    private function getDailyTransactionColumns(): array
    {
        return [
            DB::raw("revenue AS total_daily_revenue"),
        ];
    }

    private function getDailyTransactionTableColumns(): array
    {
        return [
            TextColumn::make("total_daily_revenue")
                ->label("Total Revenue")
                ->sortable()
                ->formatStateUsing(function ($state, $record) {
                    $rates = \Illuminate\Support\Facades\Cache::remember('operator_rates_usd', 300, function () {
                        return \Illuminate\Support\Facades\DB::table('operators')
                            ->join('countries', 'operators.id_country', '=', 'countries.id')
                            ->pluck('countries.convert_usd', 'operators.operator')
                            ->toArray();
                    });
                    $rate = round(floatval($rates[$record->operator] ?? 1));
                    return number_format((float)$state * $rate, 0, ',', '.');
                }),
        ];
    }
}
