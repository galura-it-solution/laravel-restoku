<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    public function stream(Request $request)
    {
        $user = $request->user();

        return response()->stream(function () use ($user) {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', false);
            @ini_set('implicit_flush', true);

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $orders = Order::where('user_id', $user->id)
                    ->where('status', 'done')
                    ->whereNull('notified_at')
                    ->orderBy('id')
                    ->limit(10)
                    ->get();

                foreach ($orders as $order) {
                    $order->update(['notified_at' => Carbon::now()]);

                    echo "event: order_ready\n";
                    echo 'data: ' . json_encode([
                        'order_id' => $order->id,
                        'notified_at' => $order->notified_at,
                    ]) . "\n\n";
                }

                echo "event: heartbeat\n";
                echo "data: ping\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
                sleep(3);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}
