<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SummaryWeeklyResource\Pages;
use App\Models\SummaryWeekly;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class SummaryWeeklyResource extends Resource
{
    protected static ?string $model = SummaryWeekly::class;
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Weekly Reports';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // form schema jika diperlukan
            ]);
    }

    /**
     * Override Eloquent query so non-super-admin only sees their operators.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();
        if (! $user) {
            return $query->whereRaw('0 = 1');
        }

        if (self::isSuperAdminUser()) {
            return $query;
        }

        // Non-super admin: restrict to pivot user_has_operators
        if (! Schema::hasTable('user_has_operators')) {
            return $query->whereRaw('0 = 1');
        }

        // prefer id_operator if available
        if (Schema::hasTable('operators')) {
            $operatorIds = DB::table('user_has_operators')
                ->where('id_user', $user->id)
                ->pluck('id_operator')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (! empty($operatorIds) && Schema::hasColumn('summary_weekly', 'id_operator')) {
                return $query->whereIn('summary_weekly.id_operator', $operatorIds);
            }

            // fallback: try names in pivot
            $operatorNames = DB::table('user_has_operators')
                ->where('id_user', $user->id)
                ->pluck('operator')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (! empty($operatorNames) && Schema::hasColumn('summary_weekly', 'operator')) {
                return $query->whereIn('summary_weekly.operator', $operatorNames);
            }

            // fallback: try resolve names from operatorIds
            if (! empty($operatorIds) && Schema::hasTable('operators')) {
                $names = DB::table('operators')->whereIn('id', $operatorIds)->pluck('operator')->toArray();
                if (! empty($names) && Schema::hasColumn('summary_weekly', 'operator')) {
                    return $query->whereIn('summary_weekly.operator', $names);
                }
            }

            return $query->whereRaw('0 = 1');
        }

        // if no operators table, pivot likely stores names
        $operatorNames = DB::table('user_has_operators')
            ->where('id_user', $user->id)
            ->pluck('operator')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (! empty($operatorNames) && Schema::hasColumn('summary_weekly', 'operator')) {
            return $query->whereIn('summary_weekly.operator', $operatorNames);
        }

        return $query->whereRaw('0 = 1');
    }

    protected static function isSuperAdminUser(): bool
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'hasRole')) {
            return false;
        }

        // tambahkan role lain jika aplikasi Anda memakai nama role berbeda
        foreach (['super_admin', 'super-admin', 'superadmin', 'super admin', 'admin'] as $roleName) {
            if ($user->hasRole($roleName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return operator options limited to what current user can access.
     *
     * Kembalikan array sederhana key => label (key bisa id atau name tergantung schema).
     */
    protected static function getAccessibleOperatorOptions(): array
    {
        $user = auth()->user();
        $isSuper = self::isSuperAdminUser();

        // jika ada table operators, prefer id => operator label untuk super
        if (Schema::hasTable('operators')) {
            if ($isSuper) {
                // id => operator
                return DB::table('operators')->distinct()->orderBy('operator')->pluck('operator', 'id')->toArray();
            }

            // non-super: ambil dari pivot
            if (Schema::hasTable('user_has_operators')) {
                $ids = DB::table('user_has_operators')->where('id_user', $user->id)->pluck('id_operator')->filter()->unique()->values()->toArray();
                if (! empty($ids)) {
                    return DB::table('operators')->whereIn('id', $ids)->orderBy('operator')->pluck('operator', 'id')->toArray();
                }

                // pivot mungkin menyimpan nama operator
                $names = DB::table('user_has_operators')->where('id_user', $user->id)->pluck('operator')->filter()->unique()->values()->toArray();
                if (! empty($names)) {
                    // coba cocokkan ke tabel operators dulu
                    $rows = DB::table('operators')->whereIn('operator', $names)->orderBy('operator')->pluck('operator', 'id')->toArray();
                    if (! empty($rows)) {
                        return $rows;
                    }
                    // fallback ke name => name
                    return array_combine($names, $names) ?: [];
                }

                return [];
            }

            return [];
        }

        // jika tidak ada tabel operators, pakai nama dari summary_weekly (super lihat semua)
        if ($isSuper) {
            $ops = SummaryWeekly::query()->distinct()->orderBy('operator')->pluck('operator')->toArray();
            return array_combine($ops, $ops) ?: [];
        }

        if (Schema::hasTable('user_has_operators')) {
            $names = DB::table('user_has_operators')->where('id_user', $user->id)->pluck('operator')->filter()->unique()->values()->toArray();
            return array_combine($names, $names) ?: [];
        }

        return [];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('⏱️ Latest data is updated every 30 minutes')
            ->columns([
                Tables\Columns\TextColumn::make('year')
                    ->label('Year')
                    ->sortable(),

                Tables\Columns\TextColumn::make('operator.operator')
                    ->label('Operator')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => $state ?? optional($record->operator)->operator),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('End Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_mt')
                    ->label('Total MT')
                    ->numeric()
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('total_mo')
                    ->label('Total MO')
                    ->numeric()
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('operator.country.currency')
                    ->label('Currency')
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state, $record) => $state ?? optional(optional($record->operator)->country)->currency ?? 'IDR'),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Total Revenue')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('revenue_usd')
                    ->label('Revenue USD')
                    ->getStateUsing(function ($record) {
                        $rate = $record->country?->convert_usd ?? 0;
                        if (strtoupper($record->country?->country ?? '') === 'OMAN') {
                            $rate = $rate / 1000;
                        }
                        return round($record->total_revenue * $rate, 0);
                    })
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignRight()
                    ->sortable(),
            ])
            ->filters([
                // Year select filter (safe closure — no params)
                Tables\Filters\SelectFilter::make('year')
                    ->label('Year')
                    ->options(fn () => SummaryWeekly::query()
                        ->distinct()
                        ->orderBy('year')
                        ->pluck('year', 'year')
                        ->toArray()
                    )
                    ->searchable(),

                // Operator select filter (options closure must NOT expect $value)
                Tables\Filters\SelectFilter::make('operator')
                    ->label('Operator')
                    ->options(fn () => self::getAccessibleOperatorOptions()) // <- no $value here
                    ->searchable()
                    ->query(function (Builder $query, $value) {
                        if ($value === null || $value === '') {
                            return;
                        }

                        // jika opsi key numeric dan ada id_operator di tabel
                        if (is_numeric($value) && Schema::hasColumn('summary_weekly', 'id_operator')) {
                            $query->where('summary_weekly.id_operator', $value);
                            return;
                        }

                        // fallback: cocokkan berdasarkan nama operator
                        $query->where('summary_weekly.operator', $value);
                    }),

                Tables\Filters\SelectFilter::make('period')
                    ->label('Period (Start - End)')
                    ->options(fn () => SummaryWeekly::query()
                        ->select('start_date', 'end_date')
                        ->distinct()
                        ->orderByDesc('start_date')
                        ->get()
                        ->mapWithKeys(function ($item) {
                            $start = $item->start_date ? \Carbon\Carbon::parse($item->start_date)->format('Y-m-d') : '';
                            $end = $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('Y-m-d') : '';
                            $key = $start . '|' . $end;
                            $label = $start . ' to ' . $end;
                            return [$key => $label];
                        })
                        ->toArray()
                    )
                    ->query(function (Builder $query, $value) {
                        if ($value === null || $value === '') {
                            return;
                        }

                        $parts = explode('|', $value);
                        if (count($parts) === 2) {
                            $start = $parts[0];
                            $end = $parts[1];
                            if ($start) {
                                $query->whereDate('summary_weekly.start_date', '=', $start);
                            }
                            if ($end) {
                                $query->whereDate('summary_weekly.end_date', '=', $end);
                            }
                        }
                    }),
            ])

            ->headerActions([
                ExportAction::make('export')
                    ->label('Export Data')
                    ->color('success')
                    ->exports([
                        ExcelExport::make('summary_weekly_export')
                            ->fromTable()
                            ->withColumns([
                                Column::make('year'),
                                Column::make('operator.operator')->heading('Operator'),
                                Column::make('start_date'),
                                Column::make('end_date'),
                                Column::make('total_mt')->heading('Total MT'),
                                Column::make('total_mo')->heading('Total MO'),
                                Column::make('total_revenue')->heading('Total Revenue'),
                                Column::make('revenue_usd')->heading('Revenue USD'),
                            ]),
                    ]),
            ])
            ->actions([
                // Tables\Actions\Action::make('view')
                //     ->label('View')
                //     ->icon('heroicon-o-eye')
                //     ->url(fn (SummaryWeekly $record) => null), // sesuaikan jika punya halaman view
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                     Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [
            // Relation managers jika ada
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSummaryWeeklies::route('/'),
        ];
    }
}
