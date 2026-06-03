<?php

namespace App\Filament\Resources\SubActiveUserHourResource\Pages;

use App\Filament\Resources\SubActiveUserHourResource;
use App\Models\SubActiveUserHour;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Support\Facades\DB;

class SubActiveList extends Page implements Tables\Contracts\HasTable, Forms\Contracts\HasForms
{
    use Tables\Concerns\InteractsWithTable;
    use Forms\Concerns\InteractsWithForms;

    protected static string $resource = SubActiveUserHourResource::class;
    protected static string $view = 'filament.resources.sub-active-user-hour-resource.pages.sub-active-list';

    // filter states
    public ?string $viewMode = 'daily';
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?int $id_operator = null;
    public ?int $id_service = null;

    // Form filter
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(5) // 🔹 tampilkan semua filter dalam 1 baris
                ->schema([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Start')
                        ->default(now()->startOfMonth())
                        ->reactive(),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('End')
                        ->default(now())
                        ->reactive(),

                    Forms\Components\TextInput::make('id_operator')
                        ->label('Operator')
                        ->numeric()
                        ->placeholder('Operator ID')
                        ->reactive(),

                    Forms\Components\TextInput::make('id_service')
                        ->label('Service')
                        ->numeric()
                        ->placeholder('Service ID')
                        ->reactive(),

                    Forms\Components\Select::make('viewMode')
                        ->label('Mode')
                        ->options([
                            'daily' => 'Daily',
                            // 'hourly' => 'Hourly',
                        ])
                        ->default('daily')
                        ->reactive(),
                ])
                ->columns(5)
                ->columnSpanFull(),
        ];
    }

    // Table query
    protected function getTableQuery()
    {
        $query = SubActiveUserHour::query()
            ->when($this->start_date, fn($q) => $q->whereDate('date', '>=', $this->start_date))
            ->when($this->end_date, fn($q) => $q->whereDate('date', '<=', $this->end_date))
            ->when($this->id_operator, fn($q) => $q->where('id_operator', $this->id_operator))
            ->when($this->id_service, fn($q) => $q->where('id_service', $this->id_service));

        if ($this->viewMode === 'hourly') {
            $query->select(
                'date',
                'hour',
                'id_operator',
                'id_service',
                DB::raw('SUM(total_sub) as total_sub')
            )
            ->groupBy('date', 'hour', 'id_operator', 'id_service')
            ->orderBy('date', 'desc')
            ->orderBy('hour', 'asc');
        } else {
            $query->select(
                'date',
                'id_operator',
                'id_service',
                DB::raw('SUM(total_sub) as total_sub')
            )
            ->groupBy('date', 'id_operator', 'id_service')
            ->orderBy('date', 'desc');
        }

        return $query;
    }

    // Table columns
    protected function getTableColumns(): array
    {
        $columns = [
            Tables\Columns\TextColumn::make('date')->label('Date')->sortable(),
            Tables\Columns\TextColumn::make('id_operator')->label('Operator'),
            Tables\Columns\TextColumn::make('id_service')->label('Service'),
            Tables\Columns\TextColumn::make('total_sub')->label('Total Sub'),
        ];

        if ($this->viewMode === 'hourly') {
            array_splice($columns, 1, 0, [
                Tables\Columns\TextColumn::make('hour')->label('Hour'),
            ]);
        }

        return $columns;
    }
}
