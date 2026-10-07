<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('نام محصول')->required(),
                TextInput::make('sku')->label('کد محصول')->required(),
                TextInput::make('unit_price')->label('قیمت')->required()->numeric()->suffix('ریال')
            ]);
    }
}
