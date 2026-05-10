<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class UserManual extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Manual de Uso e Integración';

    protected static ?string $navigationGroup = 'General';

    protected static ?string $title = 'Manual de Uso e Integración';

    protected static string $view = 'filament.pages.user-manual';
}
