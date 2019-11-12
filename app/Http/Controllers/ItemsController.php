<?php
namespace Laraspace\Http\Controllers;

use Illuminate\Http\Request;
use Laraspace\Http\Requests;
use Laraspace\Item;
use Laraspace\TaxType;
use Laraspace\Tax;
use Laraspace\User;

class ItemsController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->has('limit') ? $request->limit : 10;

        $items = Item::applyFilters($request->only([
                'search',
                'price',
                'unit',
                'orderByField',
                'orderBy'
            ]))
            ->whereCompany($request->header('company'))
            ->latest()
            ->paginate($limit);

        return response()->json([
            'items' => $items,
            'taxTypes' => TaxType::latest()->get()
        ]);
    }

    public function edit(Request $request, $id)
    {
        $item = Item::with('taxes')->find($id);

        return response()->json([
            'item' => $item,
            'taxes' => Tax::whereCompany($request->header('company'))
                ->latest()
                ->get()
        ]);
    }

    public function store(Requests\ItemsRequest $request)
    {
        $item = new Item();
        $item->name = $request->name;
        $item->unit = $request->unit;
        $item->description = $request->description;
        $item->company_id = $request->header('company');
        $item->price = $request->price;
        $item->save();

        if ($request->has('taxes')) {
            foreach ($request->taxes as $tax) {
                $item->taxes()->create($tax);
            }
        }

        $item = Item::with('taxes')->find($item->id);

        return response()->json([
            'item' => $item
        ]);
    }

    public function update(Requests\ItemsRequest $request, $id)
    {
        $item = Item::find($id);
        $item->name = $request->name;
        $item->unit = $request->unit;
        $item->description = $request->description;
        $item->price = $request->price;
        $item->save();

        if ($request->has('taxes')) {
            foreach ($request->taxes as $tax) {
                $item->taxes()->updateOrCreate(
                    ['tax_type_id' => $tax['tax_type_id']],
                    ['amount' => $tax['amount'], 'percent' => $tax['percent'], 'name' => $tax['name']]
                );
            }
        }

        $item = Item::with('taxes')->find($item->id);

        return response()->json([
            'item' => $item
        ]);
    }

    public function destroy($id)
    {
        $data = Item::deleteItem($id);

        if (!$data) {
            return response()->json([
                'error' => 'item_attached'
            ]);
        }

        return response()->json([
            'success' => $data
        ]);
    }

    public function delete(Request $request)
    {
        $items = [];
        foreach ($request->id as $id) {
            $item = Item::deleteItem($id);
            if (!$item) {
                array_push($items, $id);
            }
        }

        if (empty($items)) {
            return response()->json([
                'success' => true
            ]);
        }

        return response()->json([
            'items' => $items
        ]);
    }
}
