<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Annonce;
use App\Models\User;
use Illuminate\Http\Request;

class AdminStatsController extends Controller
{
    /**
     * Afficher les statistiques du site
     */
    public function index()
    {
        $totalViews = Annonce::sum('views');
        $activeCount = Annonce::where('is_active', true)->count();
        $inactiveCount = Annonce::where('is_active', false)->count();
        
        $topViewed = Annonce::with('user')
            ->where('is_active', true)
            ->orderByDesc('views')
            ->take(10)
            ->get();

        return view('admin.stats.index', compact('totalViews', 'activeCount', 'inactiveCount', 'topViewed'));
    }
}
