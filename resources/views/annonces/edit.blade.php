@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 md:py-8">
    {{-- Titre page --}}
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold mb-1">
            Modifier mon annonce
        </h1>
        <p class="text-xs md:text-sm text-gray-500">
            Mettez à jour les informations de votre véhicule.
        </p>
    </div>

    {{-- Messages --}}
    @if (session('success'))
        <div class="mb-4 text-sm bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 text-sm bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3">
            <p class="font-semibold mb-1">Veuillez corriger les erreurs suivantes :</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FORMULAIRE --}}
        <form id="edit-annonce-form"
            method="POST"
            action="{{ route('annonces.update', $annonce) }}"
            enctype="multipart/form-data"
            class="bg-white rounded-2xl shadow p-4 md:p-6 space-y-6">
        @csrf
        @method('PUT')

        {{-- ...existing code... --}}

        {{-- Type véhicule --}}
        <div>
            <label class="block text-xs font-semibold mb-2">Type de véhicule</label>
            <input type="hidden" name="vehicle_type" id="vehicle_type_input" value="{{ old('vehicle_type', $annonce->vehicle_type ?? 'car') }}">
            <div class="flex flex-wrap gap-2 text-xs">
                @php
                    $types = ['car' => 'Voiture', 'van' => 'Utilitaire', 'moto' => 'Moto'];
                    $currentType = old('vehicle_type', $annonce->vehicle_type ?? 'car');
                @endphp
                @foreach($types as $value => $label)
                    <button type="button"
                            data-type="{{ $value }}"
                            class="vehicle-type-btn-create px-3 py-1.5 rounded-full border
                                {{ $currentType === $value ? 'bg-pink-600 text-white border-pink-600' : 'bg-white text-gray-700 border-gray-200' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Véhicule neuf ? --}}
        <div>
            <label class="block text-xs font-semibold mb-2">Véhicule neuf ? <span class="text-red-500">*</span></label>
            <div class="flex gap-4 text-xs md:text-sm">
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="condition" value="non"
                           {{ old('condition', $annonce->condition) === 'non' ? 'checked' : '' }}>
                    Non
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="condition" value="oui"
                           {{ old('condition', $annonce->condition) === 'oui' ? 'checked' : '' }}>
                    Oui
                </label>
            </div>
        </div>

        {{-- Titre + Prix --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold mb-1">Titre de l'annonce <span class="text-red-500">*</span></label>
                <input type="text"
                       name="titre"
                       value="{{ old('titre', $annonce->titre) }}"
                       class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
                       placeholder="ex : Renault Clio 1.5 DCI 2018 très bon état">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Prix (DA) <span class="text-red-500">*</span></label>
                <input type="number"
                       name="prix"
                       value="{{ old('prix', $annonce->prix) }}"
                       class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
                       placeholder="ex : 2500000">
            </div>
        </div>


        {{-- Marque / Modèle / Ville --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1">Marque <span class="text-red-500">*</span></label>
                <select name="marque" id="marque_select" class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm">
                    <option value="">Sélectionnez une marque</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->name }}" {{ old('marque', $annonce->marque) === $brand->name ? 'selected' : '' }}>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Modèle</label>
                <select name="modele" id="modele_select" class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm">
                    <option value="">Sélectionnez d'abord une marque</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Ville / Wilaya</label>
                <input type="text" name="ville" value="{{ old('ville', $annonce->ville) }}" class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm" placeholder="ex : Alger">
            </div>
        </div>

        {{-- Année / km / carburant / boite --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1">Année</label>
                <input type="number" name="annee" value="{{ old('annee', $annonce->annee) }}" class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm" placeholder="ex : 2018">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Kilométrage (km)</label>
                <input type="number" name="kilometrage" value="{{ old('kilometrage', $annonce->kilometrage) }}" class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm" placeholder="ex : 120000">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Carburant</label>
                <select name="carburant" class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm">
                    <option value="">Sélectionnez</option>
                    @foreach(['Essence','Diesel','Hybride','Électrique'] as $fuel)
                        <option value="{{ $fuel }}" {{ old('carburant', $annonce->carburant) === $fuel ? 'selected' : '' }}>{{ $fuel }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Boîte de vitesses</label>
                <select name="boite_vitesse" class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm">
                    <option value="">Sélectionnez</option>
                    @foreach(['Manuelle','Automatique'] as $gear)
                        <option value="{{ $gear }}" {{ old('boite_vitesse', $annonce->boite_vitesse) === $gear ? 'selected' : '' }}>{{ $gear }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Détails --}}
        <div>
            <h2 class="text-sm md:text-base font-bold mb-3">Détails</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold mb-1">Couleur</label>
                    <select name="couleur" class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm">
                        <option value="">— Choisir —</option>
                        <option value="Blanc" {{ old('couleur', $annonce->couleur) === 'Blanc' ? 'selected' : '' }}>Blanc</option>
                        <option value="Noir" {{ old('couleur', $annonce->couleur) === 'Noir' ? 'selected' : '' }}>Noir</option>
                        <option value="Gris" {{ old('couleur', $annonce->couleur) === 'Gris' ? 'selected' : '' }}>Gris</option>
                        <option value="Argent" {{ old('couleur', $annonce->couleur) === 'Argent' ? 'selected' : '' }}>Argent</option>
                        <option value="Bleu" {{ old('couleur', $annonce->couleur) === 'Bleu' ? 'selected' : '' }}>Bleu</option>
                        <option value="Rouge" {{ old('couleur', $annonce->couleur) === 'Rouge' ? 'selected' : '' }}>Rouge</option>
                        <option value="Vert" {{ old('couleur', $annonce->couleur) === 'Vert' ? 'selected' : '' }}>Vert</option>
                        <option value="Beige" {{ old('couleur', $annonce->couleur) === 'Beige' ? 'selected' : '' }}>Beige</option>
                        <option value="Orange" {{ old('couleur', $annonce->couleur) === 'Orange' ? 'selected' : '' }}>Orange</option>
                        <option value="Marron" {{ old('couleur', $annonce->couleur) === 'Marron' ? 'selected' : '' }}>Marron</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Document</label>
                    <select name="document_type" class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm">
                        <option value="">— Choisir —</option>
                        <option value="carte_grise" {{ old('document_type', $annonce->document_type ?? '') === 'carte_grise' ? 'selected' : '' }}>Carte grise</option>
                        <option value="procuration" {{ old('document_type', $annonce->document_type ?? '') === 'procuration' ? 'selected' : '' }}>Procuration</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Finition</label>
                    <input type="text" name="finition" value="{{ old('finition', $annonce->finition ?? '') }}" class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm" placeholder="Ex : Allure, GT Line, Titanium">
                </div>
            </div>
        </div>

        {{-- Année / Kilométrage / Carburant / Boîte --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1">Année</label>
                <input type="number"
                       name="annee"
                       value="{{ old('annee', $annonce->annee) }}"
                       class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Kilométrage (km)</label>
                <input type="number"
                       name="kilometrage"
                       value="{{ old('kilometrage', $annonce->kilometrage) }}"
                       class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Carburant</label>
                <select name="carburant"
                        class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    <option value="">Sélectionnez</option>
                    @foreach(['Essence','Diesel','Hybride','Électrique'] as $fuel)
                        <option value="{{ $fuel }}"
                            @selected(old('carburant', $annonce->carburant) === $fuel)>
                            {{ $fuel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Boîte de vitesses</label>
                <select name="boite_vitesse"
                        class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    <option value="">Sélectionnez</option>
                    @foreach(['Manuelle','Automatique'] as $gear)
                        <option value="{{ $gear }}"
                            @selected(old('boite_vitesse', $annonce->boite_vitesse) === $gear)>
                            {{ $gear }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Type de véhicule --}}
        <div>
            <label class="block text-xs font-semibold mb-1">Type de véhicule</label>
            <input type="text"
                   name="vehicle_type"
                   value="{{ old('vehicle_type', $annonce->vehicle_type) }}"
                   class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
        </div>

        {{-- Affichage téléphone --}}
        <div class="border-t border-gray-100 pt-4">
            <p class="text-xs font-semibold mb-2">{Contact et affichage num}</p>
            <label class="inline-flex items-start gap-2 text-xs md:text-sm text-gray-700">
                <input type="checkbox"
                       name="show_phone"
                       value="1"
                       class="mt-0.5 rounded border-gray-300 text-pink-600 focus:ring-pink-500"
                       @checked(old('show_phone', $annonce->show_phone))>
                <span>
                    Afficher mon numéro de téléphone sur l’annonce
                    <span class="block text-[11px] text-gray-400">
                        Si vous décochez, les acheteurs pourront uniquement vous envoyer des messages via la messagerie interne.
                    </span>
                </span>
            </label>
        </div>

        {{-- Description --}}
        <div>
            <label class="block text-xs font-semibold mb-1">Description</label>
            <textarea name="description"
                      rows="5"
                      class="w-full border rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500">{{ old('description', $annonce->description) }}</textarea>
        </div>


        {{-- Ajouter des photos --}}
        <div>
            <label class="block text-xs font-semibold mb-1">
                Photos actuelles
            </label>
            @if($annonce->image_path || $annonce->image_path_2 || $annonce->image_path_3 || $annonce->image_path_4)
                <div class="grid grid-cols-2 md:grid-cols-5 gap-2 mb-4">
                    @if($annonce->image_path)
                        <div class="relative annonce-image-block">
                            <img src="{{ asset('storage/' . $annonce->image_path) }}" alt="Photo 1" class="w-full h-20 object-cover rounded-lg border border-gray-200">
                            <button type="button" class="absolute top-1 right-1 bg-white bg-opacity-80 rounded-full p-1 text-red-600 hover:bg-red-100 js-delete-image" data-image="image_path" title="Supprimer cette image" style="line-height:1;">
                                <span class="text-lg font-bold">&times;</span>
                            </button>
                            <input type="hidden" name="delete_images[]" value="" class="delete-image-hidden">
                        </div>
                    @endif
                    @if($annonce->image_path_2)
                        <div class="relative annonce-image-block">
                            <img src="{{ asset('storage/' . $annonce->image_path_2) }}" alt="Photo 2" class="w-full h-20 object-cover rounded-lg border border-gray-200">
                            <button type="button" class="absolute top-1 right-1 bg-white bg-opacity-80 rounded-full p-1 text-red-600 hover:bg-red-100 js-delete-image" data-image="image_path_2" title="Supprimer cette image" style="line-height:1;">
                                <span class="text-lg font-bold">&times;</span>
                            </button>
                            <input type="hidden" name="delete_images[]" value="" class="delete-image-hidden">
                        </div>
                    @endif
                    @if($annonce->image_path_3)
                        <div class="relative annonce-image-block">
                            <img src="{{ asset('storage/' . $annonce->image_path_3) }}" alt="Photo 3" class="w-full h-20 object-cover rounded-lg border border-gray-200">
                            <button type="button" class="absolute top-1 right-1 bg-white bg-opacity-80 rounded-full p-1 text-red-600 hover:bg-red-100 js-delete-image" data-image="image_path_3" title="Supprimer cette image" style="line-height:1;">
                                <span class="text-lg font-bold">&times;</span>
                            </button>
                            <input type="hidden" name="delete_images[]" value="" class="delete-image-hidden">
                        </div>
                    @endif
                    @if($annonce->image_path_4)
                        <div class="relative annonce-image-block">
                            <img src="{{ asset('storage/' . $annonce->image_path_4) }}" alt="Photo 4" class="w-full h-20 object-cover rounded-lg border border-gray-200">
                            <button type="button" class="absolute top-1 right-1 bg-white bg-opacity-80 rounded-full p-1 text-red-600 hover:bg-red-100 js-delete-image" data-image="image_path_4" title="Supprimer cette image" style="line-height:1;">
                                <span class="text-lg font-bold">&times;</span>
                            </button>
                            <input type="hidden" name="delete_images[]" value="" class="delete-image-hidden">
                        </div>
                    @endif
                </div>
            @endif

            <label class="block text-xs font-semibold mb-1">
                Ajouter des photos <span class="text-gray-400">(jusqu'à 5 photos au total)</span>
            </label>
            <p class="text-[11px] text-gray-500 mb-2">
                Formats acceptés : JPG, JPEG, PNG, WEBP. Taille max : 4 Mo par photo.
            </p>

            <div id="images_container">
                <!-- Initial input -->
                <div class="image-input-group flex items-center gap-2 mb-2">
                    <input type="file"
                           name="images[]"
                           accept="image/*"
                           class="flex-1 text-xs md:text-sm text-gray-600
                                  file:mr-3 file:py-1 file:px-3
                                  file:rounded-lg file:border-0
                                  file:text-xs file:font-semibold
                                  file:bg-pink-50 file:text-pink-700
                                  hover:file:bg-pink-100">
                    <button type="button" class="remove-image-btn text-red-500 hover:text-red-700" style="display: none;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="button" id="add_image_btn"
                    class="mt-2 px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                + Ajouter une photo
            </button>

            <div id="images_preview" class="mt-3 grid grid-cols-2 md:grid-cols-5 gap-2"></div>
        </div>

        {{-- Actions --}}
        <div class="pt-2 flex flex-col md:flex-row gap-3 md:justify-end">
            <a href="{{ route('annonces.my') }}"
               class="inline-flex items-center justify-center px-4 py-2 rounded-full border border-gray-200 text-xs md:text-sm text-gray-600 hover:border-gray-300">
                Annuler
            </a>
            <button type="submit"
                    class="inline-flex items-center justify-center px-6 py-2 rounded-full bg-pink-600 text-white text-xs md:text-sm font-semibold hover:bg-pink-700">
                Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

<script>
    const imagesContainer = document.getElementById('images_container');
    const addImageBtn = document.getElementById('add_image_btn');
    const imagesPreview = document.getElementById('images_preview');

    let imageCount = 1;

    if (addImageBtn) {
        addImageBtn.addEventListener('click', () => {
            if (imageCount >= 5) {
                alert('Vous pouvez ajouter au maximum 5 photos.');
                return;
            }

            imageCount++;
            const newInputGroup = document.createElement('div');
            newInputGroup.className = 'image-input-group flex items-center gap-2 mb-2';
            newInputGroup.innerHTML = `
                <input type="file"
                       name="images[]"
                       accept="image/*"
                       class="flex-1 text-xs md:text-sm text-gray-600
                              file:mr-3 file:py-1 file:px-3
                              file:rounded-lg file:border-0
                              file:text-xs file:font-semibold
                              file:bg-pink-50 file:text-pink-700
                              hover:file:bg-pink-100">
                <button type="button" class="remove-image-btn text-red-500 hover:text-red-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            `;
            imagesContainer.appendChild(newInputGroup);
            updateRemoveButtons();
        });
    }

    function updateRemoveButtons() {
        const groups = imagesContainer.querySelectorAll('.image-input-group');
        groups.forEach((group, index) => {
            const removeBtn = group.querySelector('.remove-image-btn');
            if (groups.length > 1) {
                removeBtn.style.display = 'block';
            } else {
                removeBtn.style.display = 'none';
            }
        });
    }

    if (imagesContainer) {
        imagesContainer.addEventListener('click', (e) => {
            if (e.target.closest('.remove-image-btn')) {
                e.target.closest('.image-input-group').remove();
                imageCount--;
                updateRemoveButtons();
                updatePreview();
            }
        });

        imagesContainer.addEventListener('change', updatePreview);
    }

    function updatePreview() {
        if (!imagesPreview) return;
        imagesPreview.innerHTML = '';
        const inputs = imagesContainer.querySelectorAll('input[type="file"]');
        inputs.forEach((input, index) => {
            if (input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'w-full h-20 object-cover rounded-lg border border-gray-200';
                    imagesPreview.appendChild(img);
                };
                reader.readAsDataURL(input.files[0]);
            }
        });
    }

    // Dynamic models based on brand
    const marqueSelect = document.getElementById('marque_select');
    const modeleSelect = document.getElementById('modele_select');

    function loadModels(brand, selectedModel = '') {
        if (!brand) {
            modeleSelect.innerHTML = '<option value="">Sélectionnez d\'abord une marque</option>';
            return;
        }

        modeleSelect.innerHTML = '<option value="">Chargement...</option>';

        fetch(`/api/models?brand=${encodeURIComponent(brand)}`)
            .then(response => response.json())
            .then(models => {
                modeleSelect.innerHTML = '<option value="">Sélectionnez un modèle</option>';
                models.forEach(model => {
                    const option = document.createElement('option');
                    option.value = model;
                    option.textContent = model;
                    if (model === selectedModel) {
                        option.selected = true;
                    }
                    modeleSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Erreur lors du chargement des modèles:', error);
                modeleSelect.innerHTML = '<option value="">Erreur de chargement</option>';
            });
    }

    if (marqueSelect && modeleSelect) {
        // Load models on page load if marque is selected
        const currentMarque = marqueSelect.value;
        const currentModele = '{{ old('modele', $annonce->modele) }}';
        if (currentMarque) {
            loadModels(currentMarque, currentModele);
        }

        // Load models on change
        marqueSelect.addEventListener('change', function() {
            const brand = this.value;
            loadModels(brand);
        });
    }

    // Clean up empty image inputs before form submission
    const form = document.getElementById('edit-annonce-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const imageInputs = form.querySelectorAll('input[type="file"][name="images[]"]');
            const emptyInputs = [];
            imageInputs.forEach(input => {
                if (!input.files || input.files.length === 0) {
                    emptyInputs.push(input);
                }
            });
            emptyInputs.forEach(input => {
                input.remove();
            });
        });
    }
    // Gestion suppression images existantes (X rouge)
    document.querySelectorAll('.js-delete-image').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const block = btn.closest('.annonce-image-block');
            if (block) {
                // Marquer l'image pour suppression
                block.querySelector('.delete-image-hidden').value = btn.dataset.image;
                // Masquer visuellement le bloc
                block.style.display = 'none';
            }
        });
    });
</script>
@endsection
