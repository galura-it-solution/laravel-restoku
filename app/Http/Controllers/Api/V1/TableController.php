<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TableRequest;
use App\Http\Resources\Api\V1\TableResource;
use App\Models\RestaurantTable;
use App\Services\TableService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TableController extends Controller
{
    public function __construct(private TableService $tableService)
    {
        $this->authorizeResource(RestaurantTable::class, 'table');
    }

    public function index(Request $request)
    {
        $tables = $this->tableService->list($request->only(['status', 'per_page']));

        return TableResource::collection($tables);
    }

    public function store(TableRequest $request)
    {
        $table = $this->tableService->create($request->validated());

        return new TableResource($table);
    }

    public function show(RestaurantTable $table)
    {
        return new TableResource($table);
    }

    public function update(TableRequest $request, RestaurantTable $table)
    {
        $table = $this->tableService->update($table, $request->validated());

        return new TableResource($table);
    }

    public function destroy(RestaurantTable $table)
    {
        $this->tableService->delete($table);

        return response()->json(['message' => 'Meja dihapus.']);
    }

    public function stream(Request $request)
    {
        $this->authorize('viewAny', RestaurantTable::class);

        return response()->stream(function () {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', false);
            @ini_set('implicit_flush', true);

            $lastUpdated = Carbon::now()->subSeconds(1);

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $tables = RestaurantTable::query()
                    ->where('updated_at', '>', $lastUpdated)
                    ->orderBy('updated_at')
                    ->limit(50)
                    ->get();

                foreach ($tables as $table) {
                    $lastUpdated = $table->updated_at ?? $lastUpdated;
                    echo "event: table_update\n";
                    echo 'data: ' . json_encode([
                        'id' => $table->id,
                        'status' => $table->status,
                        'updated_at' => $table->updated_at,
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
