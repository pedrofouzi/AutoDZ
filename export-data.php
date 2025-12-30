<?php
/**
 * Script d'export des données SQLite vers JSON
 * Exécuter localement : php export-data.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔄 Export des données SQLite...\n\n";

// Export CarBrands
$brands = \App\Models\CarBrand::all()->toArray();
file_put_contents('export-brands.json', json_encode($brands, JSON_PRETTY_PRINT));
echo "✅ CarBrands exportés : " . count($brands) . " marques\n";

// Export CarModels
$models = \App\Models\CarModel::all()->toArray();
file_put_contents('export-models.json', json_encode($models, JSON_PRETTY_PRINT));
echo "✅ CarModels exportés : " . count($models) . " modèles\n";

// Export Users (admin seulement)
$users = \App\Models\User::where('is_admin', true)->get()->toArray();
file_put_contents('export-users.json', json_encode($users, JSON_PRETTY_PRINT));
echo "✅ Users exportés : " . count($users) . " admins\n";

echo "\n📦 Fichiers créés :\n";
echo "   - export-brands.json\n";
echo "   - export-models.json\n";
echo "   - export-users.json\n";
echo "\n🚀 Uploade ces fichiers sur le serveur Laravel Cloud\n";
