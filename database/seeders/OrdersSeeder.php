<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Book;
use Illuminate\Support\Str;

class OrdersSeeder extends Seeder
{
    public function run()
    {
        $users = User::where('role', 'customer')->get();
        $books = Book::all();
        if ($users->isEmpty() || $books->isEmpty()) {
            return;
        }

        $statuses = ['pending', 'processing', 'shipped', 'delivered'];
        $payments = ['card', 'paypal', 'cod'];

        // create 8 sample orders
        for ($i = 0; $i < 8; $i++) {
            $user = $users->random();

            $countItems = rand(1, 4);
            $selected = $books->random(min($countItems, $books->count()));
            // ensure we have a collection
            $selectedItems = $selected instanceof \Illuminate\Support\Collection ? $selected : collect([$selected]);

            $total = 0;
            $order = Order::create([
                'user_id' => $user->id,
                'total' => 0,
                'status' => $statuses[array_rand($statuses)],
                'payment_method' => $payments[array_rand($payments)],
                'shipping_address' => $user->address ?? '123 Example St',
                'city' => 'Sample City',
                'state' => null,
                'postal_code' => null,
                'phone' => $user->phone ?? '+10000000000',
                'notes' => 'Seeded order',
                'created_at' => now()->subDays(rand(1, 30)),
            ]);

            foreach ($selectedItems as $book) {
                $qty = rand(1, 3);
                $price = $book->price;
                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id' => $book->id,
                    'quantity' => $qty,
                    'price' => $price,
                ]);
                $total += $price * $qty;
            }

            $order->update(['total' => $total]);
        }
    }
}
