<?php

namespace App\Filament\Resources;

// use App\Filament\Resources\SubActiveUserHourResource\Pages;
use App\Filament\Resources\SubActiveUserHourResource\Pages;
use App\Models\SubActiveUserHour;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubActiveUserHourResource extends Resource
{
    protected static ?string $model = SubActiveUserHour::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required(),
                Forms\Components\TextInput::make('hour')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_operator')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_service')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('total_sub')
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
                Tables\Columns\TextColumn::make('id_operator')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_service')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_sub')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

    // public static function getPages(): array
    // {
    //     return [
    //         'index' => Pages\ListSubActiveUserHours::route('/'),
    //         'create' => Pages\CreateSubActiveUserHour::route('/create'),
    //         'edit' => Pages\EditSubActiveUserHour::route('/{record}/edit'),
    //     ];
    // }

     public static function getPages(): array
    {
        return [
            // pastikan ini mengarah ke class SubActiveList yang kamu buat
            'index' => Pages\SubActiveList::route('/'),
            // kalau mau tambahkan create/edit:
            // 'create' => Pages\CreateSubActiveUserHour::route('/create'),
            // 'edit' => Pages\EditSubActiveUserHour::route('/{record}/edit'),
        ];
    }
}
