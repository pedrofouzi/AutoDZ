<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Annonce;

class AdminAnnonceController extends Controller
{
    
    public function index()
    {
        $annonces = Annonce::with('user')->latest()->paginate(20);
        return view('admin.annonces.index', compact('annonces'));
    }

    public function toggle(Annonce $annonce)
    {
        // plus tard : ex $annonce->update(['is_active' => !$annonce->is_active]);
        return back();
    }

    public function destroy(Annonce $annonce)
    {
        $annonce->delete();
        return back()->with('success', 'Annonce supprimée.');
    }
}
