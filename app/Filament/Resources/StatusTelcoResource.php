<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StatusTelcoResource\Pages;
use App\Filament\Resources\StatusTelcoResource\RelationManagers;
use App\Models\StatusTelco;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StatusTelcoResource extends Resource
{
    protected static ?string $model = StatusTelco::class;

    protected static ?string $navigationIcon = 'heroicon-o-hashtag';
    protected static ?string $navigationLabel = 'Status Telco';
    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('id_telco')
                            ->relationship('telco', 'name')
                            ->label('Telco')
                            ->required()
                            ->searchable()
                            ->preload(),
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
                            ->rows(3),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('telco.name')
                    ->label('Telco')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status_code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Success' => 'success',
                        'Not Found' => 'danger',
                        'Permanent' => 'warning',
                        'Business Temp' => 'info',
                        'Technical Temp' => 'primary',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('id_telco')
                    ->relationship('telco', 'name')
                    ->label('Filter by Telco')
                    ->searchable()
                    ->preload(),
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
            'index' => Pages\ListStatusTelcos::route('/'),
            'create' => Pages\CreateStatusTelco::route('/create'),
            'edit' => Pages\EditStatusTelco::route('/{record}/edit'),
        ];
    }
}
