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
        $q = (string) $request->input('q', '');
        $status = $request->input('status', 'all'); // all|active|inactive

        $query = Annonce::with('user')->latest();

        if ($q !== '') {
            $query->where(function ($qb) use ($q) {
                $qb->where('titre', 'like', "%$q%");
                $qb->orWhere('marque', 'like', "%$q%");
                $qb->orWhere('modele', 'like', "%$q%");
                $qb->orWhereHas('user', function ($uq) use ($q) {
                    $uq->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%");
                });
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $annonces = $query->paginate(20)->withQueryString();

        return view('admin.annonces.index', compact('annonces', 'q', 'status'));
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
