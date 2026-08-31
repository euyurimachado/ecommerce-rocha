<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Support\Products\DuplicateProduct;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

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

                    return $this->redirect(ProductResource::getUrl('edit', ['record' => $copy]));
                }),
            DeleteAction::make(),
        ];
    }
}
