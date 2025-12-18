<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        return view('admin.monetization.payments.index');
    }

    public function show($id)
    {
        return view('admin.monetization.payments.show');
    }

    public function verify($id)
    {
        return redirect()->route('admin.payments.index')
            ->with('success', 'Paiement vérifié avec succès');
    }

    public function refund($id)
    {
        return redirect()->route('admin.payments.index')
            ->with('success', 'Paiement remboursé avec succès');
    }
}
