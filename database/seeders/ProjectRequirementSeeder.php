<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Account\User\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProjectRequirementSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('vi_VN');

        DB::transaction(function () use ($faker) {
            $this->seedCustomers($faker);
            $this->seedCarts();
            $this->seedShops($faker);
            $this->seedProducts($faker);
            $this->seedWarehouses($faker);
            $this->seedVouchers($faker);
            $this->seedOrders($faker);
            $this->seedReviews($faker);
            $this->seedFavorites();
            $this->seedPriceHistory();
        });
    }

    private function seedCustomers($faker): void
    {
        $current = DB::table('users')->count();
        $password = Hash::make('password');
        $users = [];
        for ($i = $current + 1; $i <= 100; $i++) {
            $users[] = [
                'customer_code' => sprintf('CUS-%06d', $i),
                'national_id' => sprintf('%012d', $i),
                'username' => sprintf('customer%03d', $i),
                'email' => sprintf('customer%03d@example.com', $i),
                'password' => $password,
                'email_verified_at' => now(),
                'account_status' => $i % 25 === 0 ? 'inactive' : 'active',
                'created_at' => now()->subDays(random_int(1, 1000)),
            ];
        }
        if ($users) {
            DB::table('users')->insert($users);
        }

        DB::table('users')->orderBy('id')->get()->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'customer_code' => $user->customer_code ?: sprintf('CUS-%06d', $user->id),
                'national_id' => $user->national_id ?: sprintf('%012d', $user->id),
            ]);
        });

        $profileRows = [];
        $addressRows = [];
        foreach (DB::table('users')->orderBy('id')->get() as $user) {
            if (!DB::table('user_profiles')->where('user_id', $user->id)->exists()) {
                $profileRows[] = [
                    'user_id' => $user->id,
                    'display_name' => 'Customer ' . $user->id,
                    'phone' => sprintf('09%08d', $user->id),
                    'gender' => $user->id % 2,
                    'date_of_birth' => now()->subYears(18 + ($user->id % 30))->toDateString(),
                ];
            }
            if (!DB::table('addressables')->where('addressable_id', $user->id)->where('addressable_type', User::class)->exists()) {
                $addressRows[] = $user->id;
            }
        }
        if ($profileRows) {
            DB::table('user_profiles')->insert($profileRows);
        }
        foreach ($addressRows as $userId) {
            $addressId = DB::table('addresses')->insertGetId([
                'full_name' => 'Customer ' . $userId,
                'phone' => sprintf('09%08d', $userId),
                'state' => 'Ha Noi',
                'city' => 'Ha Noi',
                'town' => 'District ' . (($userId % 12) + 1),
                'address' => ($userId + 10) . ' Sample Street',
                'is_home' => true,
            ]);
            DB::table('addressables')->insert([
                'addressable_id' => $userId,
                'addressable_type' => User::class,
                'address_id' => $addressId,
                'type_id' => 1,
            ]);
        }
    }

    private function seedShops($faker): void
    {
        DB::table('shops')->orderBy('id')->get()->each(function ($shop, $index) {
            DB::table('shops')->where('id', $shop->id)->update([
                'seller_code' => $shop->seller_code ?: sprintf('SEL-%05d', $index + 1),
                'tax_code' => $shop->tax_code ?: sprintf('TAX-%010d', $shop->id),
                'phone' => $shop->phone ?: sprintf('08%08d', $shop->id),
                'email' => $shop->email ?: sprintf('seller%03d@example.com', $shop->id),
                'is_active' => true,
            ]);
        });

        $needed = 20 - DB::table('shops')->count();
        if ($needed <= 0) {
            return;
        }
        $userIds = DB::table('users')->whereNotIn('id', DB::table('shops')->select('id'))->limit($needed)->pluck('id');
        foreach ($userIds as $userId) {
            DB::table('shops')->insert([
                'id' => $userId,
                'seller_code' => sprintf('SEL-%05d', DB::table('shops')->count() + 1),
                'tax_code' => sprintf('TAX-%010d', $userId),
                'slug' => 'shop-' . $userId,
                'name' => 'Sample Shop ' . $userId,
                'description' => $faker->sentence(),
                'avatar_image' => null,
                'cover_image' => null,
                'banners' => '[]',
                'phone' => sprintf('08%08d', $userId),
                'email' => sprintf('seller%03d@example.com', $userId),
                'is_active' => true,
                'created_at' => now()->subDays(random_int(1, 900)),
            ]);
        }
    }

    private function seedCarts(): void
    {
        foreach (DB::table('users')->pluck('id') as $userId) {
            $cartId = DB::table('carts')->where('user_id', $userId)->where('status', 'active')->value('id');
            if (!$cartId) {
                $cartId = DB::table('carts')->insertGetId([
                    'user_id' => $userId,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('cart_items')->where('user_id', $userId)->whereNull('cart_id')->update(['cart_id' => $cartId]);
        }
    }

    private function seedProducts($faker): void
    {
        DB::table('products')->orderBy('id')->get()->each(function ($product) {
            DB::table('products')->where('id', $product->id)->update([
                'product_code' => $product->product_code ?: sprintf('PRD-%07d', $product->id),
                'cost_price' => max(0, round(((float) $product->avg_price) * 0.65, 2)),
                'unit' => 'item',
            ]);
        });

        $shopIds = DB::table('shops')->orderBy('id')->limit(5)->pluck('id')->all();
        $needed = 500 - DB::table('products')->count();
        $start = (int) DB::table('products')->max('id') + 1;
        $rows = [];
        for ($i = 0; $i < $needed; $i++) {
            $id = $start + $i;
            $price = random_int(5000, 5000000) / 100;
            $rows[] = [
                'product_code' => sprintf('PRD-%07d', $id),
                'name' => 'Sample Product ' . $id,
                'description' => $faker->sentence(12),
                'weight' => random_int(10, 5000) / 100,
                'avg_rating' => 0,
                'avg_price' => $price,
                'cost_price' => round($price * 0.65, 2),
                'unit' => 'item',
                'sold' => 0,
                'images' => '[]',
                'videos' => '[]',
                'variations' => '[]',
                'shop_id' => $shopIds[$i % count($shopIds)],
                'brand_id' => null,
                'status_id' => 1,
                'created_at' => now()->subDays(random_int(1, 700)),
            ];
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('products')->insert($chunk);
        }

        $productsWithoutModel = DB::table('products as p')->leftJoin('product_models as pm', 'pm.product_id', '=', 'p.id')
            ->whereNull('pm.id')->select('p.id', 'p.avg_price')->get();
        $modelRows = [];
        foreach ($productsWithoutModel as $product) {
            $modelRows[] = [
                'product_id' => $product->id,
                'sku' => sprintf('SKU-%07d', $product->id),
                'price' => max(0.5, (float) $product->avg_price),
                'stock' => $product->id % 20 === 0 ? 0 : ($product->id % 15 === 0 ? 5 : random_int(20, 200)),
                'variation_index' => '[]',
                'is_default' => true,
            ];
        }
        foreach (array_chunk($modelRows, 200) as $chunk) {
            DB::table('product_models')->insert($chunk);
        }

        DB::table('product_models')->whereNull('sku')->orderBy('id')->get()->each(function ($model) {
            DB::table('product_models')->where('id', $model->id)->update(['sku' => sprintf('SKU-%07d', $model->id)]);
        });

        $categoryIds = DB::table('product_categories')->orderBy('id')->limit(20)->pluck('id')->all();
        foreach (DB::table('products')->select('id')->get() as $product) {
            DB::table('product_category_paths')->insertOrIgnore([
                'product_id' => $product->id,
                'category_id' => $categoryIds[$product->id % count($categoryIds)],
            ]);
        }
    }

    private function seedWarehouses($faker): void
    {
        if (DB::table('warehouses')->count() === 0) {
            $shopIds = DB::table('shops')->orderBy('id')->limit(5)->pluck('id');
            foreach ($shopIds as $index => $shopId) {
                DB::table('warehouses')->insert([
                    'warehouse_code' => sprintf('WH-%03d', $index + 1),
                    'shop_id' => $shopId,
                    'name' => 'Warehouse ' . ($index + 1),
                    'address' => $faker->address(),
                    'manager_user_id' => $shopId,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $warehouseByShop = DB::table('warehouses')->pluck('id', 'shop_id');
        $models = DB::table('product_models as pm')->join('products as p', 'p.id', '=', 'pm.product_id')
            ->select('pm.id', 'pm.stock', 'p.shop_id')->get();
        foreach ($models as $model) {
            if (!$warehouseByShop->has($model->shop_id)) {
                continue;
            }
            $exists = DB::table('inventory_stocks')->where('warehouse_id', $warehouseByShop[$model->shop_id])
                ->where('product_model_id', $model->id)->exists();
            DB::table('inventory_stocks')->updateOrInsert([
                'warehouse_id' => $warehouseByShop[$model->shop_id],
                'product_model_id' => $model->id,
            ], [
                'quantity' => $model->stock,
                'reserved_quantity' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if (!$exists) {
                DB::table('inventory_movements')->insert([
                'warehouse_id' => $warehouseByShop[$model->shop_id],
                'product_model_id' => $model->id,
                'order_id' => null,
                'movement_type' => 'import',
                'quantity_change' => $model->stock,
                'quantity_after' => $model->stock,
                'note' => 'Initial sample inventory',
                'created_at' => now()->subDays(30),
                ]);
            }
        }
    }

    private function seedVouchers($faker): void
    {
        $needed = 20 - DB::table('vouchers')->count();
        for ($i = 1; $i <= $needed; $i++) {
            $expired = $i > 15;
            DB::table('vouchers')->insert([
                'code' => sprintf('VOUCHER%02d', $i),
                'name' => 'Sample Voucher ' . $i,
                'discount_type' => $i % 2 === 0 ? 'percentage' : 'fixed',
                'discount_value' => $i % 2 === 0 ? 10 : 5,
                'minimum_order_value' => 20,
                'maximum_discount' => 50,
                'starts_at' => $expired ? now()->subMonths(3) : now()->subMonth(),
                'ends_at' => $expired ? now()->subMonth() : now()->addMonths(3),
                'max_uses' => 100,
                'max_uses_per_customer' => 1,
                'used_count' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedOrders($faker): void
    {
        $this->backfillLegacyOrders();
        $needed = 1000 - DB::table('orders')->count();
        $userIds = DB::table('users')->pluck('id')->all();
        $addresses = DB::table('addressables')->where('addressable_type', User::class)->pluck('address_id', 'addressable_id');
        $shopIds = DB::table('warehouses')->pluck('shop_id')->all();
        $modelsByShop = DB::table('product_models as pm')->join('products as p', 'p.id', '=', 'pm.product_id')
            ->whereIn('p.shop_id', $shopIds)->select('pm.id', 'pm.product_id', 'pm.price', 'pm.variation_index', 'p.shop_id', 'p.product_code', 'p.name')->get()->groupBy('shop_id');

        for ($i = 0; $i < $needed; $i++) {
            $shopId = $shopIds[$i % count($shopIds)];
            $availableModels = $modelsByShop[$shopId]->values();
            $selected = collect([0, 1, 2])->map(fn ($offset) => $availableModels[($i * 3 + $offset) % $availableModels->count()]);
            $userId = $userIds[$i % count($userIds)];
            $status = match ($i % 10) {
                0 => OrderStatus::Cancelled->value,
                1 => OrderStatus::Return->value,
                2 => OrderStatus::Delivering->value,
                3 => OrderStatus::Ready->value,
                4 => OrderStatus::Processing->value,
                default => OrderStatus::Delivered->value,
            };
            $createdAt = now()->subDays(random_int(0, 700))->subMinutes($i % 1440);
            $subtotal = $selected->sum(fn ($model) => round($model->price * (($i % 3) + 1), 2));
            $shipping = 2.5;
            $first = $selected->first();
            $orderId = DB::table('orders')->insertGetId([
                'order_code' => sprintf('ORD-SEED-%07d', DB::table('orders')->count() + 1),
                'name' => 'Sample multi-item order',
                'variations' => $first->variation_index,
                'quantity' => 3 * (($i % 3) + 1),
                'total' => $subtotal,
                'grand_total' => $subtotal + $shipping,
                'subtotal' => $subtotal,
                'shipping_fee' => $shipping,
                'discount_amount' => 0,
                'payment_total' => $subtotal + $shipping,
                'commission_rate' => 5,
                'commission_amount' => round(($subtotal + $shipping) * 0.05, 2),
                'user_id' => $userId,
                'product_id' => $first->product_id,
                'product_model_id' => $first->id,
                'received_address_id' => $addresses[$userId] ?? $addresses->first(),
                'pickup_address_id' => $addresses[$shopId] ?? $addresses->first(),
                'status_id' => $status,
                'shop_id' => $shopId,
                'created_at' => $createdAt,
            ]);

            $itemRows = [];
            foreach ($selected as $model) {
                $quantity = ($i % 3) + 1;
                $itemRows[] = [
                    'order_id' => $orderId,
                    'product_id' => $model->product_id,
                    'product_model_id' => $model->id,
                    'product_code' => $model->product_code,
                    'product_name' => $model->name,
                    'variations' => $model->variation_index,
                    'quantity' => $quantity,
                    'unit_price' => $model->price,
                    'line_total' => round($model->price * $quantity, 2),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }
            DB::table('order_items')->insert($itemRows);
            $this->insertOrderProgresses($orderId, $status, $createdAt);
            DB::table('payments')->insert([
                'payment_code' => sprintf('PAY-SEED-%07d', $orderId),
                'order_id' => $orderId,
                'method' => ['cod', 'bank_transfer', 'e_wallet'][$i % 3],
                'amount' => $subtotal + $shipping,
                'paid_at' => in_array($status, [OrderStatus::Delivered->value, OrderStatus::Return->value]) ? $createdAt->copy()->addDays(3) : null,
                'status' => in_array($status, [OrderStatus::Delivered->value, OrderStatus::Return->value]) ? 'paid' : 'pending',
                'metadata' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            if (!in_array($status, [OrderStatus::Processing->value, OrderStatus::Cancelled->value])) {
                DB::table('shipments')->insert([
                    'tracking_code' => sprintf('TRACK-%07d', $orderId),
                    'order_id' => $orderId,
                    'carrier' => ['GHN', 'GHTK', 'Viettel Post'][$i % 3],
                    'picked_up_at' => $createdAt->copy()->addDay(),
                    'estimated_delivery_at' => $createdAt->copy()->addDays(4),
                    'delivered_at' => in_array($status, [OrderStatus::Delivered->value, OrderStatus::Return->value]) ? $createdAt->copy()->addDays(3) : null,
                    'shipping_fee' => $shipping,
                    'status' => in_array($status, [OrderStatus::Delivered->value, OrderStatus::Return->value]) ? 'delivered' : 'shipping',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }

        $validVouchers = DB::table('vouchers')->where('ends_at', '>', now())->limit(10)->get();
        $orders = DB::table('orders')->whereNull('voucher_id')->where('subtotal', '>=', 20)->orderBy('id')->limit($validVouchers->count())->get();
        foreach ($orders as $index => $order) {
            $voucher = $validVouchers[$index];
            $discount = min(5, $order->subtotal);
            DB::table('orders')->where('id', $order->id)->update([
                'voucher_id' => $voucher->id,
                'discount_amount' => $discount,
                'payment_total' => max(0, $order->subtotal + $order->shipping_fee - $discount),
            ]);
            DB::table('voucher_usages')->insert([
                'voucher_id' => $voucher->id,
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'discount_amount' => $discount,
                'used_at' => now(),
            ]);
            DB::table('vouchers')->where('id', $voucher->id)->increment('used_count');
        }

        $missingItems = 3000 - DB::table('order_items')->count();
        if ($missingItems > 0) {
            $orders = DB::table('orders')->orderBy('id')->limit($missingItems)->get();
            foreach ($orders as $order) {
                $model = DB::table('product_models as pm')->join('products as p', 'p.id', '=', 'pm.product_id')
                    ->where('p.shop_id', $order->shop_id)->select('pm.id', 'pm.price', 'pm.variation_index', 'p.id as product_id', 'p.product_code', 'p.name')->first();
                DB::table('order_items')->insert([
                    'order_id' => $order->id,
                    'product_id' => $model->product_id,
                    'product_model_id' => $model->id,
                    'product_code' => $model->product_code,
                    'product_name' => $model->name,
                    'variations' => $model->variation_index,
                    'quantity' => 1,
                    'unit_price' => $model->price,
                    'line_total' => $model->price,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->created_at,
                ]);
            }
        }
    }

    private function backfillLegacyOrders(): void
    {
        foreach (DB::table('orders')->orderBy('id')->get() as $order) {
            DB::table('orders')->where('id', $order->id)->update([
                'order_code' => $order->order_code ?: sprintf('ORD-LEGACY-%07d', $order->id),
                'subtotal' => $order->subtotal ?: $order->total,
                'payment_total' => $order->payment_total ?: $order->grand_total,
                'commission_rate' => 5,
                'commission_amount' => round(($order->payment_total ?: $order->grand_total) * 0.05, 2),
            ]);
            if (!DB::table('order_items')->where('order_id', $order->id)->exists()) {
                $product = DB::table('products')->where('id', $order->product_id)->first();
                $model = DB::table('product_models')->where('id', $order->product_model_id)->first();
                DB::table('order_items')->insert([
                    'order_id' => $order->id,
                    'product_id' => $order->product_id,
                    'product_model_id' => $order->product_model_id,
                    'product_code' => $product?->product_code,
                    'product_name' => $order->name,
                    'variations' => $order->variations,
                    'quantity' => $order->quantity,
                    'unit_price' => $order->quantity ? round($order->total / $order->quantity, 2) : ($model?->price ?? 0),
                    'line_total' => $order->total,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->created_at,
                ]);
            }
            if (!DB::table('payments')->where('order_id', $order->id)->exists()) {
                DB::table('payments')->insert([
                    'payment_code' => sprintf('PAY-LEGACY-%07d', $order->id),
                    'order_id' => $order->id,
                    'method' => 'cod',
                    'amount' => $order->grand_total,
                    'paid_at' => $order->status_id == OrderStatus::Delivered->value ? $order->created_at : null,
                    'status' => $order->status_id == OrderStatus::Delivered->value ? 'paid' : 'pending',
                    'created_at' => $order->created_at,
                    'updated_at' => $order->created_at,
                ]);
            }
        }
    }

    private function insertOrderProgresses(int $orderId, int $status, $createdAt): void
    {
        $path = match ($status) {
            1 => [1],
            2 => [1, 2],
            3 => [1, 2, 3],
            4 => [1, 2, 3, 4],
            5 => [1, 5],
            6 => [1, 2, 3, 4, 6],
        };
        foreach ($path as $index => $statusId) {
            DB::table('order_progresses')->insert([
                'order_id' => $orderId,
                'status_id' => $statusId,
                'note' => 'Sample status history',
                'created_at' => $createdAt->copy()->addHours($index * 12),
            ]);
        }
    }

    private function seedReviews($faker): void
    {
        $needed = 500 - DB::table('product_reviews')->count();
        $eligible = DB::table('orders as o')->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->whereIn('o.status_id', [OrderStatus::Delivered->value, OrderStatus::Return->value])
            ->select('o.id as order_id', 'o.user_id', 'o.shop_id', 'oi.product_id', 'oi.variations')->get();
        $index = 0;
        foreach ($eligible as $row) {
            if (DB::table('product_reviews')->count() >= 500) {
                break;
            }
            DB::table('product_reviews')->insertOrIgnore([
                'user_id' => $row->user_id,
                'shop_id' => $row->shop_id,
                'product_id' => $row->product_id,
                'order_id' => $row->order_id,
                'rating' => ($index % 5) + 1,
                'comment' => $faker->sentence(),
                'reply' => '',
                'variations' => $row->variations ?: '[]',
                'create_at' => now()->subDays(random_int(0, 365)),
            ]);
            $index++;
        }
    }

    private function seedFavorites(): void
    {
        $productIds = DB::table('products')->limit(50)->pluck('id')->all();
        foreach (DB::table('users')->limit(100)->pluck('id') as $userId) {
            foreach ([$productIds[$userId % count($productIds)], $productIds[($userId + 7) % count($productIds)]] as $productId) {
                DB::table('favorites')->insertOrIgnore(['user_id' => $userId, 'product_id' => $productId, 'created_at' => now()]);
            }
        }
    }

    private function seedPriceHistory(): void
    {
        foreach (DB::table('product_models')->orderBy('id')->limit(50)->get() as $model) {
            $base = (float) $model->price;
            DB::table('product_price_histories')->insert([
                ['product_model_id' => $model->id, 'old_price' => $base * 0.8, 'new_price' => $base * 0.9, 'effective_at' => now()->subMonths(2)],
                ['product_model_id' => $model->id, 'old_price' => $base * 0.9, 'new_price' => $base, 'effective_at' => now()->subMonth()],
            ]);
        }
    }
}
