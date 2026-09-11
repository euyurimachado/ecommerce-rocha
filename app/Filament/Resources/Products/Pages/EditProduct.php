<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Support\Products\DuplicateProduct;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $usesAutomaticSeo = request()->boolean('auto_seo')
            || Str::startsWith((string) ($data['name'] ?? ''), 'Cópia de ');

        $data['_slug_is_automatic'] = $usesAutomaticSeo;
        $data['_meta_description_is_automatic'] = $usesAutomaticSeo;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('duplicate')
                ->label('Duplicar produto')
                ->icon('heroicon-o-square-2-stack')
                ->requiresConfirmation()
                ->action(function () {
                    $copy = app(DuplicateProduct::class)($this->record);

                    Notification::make()->success()->title('Produto duplicado com sucesso')->send();

                    return $this->redirect(ProductResource::getUrl('edit', ['record' => $copy, 'auto_seo' => 1]));
                }),
            DeleteAction::make(),
        ];
    }
}
