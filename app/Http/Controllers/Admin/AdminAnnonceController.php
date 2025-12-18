<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Annonce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminAnnonceController extends Controller
{
    
    public function index(Request $request)
    {
        $query = Annonce::with('user');

        // Recherche
        if ($q = $request->input('q')) {
            $query->where(function ($qb) use ($q) {
                $qb->where('titre', 'like', "%$q%")
                   ->orWhere('marque', 'like', "%$q%")
                   ->orWhere('modele', 'like', "%$q%")
                   ->orWhereHas('user', function ($uq) use ($q) {
                       $uq->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%");
                   });
            });
        }

        // Filtre par statut
        $status = $request->input('status', 'all');
        if ($status === 'pending') {
            $query->where('is_active', false);
        } elseif ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        // Filtre par marque
        if ($marque = $request->input('marque')) {
            $query->where('marque', $marque);
        }

        // Filtre par carburant
        if ($carburant = $request->input('carburant')) {
            $query->where('carburant', $carburant);
        }

        // Tri
        $sort = $request->input('sort', 'recent');
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'price_high':
                $query->orderBy('prix', 'desc');
                break;
            case 'price_low':
                $query->orderBy('prix', 'asc');
                break;
            case 'recent':
            default:
                $query->latest();
                break;
        }

        $annonces = $query->paginate(20)->withQueryString();

        // Récupérer les marques uniques pour le filtre
        $marques = Annonce::select('marque')
            ->distinct()
            ->whereNotNull('marque')
            ->orderBy('marque')
            ->pluck('marque');

        $filters = $request->only(['q', 'status', 'marque', 'carburant', 'sort']);

        return view('admin.annonces.index', compact('annonces', 'marques', 'filters'));
    }

    public function toggle(Annonce $annonce)
    {
        $annonce->update(['is_active' => !$annonce->is_active]);
        return back()->with('success', $annonce->is_active ? 'Annonce activée.' : 'Annonce désactivée.');
    }

    public function destroy(Annonce $annonce)
    {
        // Supprimer les images liées du disque public
        $images = [
            $annonce->image_path,
            $annonce->image_path_2,
            $annonce->image_path_3,
            $annonce->image_path_4,
            $annonce->image_path_5,
        ];

        foreach ($images as $path) {
            if (!empty($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $annonce->delete();
        return back()->with('success', 'Annonce supprimée.');
    }

    public function bulkAction(Request $request)
    {
        $action = $request->input('bulk_action'); // approve|reject|delete
        $ids = $request->input('selected_ids', []); // array d'IDs

        if (empty($ids) || !in_array($action, ['approve', 'reject', 'delete'])) {
            return back()->with('error', 'Action invalide ou aucune annonce sélectionnée.');
        }

        $annonces = Annonce::whereIn('id', $ids)->get();

        if ($action === 'approve') {
            Annonce::whereIn('id', $ids)->update(['is_active' => true]);
            return back()->with('success', count($annonces) . ' annonce(s) activée(s).');
        } elseif ($action === 'reject') {
            Annonce::whereIn('id', $ids)->update(['is_active' => false]);
            return back()->with('success', count($annonces) . ' annonce(s) désactivée(s).');
        } elseif ($action === 'delete') {
            foreach ($annonces as $annonce) {
                $images = [
                    $annonce->image_path,
                    $annonce->image_path_2,
                    $annonce->image_path_3,
                    $annonce->image_path_4,
                    $annonce->image_path_5,
                ];
                foreach ($images as $path) {
                    if (!empty($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }
            Annonce::whereIn('id', $ids)->delete();
            return back()->with('success', count($annonces) . ' annonce(s) supprimée(s).');
        }
    }
}
