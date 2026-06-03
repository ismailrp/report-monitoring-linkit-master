<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Country;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                // Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    // ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name') // ambil relasi roles
                    // ->multiple()
                    ->preload()
                    ->required(),


                Forms\Components\TextInput::make('chat_id')
                    ->label('Chat ID Telegram')
                    ->required()
                    ->default('0')
                    ->maxLength(20),

                Forms\Components\TextInput::make('alert_id')
                    ->label('Alert ID Telegram')
                    ->required()
                    ->default('0')
                    ->maxLength(20),

                Forms\Components\TextInput::make('renewal_id')
                    ->label('Renewal ID Telegram')
                    ->required()
                    ->default('0')
                    ->maxLength(20),

                Forms\Components\TextInput::make('weekly_id')
                    ->label('Weekly ID Telegram')
                    ->required()
                    ->default('0')
                    ->maxLength(20),

                Forms\Components\TextInput::make('alert_mo_id')
                    ->label('Alert MO ID Telegram')
                    ->required()
                    ->default('0')
                    ->maxLength(20),

                Fieldset::make('Operator Access')
                    ->schema(static::operatorGroupedByCountry())
                    ->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('⏱️ Latest data is updated every 30 minutes')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('email_verified_at')
                //     ->dateTime()
                //     ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(', ')
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
                Tables\Actions\ViewAction::make(),
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


    public static function operatorGroupedByCountry(): array
    {
        $countries = Country::with('operators')->get();

        $groups = [];

        foreach ($countries as $country) {
            if ($country->operators->count() === 0) {
                continue;
            }

            $groups[] =
                Fieldset::make($country->country)
                    ->schema([
                        CheckboxList::make('operators')
                            ->relationship('operators', 'operator')
                            ->options(
                                $country->operators->pluck('operator', 'id')
                            )
                            ->columns(2)
                            ->gridDirection('row'),
                    ])
                    ->columns(1)
                    ->columnSpanFull()
                    ->extraAttributes([
                        'class' => 'p-4 border rounded-xl shadow-sm bg-white dark:bg-gray-800',
                    ]);
        }

        return $groups;
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
