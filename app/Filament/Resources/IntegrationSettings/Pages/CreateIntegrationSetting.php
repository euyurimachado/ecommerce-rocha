<?php

namespace App\Filament\Resources\IntegrationSettings\Pages;

use App\Filament\Resources\IntegrationSettings\IntegrationSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIntegrationSetting extends CreateRecord
{
    protected static string $resource = IntegrationSettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->extractCredentials($data);
    }

    private function extractCredentials(array $data): array
    {
        $credentials = [];
        foreach (['public_key', 'access_token', 'webhook_secret', 'api_key', 'webhook_token', 'client_id', 'client_secret'] as $key) {
            if (filled($data['credential_'.$key] ?? null)) {
                $credentials[$key] = $data['credential_'.$key];
            }
            unset($data['credential_'.$key]);
        }
        $data['credentials'] = $credentials;

        return $data;
    }
}
