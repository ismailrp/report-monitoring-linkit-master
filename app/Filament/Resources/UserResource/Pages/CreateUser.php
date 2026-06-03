<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     $operatorIds = [];

    //     foreach ($data as $key => $value) {
    //         if (str_starts_with($key, 'operators_country_') && is_array($value)) {
    //             $operatorIds = array_merge($operatorIds, $value);
    //             unset($data[$key]);
    //         }
    //     }

    //     $data['operators'] = array_unique($operatorIds);
    //     return $data;
    // }

    // protected function afterCreate(): void
    // {
    //     $this->record->operators()->sync($this->data['operators'] ?? []);
    // }

}
