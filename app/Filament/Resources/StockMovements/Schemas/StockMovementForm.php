<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use App\Models\Product;
use App\Models\Warehouse;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class StockMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('نوع عملیات')
                    ->options([
                        'in' => 'ورود',
                        'out' => 'خروج',
                        'transfer' => 'انتقال به انباری دیگر',
                    ])
                    ->live()
                    ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                        if ($state !== 'transfer') {
                            $set('reference', null);
                            return;
                        }

                        $warehouseId = $get('warehouse_id');
                        if (!$warehouseId) {
                            return;
                        }

                        $warehouseName = Warehouse::query()->whereKey($warehouseId)->value('name');
                        $set('reference', $warehouseName);
                    })
                    ->required(),

                Select::make('product_id')
                    ->label('محصول')
                    ->options(
                        Product::query()
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (Product $product) => [
                                $product->id => "{$product->sku} — {$product->name}",
                            ])
                    )
                    ->searchable()
                    ->required(),

                Select::make('warehouse_id')
                    ->label('انبار مبدأ')
                    ->options(
                        Warehouse::query()->orderBy('name')->pluck('name', 'id')
                    )
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                        if ($get('type') !== 'transfer' || !$state) {
                            return;
                        }

                        $warehouseName = Warehouse::query()->whereKey($state)->value('name');
                        $set('reference', $warehouseName);
                    })
                    ->searchable()
                    ->required(),

                Select::make('destination_warehouse_id')
                    ->label('انبار مقصد')
                    ->options(function (Get $get) {
                        $warehouseId = $get('warehouse_id');
                        return Warehouse::query()
                            ->when(
                                $warehouseId,
                                fn ($query) => $query->whereKeyNot($warehouseId)
                            )
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required(fn (Get $get): bool => $get('type') === 'transfer')
                    ->visible(fn (Get $get): bool => $get('type') === 'transfer'),

                TextInput::make('quantity')
                    ->label('تعداد')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->required(),

                TextInput::make('reference')
                    ->label('مرجع')
                    ->maxLength(255)
                    ->disabled(fn (Get $get): bool => $get('type') === 'transfer')
                    ->dehydrated(),
            ]);
    }
}
