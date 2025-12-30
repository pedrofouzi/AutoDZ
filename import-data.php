<?php
/**
 * Script d'import des données JSON vers MySQL
 * Exécuter sur le serveur : php import-data.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔄 Import des données vers MySQL...\n\n";

// Import CarBrands
if (file_exists('export-brands.json')) {
    $brands = json_decode(file_get_contents('export-brands.json'), true);
    foreach ($brands as $brand) {
        \App\Models\CarBrand::updateOrCreate(
            ['id' => $brand['id']],
            $brand
        );
    }
    echo "✅ CarBrands importés : " . count($brands) . " marques\n";
} else {
    echo "⚠️  Fichier export-brands.json introuvable\n";
}

// Import CarModels
if (file_exists('export-models.json')) {
    $models = json_decode(file_get_contents('export-models.json'), true);
    foreach ($models as $model) {
        \App\Models\CarModel::updateOrCreate(
            ['id' => $model['id']],
            $model
        );
    }
    echo "✅ CarModels importés : " . count($models) . " modèles\n";
} else {
    echo "⚠️  Fichier export-models.json introuvable\n";
}

// Import Users
if (file_exists('export-users.json')) {
    $users = json_decode(file_get_contents('export-users.json'), true);
    foreach ($users as $user) {
        \App\Models\User::updateOrCreate(
            ['id' => $user['id']],
            $user
        );
    }
    echo "✅ Users importés : " . count($users) . " admins\n";
} else {
    echo "⚠️  Fichier export-users.json introuvable\n";
}

echo "\n🎉 Import terminé !\n";
