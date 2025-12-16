@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 md:py-8">
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold mb-1">Gérer mon profil</h1>
        <p class="text-xs md:text-sm text-gray-500">Mettez à jour vos informations de compte et sécurité.</p>
    </div>

    @if (session('status') === 'profile-updated')
        <div class="mb-4 text-sm bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3">
            Vos informations ont été mises à jour.
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow p-4 md:p-6">
            <h2 class="text-sm md:text-base font-semibold mb-3">Informations du profil</h2>
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-4 md:p-6">
            <h2 class="text-sm md:text-base font-semibold mb-3">Sécurité / Mot de passe</h2>
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl shadow p-4 md:p-6">
            <h2 class="text-sm md:text-base font-semibold mb-3">Suppression du compte</h2>
            <p class="text-xs md:text-sm text-gray-500 mb-3">Cette action est définitive. Vos annonces et données seront supprimées.</p>
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</div>
@endsection
