<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Annonce;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnonceApiController extends Controller
{
    /**
     * Liste des annonces avec filtres et pagination
     * GET /api/annonces
     */
    public function index(Request $request)
    {
        $query = Annonce::with('user')->where('is_active', true);

        // Filtres
        if ($request->filled('marque')) {
            $query->where('marque', 'like', '%' . $request->marque . '%');
        }

        if ($request->filled('modele')) {
            $query->where('modele', 'like', '%' . $request->modele . '%');
        }

        if ($request->filled('wilaya')) {
            $query->where('ville', 'like', '%' . $request->wilaya . '%');
        }

        if ($request->filled('price_min')) {
            $query->where('prix', '>=', $request->price_min);
        }

        if ($request->filled('price_max')) {
            $query->where('prix', '<=', $request->price_max);
        }

        if ($request->filled('year_min')) {
            $query->where('annee', '>=', $request->year_min);
        }

        if ($request->filled('year_max')) {
            $query->where('annee', '<=', $request->year_max);
        }

        if ($request->filled('km_min')) {
            $query->where('kilometrage', '>=', $request->km_min);
        }

        if ($request->filled('km_max')) {
            $query->where('kilometrage', '<=', $request->km_max);
        }

        if ($request->filled('fuel')) {
            $query->where('carburant', $request->fuel);
        }

        if ($request->filled('gearbox')) {
            $query->where('boite_vitesse', $request->gearbox);
        }

        if ($request->filled('q')) {
            $query->where(function($q) use ($request) {
                $q->where('titre', 'like', '%' . $request->q . '%')
                  ->orWhere('description', 'like', '%' . $request->q . '%');
            });
        }

        // Tri
        switch ($request->get('sort', 'latest')) {
            case 'price_asc':
                $query->orderBy('prix', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('prix', 'desc');
                break;
            case 'km_asc':
                $query->orderBy('kilometrage', 'asc');
                break;
            case 'year_desc':
                $query->orderBy('annee', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $annonces = $query->paginate(20);

        // Get user's favorites
        $favoriteIds = [];
        if ($request->user()) {
            $favoriteIds = Favorite::where('user_id', $request->user()->id)
                ->pluck('annonce_id')
                ->toArray();
        }

        return response()->json([
            'data' => $annonces->map(function($annonce) use ($favoriteIds) {
                return $this->formatAnnonce($annonce, in_array($annonce->id, $favoriteIds));
            }),
            'current_page' => $annonces->currentPage(),
            'last_page' => $annonces->lastPage(),
            'per_page' => $annonces->perPage(),
            'total' => $annonces->total(),
        ]);
    }

    /**
     * Détail d'une annonce
     * GET /api/annonces/{id}
     */
    public function show(Request $request, $id)
    {
        $annonce = Annonce::with('user')->findOrFail($id);

        // Increment views only if not the owner
        if (!$request->user() || $request->user()->id !== $annonce->user_id) {
            $annonce->increment('views');
        }

        $isFavorite = false;
        if ($request->user()) {
            $isFavorite = Favorite::where('user_id', $request->user()->id)
                ->where('annonce_id', $annonce->id)
                ->exists();
        }

        return response()->json($this->formatAnnonce($annonce, $isFavorite));
    }

    /**
     * Créer une annonce
     * POST /api/annonces
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'titre'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'prix'          => 'required|integer|min:0',
            'marque'        => 'required|string|max:100',
            'modele'        => 'nullable|string|max:100',
            'annee'         => 'nullable|integer|min:1980|max:' . (date('Y') + 1),
            'kilometrage'   => 'nullable|integer|min:0',
            'carburant'     => 'required|string|max:50',
            'boite_vitesse' => 'required|string|max:50',
            'ville'         => 'nullable|string|max:100',
            'vehicle_type'  => 'nullable|string|max:50',
            'show_phone'    => 'nullable',
            'couleur'       => 'nullable|string|max:50',
            'document_type' => 'nullable|in:carte_grise,procuration',
            'finition'      => 'nullable|string|max:80',
            'condition'     => 'required|in:oui,non',
            'images.*'      => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ], [
            'marque.required' => 'La marque est obligatoire.',
            'titre.required' => 'Le titre est obligatoire.',
            'prix.required' => 'Le prix est obligatoire.',
            'carburant.required' => 'Le type de carburant est obligatoire.',
            'boite_vitesse.required' => 'La boîte de vitesses est obligatoire.',
            'condition.required' => 'Veuillez indiquer si le véhicule est neuf.',
        ]);

        $data['show_phone'] = $request->boolean('show_phone');
        $data['condition'] = $request->input('condition', 'non');
        $data['vehicle_type'] = $request->input('vehicle_type', 'car'); // Voiture par défaut
        
        // Upload images
        $imagePaths = [
            'image_path'   => null,
            'image_path_2' => null,
            'image_path_3' => null,
            'image_path_4' => null,
            'image_path_5' => null,
        ];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                if ($index >= 5) break;
                
                $path = $file->store('annonces', 'public');
                
                if ($index === 0) $imagePaths['image_path']   = $path;
                if ($index === 1) $imagePaths['image_path_2'] = $path;
                if ($index === 2) $imagePaths['image_path_3'] = $path;
                if ($index === 3) $imagePaths['image_path_4'] = $path;
                if ($index === 4) $imagePaths['image_path_5'] = $path;
            }
        }

        $data = array_merge($data, $imagePaths);
        $data['user_id'] = $request->user()->id;
        $data['is_active'] = true; // Activer automatiquement les annonces de l'app

        $annonce = Annonce::create($data);

        return response()->json([
            'message' => 'Annonce créée avec succès. Elle sera visible après validation.',
            'annonce' => $this->formatAnnonce($annonce, false),
        ], 201);
    }

    /**
     * Mes annonces
     * GET /api/my-annonces
     */
    public function myAnnonces(Request $request)
    {
        $annonces = Annonce::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $annonces->map(function($annonce) {
                return $this->formatAnnonce($annonce, false);
            }),
        ]);
    }

    /**
     * Supprimer une annonce
     * DELETE /api/annonces/{id}
     */
    public function destroy(Request $request, $id)
    {
        $annonce = Annonce::findOrFail($id);

        // Check ownership
        if ($annonce->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à supprimer cette annonce.',
            ], 403);
        }

        // Delete images from storage
        $imageFields = ['image_path', 'image_path_2', 'image_path_3', 'image_path_4', 'image_path_5'];
        foreach ($imageFields as $field) {
            if ($annonce->$field) {
                Storage::disk('public')->delete($annonce->$field);
            }
        }

        $annonce->delete();

        return response()->json([
            'message' => 'Annonce supprimée avec succès',
        ]);
    }

    /**
     * Format annonce for API response
     */
    private function formatAnnonce($annonce, $isFavorite = false)
    {
        $images = [];
        $imageFields = ['image_path', 'image_path_2', 'image_path_3', 'image_path_4', 'image_path_5'];
        
        foreach ($imageFields as $field) {
            if ($annonce->$field) {
                $images[] = url('storage/' . $annonce->$field);
            }
        }

        return [
            'id' => $annonce->id,
            'title' => $annonce->titre,
            'description' => $annonce->description,
            'price' => (int) $annonce->prix,
            'marque' => $annonce->marque,
            'modele' => $annonce->modele,
            'year' => $annonce->annee,
            'km' => $annonce->kilometrage,
            'fuel' => $annonce->carburant,
            'gearbox' => $annonce->boite_vitesse,
            'wilaya' => $annonce->ville,
            'isNew' => $annonce->condition === 'neuf',
            'color' => $annonce->couleur,
            'documentType' => $annonce->document_type,
            'finition' => $annonce->finition,
            'images' => $images,
            'views' => $annonce->views,
            'createdAt' => $annonce->created_at->toIso8601String(),
            'isFavorite' => $isFavorite,
            'isActive' => $annonce->is_active,
            'user' => [
                'id' => $annonce->user->id,
                'name' => $annonce->user->name,
                'phone' => $annonce->show_phone ? $annonce->user->phone : null,
                'avatar' => $annonce->user->avatar ? url('storage/' . $annonce->user->avatar) : null,
            ],
        ];
    }
}
