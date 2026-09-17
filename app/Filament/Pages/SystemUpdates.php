<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Support\CoreUpdates\ReleaseChecker;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class SystemUpdates extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $navigationLabel = 'Atualizações';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 100;

    protected static ?string $title = 'Atualizações do sistema';

    protected static ?string $slug = 'system/updates';

    protected string $view = 'filament.pages.system-updates';

    /** @var array<string, mixed> */
    public array $release = [];

    public function mount(): void
    {
        $this->loadRelease();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    public function refreshRelease(): void
    {
        $this->loadRelease(true);

        Notification::make()
            ->title($this->release['status'] === 'unavailable' ? 'Consulta indisponível' : 'Metadados atualizados')
            ->color($this->release['status'] === 'unavailable' ? 'danger' : 'success')
            ->send();
    }

    private function loadRelease(bool $force = false): void
    {
        $result = app(ReleaseChecker::class)->check(force: $force);
        $manifest = $result->manifest;

        $this->release = [
            'installed' => $result->installedVersion,
            'source' => $result->installedVersionSource,
            'channel' => $result->channel,
            'status' => $result->status,
            'message' => $result->message,
            'available' => $manifest?->latest,
            'released_at' => $manifest?->releasedAt->format('d/m/Y H:i'),
            'security' => $manifest?->security ?? false,
            'breaking' => $manifest?->breaking ?? false,
            'release_notes' => $manifest?->releaseNotes,
            'checksum' => $manifest?->checksum,
            'compatible' => $result->compatibility?->compatible,
            'compatibility_reasons' => $result->compatibility?->reasons ?? [],
        ];
    }
}
