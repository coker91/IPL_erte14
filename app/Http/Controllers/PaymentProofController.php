<?php

namespace App\Http\Controllers;

use App\Models\PaymentProof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentProofController extends Controller
{
    public function __invoke(Request $request, PaymentProof $proof)
    {
        $u = $request->user();
        abort_unless($u->is_active && ($proof->payment->submitted_by === $u->id || $u->can('payments.view')), 403);
        abort_unless(Storage::disk($proof->disk)->exists($proof->path), 404);

        return Storage::disk($proof->disk)->download($proof->path, 'resi-'.$proof->id.'.'.(match ($proof->mime_type) {
            'image/jpeg' => 'jpg','image/png' => 'png',default => 'pdf'
        }), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
