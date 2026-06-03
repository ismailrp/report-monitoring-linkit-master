<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TelcoResource\Pages;
use App\Filament\Resources\TelcoResource\RelationManagers;
use App\Models\Telco;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TelcoResource extends Resource
{
    protected static ?string $model = Telco::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationLabel = 'Telco';
    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ]),

                Forms\Components\Section::make('Telco Status Codes')
                    ->description('Manage status codes and descriptions associated with this Telco.')
                    ->schema([
                        Forms\Components\Repeater::make('statusTelcos')
                            ->relationship('statusTelcos')
                            ->schema([
                                Forms\Components\TextInput::make('status_code')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'Not Found' => 'Not Found',
                                        'Success' => 'Success',
                                        'Permanent' => 'Permanent',
                                        'Business Temp' => 'Business Temp',
                                        'Technical Temp' => 'Technical Temp',
                                    ])
                                    ->required()
                                    ->placeholder('-- Pilih Type --'),
                                Forms\Components\Textarea::make('description')
                                    ->maxLength(1000)
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->createItemButtonLabel('Add Status Code')
                            ->defaultItems(0),
                    ])
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('operators_count')
                    ->counts('operators')
                    ->label('Assigned Operators')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('status_telcos_count')
                    ->counts('statusTelcos')
                    ->label('Status Codes Count')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListTelcos::route('/'),
            'create' => Pages\CreateTelco::route('/create'),
            'edit' => Pages\EditTelco::route('/{record}/edit'),
        ];
    }
}
