<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    /**
     * Rediriger vers Google pour l'authentification
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * Gérer le callback de Google après authentification
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            \Log::info('Google User Data:', [
                'id' => $googleUser->id,
                'email' => $googleUser->email,
                'name' => $googleUser->name,
            ]);
            
            // Chercher l'utilisateur par google_id ou email
            $user = User::where('google_id', $googleUser->id)
                        ->orWhere('email', $googleUser->email)
                        ->first();

            if ($user) {
                \Log::info('User found:', ['user_id' => $user->id]);
                
                // Si l'utilisateur existe mais n'a pas de google_id, on l'ajoute
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->id,
                        'avatar' => $googleUser->avatar,
                    ]);
                }
            } else {
                \Log::info('Creating new user');
                
                // Créer un nouveau compte utilisateur
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'password' => Hash::make(Str::random(24)), // Mot de passe aléatoire
                    'email_verified_at' => now(), // Email déjà vérifié par Google
                ]);
            }

            // Connecter l'utilisateur
            Auth::login($user, true);
            
            \Log::info('User logged in:', ['user_id' => $user->id, 'auth_check' => Auth::check()]);

            return redirect()->intended(route('home'))->with('success', 'Connexion réussie avec Google !');

        } catch (\Exception $e) {
            \Log::error('Google OAuth Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->route('login')->with('error', 'Erreur lors de la connexion avec Google : ' . $e->getMessage());
        }
    }
}
