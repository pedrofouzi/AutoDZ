# Copilot Instructions for Autodz

Autodz is a Laravel 12 classifieds platform for buying/selling vehicles in Algeria. This document guides AI agents on key patterns, architecture, and workflows based on actual codebase analysis.

## Architecture Overview

**Dual Stack:**
- **Web (Blade):** Public-facing listings, search, messaging (Tailwind CSS + Alpine.js)
- **API (REST):** Flutter mobile app via Laravel Sanctum token authentication

**Core Models & Relationships:**
- `Annonce` - Vehicle listing with 5 fixed image slots (`image_path` to `image_path_5`), always `is_active=false` on creation (awaits admin approval)
- `User` - Seller/buyer (fields: `name`, `email`, `password`, `phone`, `google_id`, `avatar`, `is_admin`, `is_banned`)
- `Conversation`, `Message` - Direct messaging (`buyer_id`, `seller_id`, `last_message_at`)
- `Favorite` - Simple join: `user_id`, `annonce_id`
- `CarBrand`, `CarModel` - Lookups via string relationships (NOT foreign keys)
- `AnnonceDeletion` - Tracks deleted ads with `was_sold` flag

**Storage:** Images in `storage/app/public/annonces/` via `ProcessAnnonceImages` job (optimizes to 1280px, watermark 45% opacity ~18% width, encodes JPG 70% quality)

## Key Workflows

### Creating an Annonce (Web)
1. `GET /annonces/create` → calls `cleanTempImages()` to clear session-stored temp files
2. User fills form (titre, prix, marque, modele, vehicle_type, carburant, boite_vitesse required + condition: oui/non)
3. Client-side validation: checks required fields, highlights errors in red, scrolls to first error
4. `POST /annonces` → `AnnonceController::store()`:
   - Validates all data, saves annonce with `is_active=false` (pending admin approval)
   - **Image upload is sync**, files stored directly to `storage/app/public/annonces/UUID.jpg`
   - **Image processing is async**: `ProcessAnnonceImages::dispatch($uploadedFiles)->afterResponse()` (resizes, watermarks, re-encodes 70% quality)
   - Redirects to `annonces.show` with success message
5. Admin approves via `PATCH /admin/annonces/{id}/toggle` to set `is_active=true`

### Editing an Annonce
- `GET /annonces/{id}/edit` → checks ownership or admin, shows 5 image slots with delete checkboxes
- `PUT /annonces/{id}` → can delete existing images (`delete_images[image_path]=1`), add up to 5 total
- Images are processed inline (not async) with watermark cloned once for performance

### Viewing an Annonce
- `GET /annonces/{id}` (public)
- Increments `views` counter (once per session, unless owner/admin)
- Shows phone only if `show_phone=true`
- Collects images from 5 slots, filters nulls, maps to asset URLs, fallback to placeholder

### Search & Filtering
- `GET /recherche?marque=Renault&price_max=2000000&carburant=Diesel` 
- Uses `Annonce::filter($request->only([...]))` scope for reusable filtering
- Supports: marque, modele, price_max, annee_min/max, km_min/max, carburant, wilaya, vehicle_type
- Returns paginated results (15 per page) with query string preserved

### Dynamic Brand-to-Model Selection
```javascript
// When brand selected in form:
fetch(`/api/models?brand=${encodeURIComponent(brand)}`)
  .then(r => r.json())
  .then(models => { /* populate modele select */ })
```
- Endpoint: `GET /api/models?brand=Renault` returns `["Clio", "Megane", ...]`
- Uses `CarModel::whereHas('brand')` to find models by brand name

### Mobile API (Sanctum)
- **Auth:** `POST /api/register`, `POST /api/login` returns `token` + user data
- **Listings:** `GET /api/annonces?marque=X&sort=price_asc` (paginated, filters same as web)
- **Detail:** `GET /api/annonces/{id}` returns formatted annonce + isFavorite flag
- **Create:** `POST /api/annonces` (multipart, store images in job)
- **My ads:** `GET /api/my-annonces` (auth required)
- **View count:** `POST /api/annonces/{id}/view` increments counter
- **Favorites:** `POST /api/favoris/toggle` (auth), `GET /api/favoris` (auth)
- **Messages:** `POST /api/messages`, `GET /api/conversations/{id}`, `POST /api/conversations/{id}/read`

---

## Data Validations & Rules

### Annonce Creation (`AnnonceController::store()`)
```php
'titre'         => 'required|string|max:255',
'description'   => 'nullable|string',
'prix'          => 'required|integer|min:0',
'marque'        => 'required|string|max:100',
'modele'        => 'nullable|string|max:100',
'annee'         => 'nullable|integer|min:1980|max:' . (date('Y') + 1),
'kilometrage'   => 'nullable|integer|min:0',
'carburant'     => 'required|string|max:50',          // One of: Essence, Diesel, Hybride, Électrique
'boite_vitesse' => 'required|string|max:50',         // One of: Manuelle, Automatique
'ville'         => 'nullable|string|max:100',
'vehicle_type'  => 'required|string|max:50',         // One of: Voiture, Utilitaire, Moto
'condition'     => 'required|in:oui,non',            // Is it new?
'show_phone'    => 'nullable|boolean',
'couleur'       => 'nullable|string|max:50',
'document_type' => 'nullable|in:carte_grise,procuration',
'finition'      => 'nullable|string|max:80',
'images'        => 'nullable|array|max:5',
'images.*'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',  // 4MB max per file
```

**Custom error messages in French are defined in `messages` param of validate().**

### Update Validations
Same as above, plus:
- `delete_images` array: `{image_path: '1', image_path_2: '0'}` to delete specific slots
- Max 5 images total (counts existing + new)

---

## Model Relationships & Scopes

### Annonce Model
```php
public function user()              // belongsTo User
public function marque()            // belongsTo CarBrand, foreign key: 'marque', owner key: 'name'
public function modele()            // belongsTo CarModel, foreign key: 'modele', owner key: 'name'
public function conversations()     // hasMany Conversation
public function favorites()         // hasMany Favorite

public function scopeFilter($query, array $filters)  // Filters: marque, modele, price_max, annee_min/max, km_min/max, carburant, wilaya, vehicle_type
```

### User Model
```php
public function conversationsAsBuyer()    // hasMany Conversation, foreign key: 'buyer_id'
public function conversationsAsSeller()   // hasMany Conversation, foreign key: 'seller_id'
public function favorites()               // hasMany Favorite
public function favoriteAnnonces()        // belongsToMany Annonce via 'favorites' table
public function annonces()                // hasMany Annonce
```

### Message Model
```php
public function conversation()    // belongsTo Conversation
public function sender()          // belongsTo User, foreign key: 'sender_id'
```

### Conversation Model
```php
public function annonce()    // belongsTo Annonce
public function buyer()      // belongsTo User, foreign key: 'buyer_id'
public function seller()     // belongsTo User, foreign key: 'seller_id'
public function messages()   // hasMany Message (ordered by created_at)
```

### CarBrand / CarModel
- `CarBrand::models()` → hasMany CarModel (via `car_brand_id` FK)
- `CarModel::brand()` → belongsTo CarBrand

---

## Authentication & Authorization

### Session-Based (Web)
- Routes use `auth` middleware (check logged in) + `verified` (email verified) + custom `banned` middleware
- Breeze scaffolding for register/login/password reset
- Google OAuth: `GET /auth/google` → callback → creates/updates user with `google_id`

### Token-Based (API - Sanctum)
- `POST /api/login` checks credentials, revokes old tokens, creates new plaintext token
- Token returned in response: `Bearer {token}`
- Protected routes use `auth:sanctum` middleware
- **Ban check:** `if ($user->is_banned) return 403`

### Admin Check
- Middleware: `AdminMiddleware` checks `auth()->user()->is_admin`, aborts 403 if false
- Applied to `/admin/*` routes
- Admin can toggle user status, approve/reject annonces, view stats

### Ban Check
- Middleware: `EnsureUserNotBanned` (alias: `banned`)
- If `is_banned=true`, logs out user + redirects to login with error message
- Applied to all auth routes that create/edit content

---

## Routes Structure

### Web Routes (`routes/web.php`)
```
GET  /                              → index (home, 6 latest ads, top deals, popular brands)
GET  /recherche                     → search (filterable)
GET  /annonces/{id}                 → show (public, increments views)
GET  /annonces/create               → create form
POST /annonces                      → store
GET  /annonces/{id}/edit            → edit form
PUT  /annonces/{id}                 → update
DELETE /annonces/{id}               → destroy
GET  /mes-annonces                  → myAds (owner's listings)
GET  /api/models?brand=X            → getModels (returns model names for brand)

GET  /favoris                       → FavoriteController::index (auth)
POST /annonces/{id}/favorite        → toggle favorite (auth)

GET  /messages                      → MessageController::index (auth)
GET  /messages/{conversation}       → show (auth)
POST /messages/{conversation}       → store (auth)
GET  /messages/{conversation}/new   → fetchNew (polling, auth)
POST /annonces/{id}/messages        → start conversation (auth)

GET  /vendeur/{user}                → SellerController::show (public, seller profile + 4 recent ads)

ADMIN ROUTES (prefix: /admin, middleware: auth,admin):
GET  /admin/                        → dashboard
GET  /admin/annonces                → index (all, with status toggle)
PATCH /admin/annonces/{id}/toggle   → toggle is_active
DELETE /admin/annonces/{id}         → destroy
POST /admin/annonces/bulk-action    → bulk toggle/delete
GET  /admin/users                   → index (with admin/ban toggles)
PATCH /admin/users/{id}/toggle-admin
PATCH /admin/users/{id}/toggle-ban
GET  /admin/stats                   → view stats
```

### API Routes (`routes/api.php`)
```
POST   /api/register                → AuthController::register
POST   /api/login                   → AuthController::login
GET    /api/annonces                → AnnonceApiController::index (paginated, filtered)
GET    /api/annonces/{id}           → show (with isFavorite)
GET    /api/users/{id}/annonces     → userAnnonces (seller's active ads)
POST   /api/annonces/{id}/view      → incrementView
(auth:sanctum)
POST   /api/logout                  → logout
GET    /api/user                    → current user
POST   /api/change-password         → changePassword
POST   /api/annonces                → store (multipart)
PUT    /api/annonces/{id}           → update
POST   /api/annonces/{id}           → update (support _method=PUT)
GET    /api/my-annonces             → myAnnonces
DELETE /api/annonces/{id}           → destroy
POST   /api/favoris/toggle          → FavoriteApiController::toggle
GET    /api/favoris                 → index (user's favorites)
GET    /api/conversations           → MessageApiController::index
GET    /api/conversations/{id}      → show
POST   /api/conversations/{id}/read → markAsRead
POST   /api/messages                → store
```

---

## Important Code Patterns

### Image Processing
**Web (sync in edit, async in create):**
```php
// In store():
ProcessAnnonceImages::dispatch($uploadedFiles)->afterResponse();  // Async, doesn't block response

// In update():
$watermarkBase = null;
$watermarkPath = public_path('watermark.png');
if (file_exists($watermarkPath)) {
    $watermarkBase = Image::make($watermarkPath)->opacity(45);
}
// Load once, clone for each image (performance)
foreach ($request->file('images') as $file) {
    $image = Image::make($file->getRealPath())->orientate();
    $image->resize(1280, null, function ($c) { $c->aspectRatio(); $c->upsize(); });
    if ($watermarkBase) {
        $wm = clone $watermarkBase;  // ← clone, don't recreate
        $wm->resize((int) ($image->width() * 0.18), null, function ($c) { $c->aspectRatio(); });
        $image->insert($wm, 'center');
    }
    Storage::disk('public')->put($filename, (string) $image->encode('jpg', 70));
}
```

### View Increment (Session-Based)
```php
if (!$isOwner && !$isAdmin) {
    $key = 'viewed_annonce_' . $annonce->id;
    if (!session()->has($key)) {
        $annonce->increment('views');
        session()->put($key, true);  // Mark as viewed in this session
    }
}
```

### Filtering Scopes
```php
// In view:
$filteredQuery = (clone $baseQuery)->filter($request->only(['marque', 'price_max', ...]));

// In model:
public function scopeFilter($query, array $filters) {
    if (!empty($filters['marque'])) $query->where('marque', 'like', '%' . $filters['marque'] . '%');
    return $query;
}
```

### Client-Side Validation (JavaScript)
```javascript
function validateForm() {
    const errors = [];
    const titre = document.querySelector('input[name="titre"]');
    if (!titre.value.trim()) {
        errors.push('Le titre est obligatoire.');
        titre.classList.add('border-red-500');
    }
    // ... more validations
    return { errors, errorFields };
}

// On submit, if errors, prevent default + scroll to first error field
```

### API Response Format
```json
{
  "data": [
    {
      "id": 1,
      "titre": "Renault Clio 2018",
      "prix": 1500000,
      "marque": "Renault",
      "modele": "Clio",
      "annee": 2018,
      "kilometrage": 50000,
      "carburant": "Essence",
      "boite_vitesse": "Manuelle",
      "ville": "Alger",
      "vehicle_type": "Voiture",
      "condition": "non",
      "couleur": "Blanc",
      "images": ["url1", "url2"],
      "views": 120,
      "show_phone": true,
      "user": {
        "id": 5,
        "name": "Ahmed",
        "email": "ahmed@example.com",
        "phone": "0555123456",
        "avatar": null
      },
      "isFavorite": false,
      "created_at": "2025-12-23T10:00:00Z"
    }
  ],
  "current_page": 1,
  "last_page": 10,
  "per_page": 20,
  "total": 150
}
```

---

## Testing & Development

### Running Tests
```bash
php artisan test                                    # All tests
php artisan test tests/Feature/AnnonceTest.php      # Single test file
php artisan test --filter=testCreateAnnonce         # Single test method
```

### Development Commands
```bash
composer setup          # One-time: install deps, generate key, migrate, npm install/build
npm run dev             # Vite watch (CSS/JS)
php artisan serve       # Serve at localhost:8000
php artisan queue:listen --tries=1    # Process jobs (sync driver in dev)
composer dev            # Run all concurrently: server, queue, logs, vite
```

### Debugging
- **Logs:** `storage/logs/laravel.log`
- **Tinker:** `php artisan tinker` → explore models, test queries
- **Queue:** If images not processing, check `config/queue.php` (default: sync in dev, executes immediately)

---

## Common Conventions & Patterns

### Naming
- **French table/column names:** `marque` (brand), `modele` (model), `boite_vitesse` (gearbox), `kilometrage` (mileage), `ville` (city/wilaya)
- **String relationships:** `Annonce::marque()` uses string FK (`marque` column) + string PK (`name` on CarBrand), NOT numeric ID
- **Boolean fields:** `is_active`, `is_admin`, `is_banned`, `show_phone` cast to boolean

### Status Flow
1. Annonce created → `is_active=false` (awaiting admin approval)
2. Admin toggles → `is_active=true` (now visible on homepage, search, API)
3. Admin toggles → `is_active=false` (hidden but not deleted)
4. Owner deletes → record deleted + tracked in `AnnonceDeletion` table

### Image Slots
- Always 5 slots: `image_path`, `image_path_2`, `image_path_3`, `image_path_4`, `image_path_5`
- Nullable columns (some may be null)
- Filter out nulls when rendering: `.filter().values()`

### Error Handling
- **Web:** `@error('field')` Blade directive displays validation errors inline
- **API:** `response()->json(['message' => '...'], 400)` or via `ValidationException`
- **HTTP codes:** 404 (not found), 403 (forbidden/not owner), 401 (unauthenticated)

---

## When Adding Features

### New Endpoint
1. Create route in `routes/api.php` or `routes/web.php`
2. Create/update controller in `app/Http/Controllers/` or subdirectory
3. Add validation in controller (or FormRequest class)
4. Add tests in `tests/Feature/`

### New Model Field
1. Create migration: `php artisan make:migration add_field_to_table`
2. Update model `$fillable` array
3. Add to form validation rules
4. Add to Blade form view (if user-facing)
5. Add to API response formatter

### Image Handling
- Store file: `$file->store('annonces', 'public')`
- Process async: `ProcessAnnonceImages::dispatch($paths)->afterResponse()`
- Use `Storage::disk('public')->delete()` to remove
- Always clean up on annonce delete

### Messaging Feature
- Follow `Conversation` + `Message` pattern
- Eager load relations: `Conversation::with(['messages.sender', 'buyer', 'seller'])`
- Mark as read: `Message::whereNull('read_at')->update(['read_at' => now()])`
- Fetch new: filter by `conversation_id` + `created_at > last_fetched`

### Admin Tasks
- Use `AdminMiddleware` for protection (`middleware: ['auth', 'admin']`)
- Check `is_admin` before bulk actions
- Log changes if audit trail needed
- Return success/error JSON responses

---

## Deployment Notes

- **Env:** `.env` should have `APP_ENV=production`, `APP_DEBUG=false`, `QUEUE_DRIVER=redis` (or similar)
- **Storage:** `php artisan storage:link` to expose `storage/app/public` as `public/storage`
- **Assets:** `npm run build` before deployment
- **DB:** Run migrations: `php artisan migrate --force`
- **Cache:** `php artisan cache:clear`, `php artisan config:cache`
