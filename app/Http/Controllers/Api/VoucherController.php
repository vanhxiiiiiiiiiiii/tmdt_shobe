<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promotion\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index()
    {
        return response()->json(['status' => true, 'data' => Voucher::orderByDesc('created_at')->get()]);
    }

    public function store(Request $request)
    {
        $voucher = Voucher::create($this->validated($request));
        return response()->json(['status' => true, 'data' => $voucher], 201);
    }

    public function update(Request $request, Voucher $voucher)
    {
        $voucher->update($this->validated($request, $voucher->id));
        return response()->json(['status' => true, 'data' => $voucher->fresh()]);
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->update(['is_active' => false]);
        return response()->json(['status' => true]);
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code' . ($id ? ',' . $id : ''),
            'name' => 'required|string|max:150',
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0.01',
            'minimum_order_value' => 'required|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'max_uses' => 'required|integer|min:1',
            'max_uses_per_customer' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
