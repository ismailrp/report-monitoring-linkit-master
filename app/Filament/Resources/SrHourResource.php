<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SrHourResource\Pages;
use App\Filament\Resources\SrHourResource\RelationManagers;
use App\Models\SrHour;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SrHourResource extends Resource
{
    protected static ?string $model = SrHour::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Daily Reports';
    protected static bool $shouldRegisterNavigation = false;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required(),
                Forms\Components\TextInput::make('hour')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('country')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('id_operator')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('operator')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('id_service')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('service')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('total_click')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('total_mo')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('sr')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('⏱️ Latest data is updated every 30 minutes')
            ->defaultSort('date', 'desc')
            ->deferLoading()
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hour')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('country')
                    ->searchable(),
                Tables\Columns\TextColumn::make('id_operator')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('operator')
                    ->searchable(),
                Tables\Columns\TextColumn::make('id_service')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_click')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_mo')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sr')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSrHours::route('/'),
            'create' => Pages\CreateSrHour::route('/create'),
            'edit' => Pages\EditSrHour::route('/{record}/edit'),
        ];
    }
}
