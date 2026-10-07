<?php

namespace App\Filament\Resources\StockMovements\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('نوع گردش')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in' => 'ورود به انبار',
                        'out' => 'خروج از انبار',
                        'transfer' => 'انتقال به انبار',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        'transfer' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('product.name')->label('نام محصول')->searchable()->sortable(),
                TextColumn::make('product.sku')->label('کد محصول')->searchable()->sortable(),
                TextColumn::make('warehouse.name')->label('انبار')->searchable()->sortable(),
                TextColumn::make('quantity')->label('تعداد')->numeric()->sortable(),
                TextColumn::make('reference')->label('مرجع')->searchable(),
                TextColumn::make('created_at')->label('تاریخ ایجاد')->dateTime()->sortable(),
            ])->defaultSort('created_at', 'desc');
    }
}
