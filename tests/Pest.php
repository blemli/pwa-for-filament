<?php

use Blemli\Pwa\Tests\TestCase;
use Filament\Facades\Filament;
use Filament\Panel;

uses(TestCase::class)->in(__DIR__);

function bootPanel(string $id = 'admin'): Panel
{
    $panel = Filament::getPanel($id);

    Filament::setCurrentPanel($panel);
    Filament::bootCurrentPanel();

    return $panel;
}
