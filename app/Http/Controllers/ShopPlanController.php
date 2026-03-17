<?php

namespace App\Http\Controllers;

use App\Models\ShopPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShopPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $userId = auth()->id();
        ShopPlan::where('created_by', $userId)
            ->whereDate('date_scheduled', '<', Carbon::today())
            ->whereIn('status', [0, 1])
            ->update(['status' => 3]);

        $fromDate = Carbon::now()->subDays(3)->startOfDay();
        $shopPlans = ShopPlan::select('id', 'address', 'date_scheduled', 'budget', 'number_of_items', 'status', 'created_by', 'updated_at')
            ->where('created_by', $userId)
            ->where('updated_at', '>=', $fromDate)
            ->latest()
            ->get();

        return view('landing', compact('shopPlans'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('plans.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'address' => 'required|string|max:255',
            'date_scheduled' => 'required|date',
            'budget' => 'required|numeric',
            'number_of_items' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.expected_quantity' => 'required|numeric|min:0'
        ], [
            'items.*.name.required' => 'The item name field is required.',
            'items.*.name.string' => 'The item name must be a string.',
            'items.*.name.max' => 'The item may not be greater than 255 characters.',
            'items.*.expected_quantity.required' => 'The item expected quantity field is required.',
            'items.*.expected_quantity.numeric' => 'The item expected quantity must be a number.',
            'items.*.expected_quantity.min' => 'The item expected quantity must be at least 0.',
        ]);


        $created_by = Auth::id();
        $dateScheduled = Carbon::parse($request->date_scheduled);
        $dateScheduled->format('Y-m-d 00:00:00');

        $status = 0;
        if ($dateScheduled->lt(today())) {
            $status = 3;
        }

        $shopPlan = ShopPlan::create([
            'created_by' => $created_by,
            'address' => $request->address,
            'date_scheduled' => $dateScheduled,
            'budget' => $request->budget,
            'number_of_items' => $request->number_of_items,
            'status' => $status
        ]);

        if ($shopPlan) {
            $itemsData = collect($request->items)->map(function($item) use ($shopPlan) {
                return [
                    'shop_plan_id' => $shopPlan->id,
                    'name' => $item['name'],
                    'expected_quantity' => $item['expected_quantity'],
                    'price' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            $shopPlan->items()->insert($itemsData);

            // return redirect()->route('list')->with('success', 'Shop Plan Created Successfully');
            return response()->json([
                'status' => 'success',
                'message' => 'Shop Plan Created Successfully'
            ], 200);
        }

        return response()->json([
            'status' => 'error', 
            'message' => 'Failed to create Shop Plan'
        ], 500);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $shopPlan = ShopPlan::find($id);
        return view('plans.update', compact('shopPlan'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function startPlan(Request $request)
    {
        $inProgress = ShopPlan::where('created_by', auth()->id())->where('status', 1)->get();

        if (count($inProgress) > 0) {
            return response()->json([
                'status' => 'warning', 
                'message' => 'Another Plan is in Progress',
            ]);
        }

        $shopPlan = ShopPlan::find($request->id);

        if ($shopPlan) {
            $shopPlan->status = 1;
            $shopPlan->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Plan started'
            ]);
        }

        return response()->json([
            'status' => 'error', 
            'message' => 'Something went wrong',
        ], 500);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'budget' => 'required|numeric',
            'items.*' => 'required|array',
            'items.*.id' => 'required|exists:items,id',
            'items.*.actual_quantity' => 'required|numeric|min:0',
            'items.*.price' => 'required|numeric|min:0',
        ], [
            'items.*.id.required' => "The item's id field is required",
            'items.*.id.unique' => "The item must exist in the database.",
            'items.*.actual_quantity.required' => "The item actual quantity field is required",
            'items.*.actual_quantity.numeric' => "The item actual quantity must be a number",
            'items.*.actual_quantity.min' => "The item actual quantity may not be lesser than zero",
            'items.*.price.required' => "The item price field is required",
            'items.*.price.numeric' => "The item price must be a number",
            'items.*.price.min' => "The item price may not be lesser than zero",
        ]);

        $plan = ShopPlan::find($id);

        if ($plan) {
            DB::transaction(function() use ($request, $plan) {
                $itemsById = $plan->items->keyBy('id');

                foreach ($request->items as $itemData) {
                    $item = $itemsById[$itemData['id']];
                    if ($item) {
                        $item->update([
                            'price' => $itemData['price'],
                            'actual_quantity' => $itemData['actual_quantity'],
                        ]);
                    }
                }
                
                $plan->update([
                    'budget' => $request->budget,
                    'status' => 2,
                ]);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Shop Plan Completed',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Shop Plan not found',
        ], 500);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
