<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\StoreAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoreSelectionController extends Controller
{
    public function select(Request $request): RedirectResponse
    {
        $user = $request->user();
        $storeId = $request->input('store_id');
        $storeId = $storeId === null || $storeId === '' ? null : (int) $storeId;

        // An empty selection means "every branch I am allowed to see", which is
        // always legitimate; only a concrete id can be rejected.
        if ($storeId !== null && ! StoreAccess::canAccess($user, $storeId)) {
            StoreAccess::syncSession($user);

            return back()->with('error', 'You do not have access to that branch.');
        }

        if ($storeId === null) {
            session()->forget(StoreAccess::SESSION_KEY);
        } else {
            session([StoreAccess::SESSION_KEY => $storeId]);
        }

        return back()->with('success', $storeId ? 'Branch changed.' : 'Showing all your branches.');
    }
}
