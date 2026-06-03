<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlertResource\Pages;
use App\Models\Alert;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;

class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Alerts';
    protected static ?string $navigationGroup = 'Monitoring';

    /**
     * Form is empty because this resource is used mainly for listing + filters.
     */
    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    /**
     * Base Eloquent query for this resource.
     * Apply operator-per-user restriction if user is not super-admin.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = Alert::query();
        $user = Auth::user();

        if (! self::isSuperAdminUser($user)) {
            $allowed = self::getAllowedOperatorIdentifiersForUser($user);

            if (empty($allowed)) {
                // no operators allowed -> no rows
                $query->whereRaw('0 = 1');
                return $query;
            }

            // If pivot stores operator ids, and alerts table has id_operator => filter by id_operator
            if (self::pivotStoresOperatorIds()) {
                if (Schema::hasColumn('alerts', 'id_operator')) {
                    $query->whereIn('id_operator', $allowed);
                    return $query;
                }

                // if alerts doesn't have id_operator, try join operators table
                if (Schema::hasTable('operators')) {
                    $query->join('operators', 'alerts.id_operator', '=', 'operators.id')
                          ->whereIn('operators.id', $allowed)
                          ->select('alerts.*');

                    return $query;
                }
            } else {
                // pivot stores operator names (strings)
                // best-effort: if alerts has 'operator' column, filter by that
                if (Schema::hasColumn('alerts', 'operator')) {
                    $query->whereIn('operator', $allowed);
                    return $query;
                }

                // otherwise join operators table and match by name if possible
                if (Schema::hasTable('operators')) {
                    $query->join('operators', 'alerts.id_operator', '=', 'operators.id')
                          ->whereIn('operators.operator', $allowed)
                          ->select('alerts.*');

                    return $query;
                }

                // fallback: no reliable way -> return no rows
                $query->whereRaw('0 = 1');
                return $query;
            }
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('⏱️ Latest data is updated every 30 minutes')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('hour')
                    ->label('Hour')
                    ->numeric()
                    ->sortable(),

                // Safe rel/label columns (works even if relation method missing)
                Tables\Columns\TextColumn::make('country_label')
                    ->label('Country')
                    ->getStateUsing(fn ($record) => $record->country?->country ?? ($record->id_country ?? '-'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('operator_label')
                    ->label('Operator')
                    ->getStateUsing(fn ($record) => $record->operator?->operator ?? ($record->id_operator ?? ($record->operator ?? '-')))
                    ->sortable(),

                Tables\Columns\TextColumn::make('service_label')
                    ->label('Service')
                    ->getStateUsing(fn ($record) => $record->service?->service ?? ($record->id_service ?? '-'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('today')
                    ->label('Today')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('yesterday')
                    ->label('Yesterday')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('drop')
                    ->label('Drop')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                // Date range filter
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $from) => $q->whereDate('date', '>=', $from))
                            ->when($data['until'] ?? null, fn (Builder $q, $until) => $q->whereDate('date', '<=', $until));
                    }),

                // Operator filter (options restricted to user's operators)
                Filter::make('operator')
                    ->form([
                        Select::make('operator')->label('Operator')
                            ->options(fn () => self::getOperatorOptionsForCurrentUser())
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['operator'])) {
                            return $query;
                        }

                        $selected = $data['operator'];

                        // if selected is numeric and alerts has id_operator => filter by id
                        if (is_numeric($selected) && Schema::hasColumn('alerts', 'id_operator')) {
                            return $query->where('id_operator', (int) $selected);
                        }

                        // if alerts has 'operator' column (name)
                        if (Schema::hasColumn('alerts', 'operator')) {
                            return $query->where('operator', $selected);
                        }

                        // try join operators table and filter by operator name
                        if (Schema::hasTable('operators')) {
                            return $query->join('operators', 'alerts.id_operator', '=', 'operators.id')
                                         ->where('operators.operator', $selected)
                                         ->select('alerts.*');
                        }

                        return $query;
                    }),

                // Service filter (options depend on selected operator)
                Filter::make('service')
                    ->form([
                        Select::make('service')->label('Service')
                            ->options(function ($get) {
                                $operator = $get('operator') ?? null;
                                return self::getServiceOptionsFilteredByOperator($operator);
                            })
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['service'])) {
                            return $query;
                        }

                        $svc = $data['service'];

                        if (is_numeric($svc) && Schema::hasColumn('alerts', 'id_service')) {
                            return $query->where('id_service', (int) $svc);
                        }

                        if (Schema::hasColumn('alerts', 'service')) {
                            return $query->where('service', $svc);
                        }

                        if (Schema::hasTable('services')) {
                            return $query->join('services', 'alerts.id_service', '=', 'services.id')
                                         ->where(function ($q) use ($svc) {
                                             if (Schema::hasColumn('services', 'service')) {
                                                 $q->where('services.service', $svc);
                                             } else {
                                                 $q->where('services.name', $svc);
                                             }
                                         })
                                         ->select('alerts.*');
                        }

                        return $query;
                    }),

                // Type quick filter
                Filter::make('type')
                    ->form([
                        TextInput::make('type')->label('Type'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['type'])) {
                            return $query;
                        }
                        return $query->where('type', 'like', '%' . $data['type'] . '%');
                    }),
            ])
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Status')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => "Status Breakdown (" . ($record->date?->format('Y-m-d') ?? $record->date) . " - Hour {$record->hour})")
                    ->modalSubmitAction(false)
                    ->modalContent(function ($record) {
                        // Ensure relations loaded
                        $record->load(['service', 'country', 'operator']);

                        $allStatuses = \App\Models\SummaryStatus::query()
                            ->where('id_operator', $record->id_operator)
                            ->whereDate('date', $record->date)
                            ->where('hour', '<=', $record->hour)
                            ->select([
                                'id_service',
                                'status',
                                \Illuminate\Support\Facades\DB::raw('SUM(total) as total_status'),
                            ])
                            ->groupBy('id_service', 'status')
                            ->orderBy('status')
                            ->get();

                        $statuses = $allStatuses
                            ->groupBy('status')
                            ->map(function ($group) {
                                return (object) [
                                    'status'       => $group->first()->status,
                                    'total_status' => $group->sum('total_status'),
                                ];
                            })
                            ->sortByDesc('total_status')
                            ->values();

                        $serviceStatuses = $allStatuses->groupBy('id_service');

                        // Wrap record as services collection; ensure 'type' is present
                        if (! isset($record->type)) {
                            $record->type = 'Revenue';
                        }
                        $services = collect([$record]);

                        return view('filament.pages.alert-services-modal', [
                            'services'        => $services,
                            'statuses'        => $statuses,
                            'serviceStatuses' => $serviceStatuses,
                            'operatorName'    => optional($record->operator)->operator ?? '-',
                            'date'            => $record->date,
                            'hour'            => $record->hour,
                        ]);
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlerts::route('/'),
        ];
    }

    /* --------------------------
     | Helper / Utility Methods
     * ------------------------- */

    protected static function isSuperAdminUser($user = null): bool
    {
        $user = $user ?? Auth::user();
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
     * Cek apakah pivot user_has_operators menyimpan id_operator numeric.
     */
    protected static function pivotStoresOperatorIds(): bool
    {
        if (! Schema::hasTable('user_has_operators')) {
            return false;
        }

        if (Schema::hasColumn('user_has_operators', 'id_operator') || Schema::hasColumn('user_has_operators', 'operator_id')) {
            return true;
        }

        return false;
    }

    /**
     * Ambil identifier operator yang dimiliki user.
     * Mengembalikan array numeric ids jika pivot menyimpan id_operator,
     * atau array nama operator (string) jika pivot menyimpan operator (nama).
     *
     * NOTE: Perbaikan MySQL DISTINCT + ORDER BY — include operator col in select before pluck.
     */
    protected static function getAllowedOperatorIdentifiersForUser($user = null): array
    {
        $user = $user ?? Auth::user();
        if (! $user) {
            return [];
        }

        if (! Schema::hasTable('user_has_operators')) {
            return [];
        }

        if (Schema::hasTable('operators')) {
            // pivot stores id_operator
            if (Schema::hasColumn('user_has_operators', 'id_operator')) {
                $rows = DB::table('user_has_operators')
                    ->where('user_has_operators.id_user', $user->id)
                    ->join('operators', 'user_has_operators.id_operator', '=', 'operators.id')
                    ->select('operators.id', 'operators.operator') // include operator for ORDER BY compatibility
                    ->distinct()
                    ->orderBy('operators.operator')
                    ->get();

                // pluck ids reliably
                return $rows->pluck('id')->unique()->values()->all();
            }

            // pivot stores operator name
            if (Schema::hasColumn('user_has_operators', 'operator')) {
                return DB::table('user_has_operators')
                    ->where('user_has_operators.id_user', $user->id)
                    ->distinct()
                    ->orderBy('user_has_operators.operator')
                    ->pluck('user_has_operators.operator')
                    ->toArray();
            }
        } else {
            // no operators table: pivot might store operator names directly
            if (Schema::hasColumn('user_has_operators', 'operator')) {
                return DB::table('user_has_operators')
                    ->where('user_has_operators.id_user', $user->id)
                    ->distinct()
                    ->orderBy('user_has_operators.operator')
                    ->pluck('user_has_operators.operator')
                    ->toArray();
            }

            if (Schema::hasColumn('user_has_operators', 'id_operator')) {
                return DB::table('user_has_operators')
                    ->where('user_has_operators.id_user', $user->id)
                    ->distinct()
                    ->orderBy('user_has_operators.id_operator')
                    ->pluck('user_has_operators.id_operator')
                    ->toArray();
            }
        }

        return [];
    }

    /**
     * Opsi operator untuk filter/select — terbatas pada operator user (kecuali super).
     * value => label. (Value bisa id atau name tergantung skema).
     */
    protected static function getOperatorOptionsForCurrentUser(): array
    {
        $user = Auth::user();
        if (self::isSuperAdminUser($user)) {
            // all operators
            if (Schema::hasTable('operators')) {
                return DB::table('operators')
                    ->select('id', 'operator') // include operator
                    ->orderBy('operator')
                    ->pluck('operator', 'id')
                    ->toArray();
            }

            // fallback: distinct operator in alerts
            if (Schema::hasColumn('alerts', 'operator')) {
                $ops = DB::table('alerts')->distinct()->orderBy('operator')->pluck('operator')->toArray();
                return array_combine($ops, $ops) ?: [];
            }
            if (Schema::hasColumn('alerts', 'id_operator')) {
                $ids = DB::table('alerts')->distinct()->orderBy('id_operator')->pluck('id_operator')->toArray();
                return array_combine($ids, $ids) ?: [];
            }

            return [];
        }

        $allowed = self::getAllowedOperatorIdentifiersForUser($user);
        if (empty($allowed)) {
            return [];
        }

        if (Schema::hasTable('operators') && self::pivotStoresOperatorIds()) {
            // allowed are ids
            return DB::table('operators')
                ->whereIn('id', $allowed)
                ->select('id', 'operator') // include operator for ORDER BY compatibility
                ->orderBy('operator')
                ->pluck('operator', 'id')
                ->toArray();
        }

        // allowed are names (strings)
        if (is_array($allowed)) {
            if (Schema::hasTable('operators')) {
                // match by operator name, return name=>name pairs
                $rows = DB::table('operators')->whereIn('operator', $allowed)->orderBy('operator')->pluck('operator')->toArray();
                return array_combine($rows, $rows) ?: [];
            }
            // fallback name=>name
            $pairs = [];
            foreach ($allowed as $name) {
                $pairs[$name] = $name;
            }
            return $pairs;
        }

        return [];
    }

    /**
     * Ambil service options dari table services, di-filter berdasarkan operator (boleh null).
     * Mengembalikan array value => label.
     */
    protected static function getServiceOptionsFilteredByOperator($operator = null): array
    {
        if (! Schema::hasTable('services')) {
            return [];
        }

        $svcCol = Schema::hasColumn('services', 'service') ? 'service' : (Schema::hasColumn('services', 'name') ? 'name' : 'id');

        if (empty($operator)) {
            $services = DB::table('services')->orderBy($svcCol)->pluck($svcCol)->toArray();
            return array_combine($services, $services) ?: [];
        }

        // if operator is numeric and services.id_operator exists
        if (is_numeric($operator) && Schema::hasColumn('services', 'id_operator')) {
            $services = DB::table('services')->where('id_operator', (int) $operator)->orderBy($svcCol)->pluck($svcCol)->toArray();
            return array_combine($services, $services) ?: [];
        }

        // if services.operator column exists (stores operator name)
        if (Schema::hasColumn('services', 'operator')) {
            $services = DB::table('services')->where('operator', $operator)->orderBy($svcCol)->pluck($svcCol)->toArray();
            return array_combine($services, $services) ?: [];
        }

        // try pivot between operators & services
        foreach (['operator_services','operators_services','operator_service'] as $p) {
            if (Schema::hasTable($p)) {
                $pivot = $p;
                break;
            }
        }

        if (! empty($pivot ?? null)) {
            $opCol = Schema::hasColumn($pivot, 'operator_id') ? 'operator_id' : (Schema::hasColumn($pivot, 'id_operator') ? 'id_operator' : null);
            $svcColPivot = Schema::hasColumn($pivot, 'service_id') ? 'service_id' : (Schema::hasColumn($pivot, 'id_service') ? 'id_service' : null);

            if ($opCol && $svcColPivot) {
                if (is_numeric($operator)) {
                    $services = DB::table('services')
                        ->join($pivot, "services.id", '=', "{$pivot}.{$svcColPivot}")
                        ->where("{$pivot}.{$opCol}", (int) $operator)
                        ->orderBy("services.{$svcCol}")
                        ->pluck("services.{$svcCol}")
                        ->toArray();

                    return array_combine($services, $services) ?: [];
                } elseif (Schema::hasTable('operators')) {
                    $opRow = DB::table('operators')->where('operator', $operator)->first();
                    if ($opRow) {
                        $services = DB::table('services')
                            ->join($pivot, "services.id", '=', "{$pivot}.{$svcColPivot}")
                            ->where("{$pivot}.{$opCol}", $opRow->id)
                            ->orderBy("services.{$svcCol}")
                            ->pluck("services.{$svcCol}")
                            ->toArray();

                        return array_combine($services, $services) ?: [];
                    }
                }
            }
        }

        // final fallback: all services
        $services = DB::table('services')->orderBy($svcCol)->pluck($svcCol)->toArray();
        return array_combine($services, $services) ?: [];
    }
}
