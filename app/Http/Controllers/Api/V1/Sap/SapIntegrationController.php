<?php

namespace App\Http\Controllers\Api\V1\Sap;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class SapIntegrationController extends Controller
{
    public function getCustomers(Request $request)
    {
        // SAP fetches updated customers
        return response()->json(User::whereNotNull('email')->paginate(50));
    }

    public function upsertCustomer(Request $request)
    {
        // SAP pushes customer data
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
            'sap_id' => 'nullable|string'
        ]);

        $user = User::updateOrCreate(
            ['email' => $validated['email']],
            ['name' => $validated['name']]
        );

        return response()->json(['message' => 'Customer synced', 'id' => $user->id]);
    }

    public function getTransactions(Request $request)
    {
        // SAP fetches financial movements
        $query = WalletTransaction::query();
        
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        return response()->json($query->paginate(100));
    }

    public function getInvoices(Request $request)
    {
        return response()->json(['message' => 'Invoicing logic not implemented yet']);
    }
}
