<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('نام و نام خانوادگی')->required(),
                TextInput::make('email')->label('آدرس ایمیل')->email()->required(),
                TextInput::make('password')->label('رمز عبور')->password()->required(),
            ]);
    }
}
