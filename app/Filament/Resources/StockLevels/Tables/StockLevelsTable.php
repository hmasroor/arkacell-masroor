<?php

namespace App\Filament\Resources\StockLevels\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockLevelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')->label('نام محصول')->searchable()->sortable(),
                TextColumn::make('product.sku')->label('کد محصول')->searchable()->sortable(),
                TextColumn::make('warehouse.name')->label('انبار')->searchable()->sortable(),
                TextColumn::make('quantity')->label('موجودی')->numeric()->sortable(),
                TextColumn::make('updated_at')->label('آخرین بروزرسانی')->dateTime()->sortable(),
            ])->modifyQueryUsing(
                fn ($query) => $query->where('quantity', '>', 0)
            );
    }
}
