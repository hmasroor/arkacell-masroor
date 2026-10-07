<?php

namespace App\Filament\Resources\StockMovements\Pages;

use App\Exceptions\InsufficientStockException;
use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Validation\ValidationException;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('ثبت گردش موجودی')
                ->using(function (array $data): StockMovement {
                    try {
                        $product = Product::query()->findOrFail($data['product_id']);
                        $warehouse = Warehouse::query()->findOrFail($data['warehouse_id']);
                        $inventory = new InventoryService();
                        if ($data['type'] === 'in') {
                            return $inventory->in(
                                $product,
                                $warehouse,
                                (int) $data['quantity'],
                                $data['reference'] ?? null
                            );
                        } elseif ($data['type'] === 'out') {
                            return $inventory->out(
                                $product,
                                $warehouse,
                                (int) $data['quantity'],
                                $data['reference'] ?? null
                            );
                        } elseif ($data['type'] === 'transfer') {
                            return $inventory->transfer(
                                $product,
                                $warehouse,
                                Warehouse::query()->findOrFail($data['destination_warehouse_id']),
                                (int) $data['quantity'],
                                $data['reference'] ?? null
                            );
                        } else {
                            throw new \InvalidArgumentException('Invalid stock movement type.');
                        }
                    } catch (InsufficientStockException $exception) {
                        Notification::make()
                            ->title('موجودی کافی نیست')
                            ->body(
                                "موجودی فعلی: {$exception->availableQuantity} — " .
                                "مقدار درخواستی: {$exception->requestedQuantity}"
                            )
                            ->danger()
                            ->send();

                        throw ValidationException::withMessages([
                            'quantity' => sprintf(
                                'موجودی کافی نیست. موجودی فعلی: %d، مقدار درخواستی: %d',
                                $exception->availableQuantity,
                                $exception->requestedQuantity,
                            ),
                        ]);
                    }
                }
            )
        ];
    }
}
