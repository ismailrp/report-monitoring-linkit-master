<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Merchant;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;


class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id_operator')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_country')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('service')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('sdc')
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name_wakiad')
                    ->maxLength(255)
                    ->nullable(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active Status')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('⏱️ Latest data is updated every 30 minutes')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Id Service By Fery')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('operator.operator')
                    ->searchable(),
                Tables\Columns\TextColumn::make('service')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sdc')
                    // ->numeric('0')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    // ->money('')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '1' => 'Internal Fery',
                        '2' => 'External',
                        default => 'Unknown',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->default('-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_wakiad')
                    ->label('Name Wakiad')
                    ->default('-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('editService')
                    ->label('Edit Service')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Edit Service Details')
                    ->modalWidth('md')
                    ->form([
                        Forms\Components\Select::make('id_merchant')
                            ->label('Merchant')
                            ->options(Merchant::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->placeholder('-- Pilih Merchant --'),
                        Forms\Components\TextInput::make('name_wakiad')
                            ->label('Name Wakiad')
                            ->maxLength(255)
                            ->nullable(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Status')
                            ->default(true),
                    ])
                    ->fillForm(fn (Service $record): array => [
                        'id_merchant' => $record->id_merchant,
                        'name_wakiad' => $record->name_wakiad,
                        'is_active' => (bool) $record->is_active,
                    ])
                    ->action(function (Service $record, array $data): void {
                        $record->update([
                            'id_merchant' => $data['id_merchant'],
                            'name_wakiad' => $data['name_wakiad'],
                            'is_active' => $data['is_active'],
                        ]);

                        Notification::make()
                            ->title('Service berhasil diupdate')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListServices::route('/'),
            // 'create' => Pages\CreateService::route('/create'),
            // 'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
