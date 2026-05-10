<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Models\RfidTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Trim email to handle accidental spaces (e.g. "user@ gmail.com" or "user@gmail.com ")
        if ($request->has('email')) {
            $request->merge([
                'email' => str_replace(' ', '', $request->email)
            ]);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/u'],
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'nfc_id' => 'nullable|string|max:255',
            'billing_document' => 'sometimes|nullable|numeric|digits_between:5,15|unique:users',
            'billing_razon_social' => 'sometimes|nullable|string|max:255',
            'billing_doc_type' => 'sometimes|nullable|in:CI,NIT',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.regex' => 'El nombre solo debe contener letras y espacios.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un formato de correo válido (ej: usuario@correo.com).',
            'email.unique' => 'Este correo ya está registrado en el sistema.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña es muy corta, debe tener al menos 8 caracteres.',
            'billing_document.numeric' => 'El NIT/CI debe contener únicamente números.',
            'billing_document.digits_between' => 'El NIT/CI debe tener entre 5 y 15 dígitos.',
            'billing_document.unique' => 'Este NIT/CI ya está registrado por otro usuario.',
        ]);

        $result = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'billing_document' => $request->billing_document,
                'billing_razon_social' => $request->billing_razon_social,
                'billing_doc_type' => $request->billing_doc_type ?: 'NIT',
            ]);

            // Ensure mobile default role exists and assign it
            $role = Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
            $user->assignRole($role);

            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'currency' => 'BOB', 'is_postpaid' => false]
            );

            $rawTag = $request->input('nfc_id');
            if ($rawTag) {
                $tagCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $rawTag));
            } else {
                $tagCode = 'A' . strtoupper(Str::random(7));
            }

            // Guarantee uniqueness
            while (RfidTag::where('tag_code', $tagCode)->exists()) {
                $tagCode = 'A' . strtoupper(Str::random(7));
            }

            $virtualProduct = Product::where('internal_code', 'VIRTUAL-TAG')->first();

            $tag = RfidTag::create([
                'tag_code' => $tagCode,
                'user_id' => $user->id,
                'product_id' => $virtualProduct?->id,
                'name' => 'Tag Virtual App',
                'is_active' => true,
                'is_virtual' => true,
            ]);

            $token = $user->createToken('mobile-app')->plainTextToken;

            return compact('user', 'wallet', 'tag', 'token');
        });

        return response()->json([
            'message' => 'Usuario registrado exitosamente',
            'user' => $result['user'],
            'wallet' => $result['wallet'],
            'rfid_tag' => $result['tag'],
            'token' => $result['token'],
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function googleLogin(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        $idToken = $request->id_token;

        // Verify with Google
        $response = Http::get("https://oauth2.googleapis.com/tokeninfo?id_token={$idToken}");

        if ($response->failed()) {
            return response()->json(['message' => 'Token de Google inválido'], 401);
        }

        $payload = $response->json();

        // Optional: Verify audience (should match the Web Client ID configured in Firebase)
        // $clientId = config('services.google.client_id');
        // if ($payload['aud'] !== $clientId) { return response()->json(['message' => 'Unauthorized Client'], 401); }

        $email = $payload['email'];
        $name = $payload['name'] ?? 'Google User';
        $googleId = $payload['sub'];

        $user = User::where('email', $email)->first();

        if (!$user) {
            // Register new user
            $user = DB::transaction(function () use ($email, $name) {
                $u = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(16)), // Random password for social login
                ]);

                $role = Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
                $u->assignRole($role);

                Wallet::firstOrCreate(
                    ['user_id' => $u->id],
                    ['balance' => 0, 'currency' => 'BOB', 'is_postpaid' => false]
                );

                $tagCode = 'A' . strtoupper(Str::random(7));
                while (RfidTag::where('tag_code', $tagCode)->exists()) {
                    $tagCode = 'A' . strtoupper(Str::random(7));
                }

                RfidTag::create([
                    'tag_code' => $tagCode,
                    'user_id' => $u->id,
                    'name' => 'Mobile Tag',
                    'is_active' => true,
                ]);

                return $u;
            });
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $wallet = Wallet::where('user_id', $user->id)->first();
        $tag = RfidTag::where('user_id', $user->id)->latest('id')->first();

        return response()->json([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'wallet' => $wallet,
            'rfid_tag' => $tag,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/u'],
            'billing_document' => [
                'sometimes',
                'nullable',
                'numeric',
                'digits_between:5,15',
                'unique:users,billing_document,' . $user->id
            ],
            'billing_razon_social' => 'sometimes|nullable|string|max:255',
            'billing_doc_type' => 'sometimes|nullable|in:CI,NIT',
            'password' => 'sometimes|nullable|string|min:8|confirmed',
        ], [
            'name.regex' => 'El nombre solo debe contener letras y espacios.',
            'billing_document.numeric' => 'El NIT/CI debe contener únicamente números.',
            'billing_document.digits_between' => 'El NIT/CI debe tener entre 5 y 15 dígitos.',
            'billing_document.unique' => 'Este NIT/CI ya está registrado por otro usuario.',
            'billing_doc_type.in' => 'El tipo de documento debe ser CI o NIT.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        if ($request->has('name'))
            $user->name = $request->name;
        if ($request->has('billing_document'))
            $user->billing_document = $request->billing_document;
        if ($request->has('billing_razon_social'))
            $user->billing_razon_social = $request->billing_razon_social;
        if ($request->has('billing_doc_type'))
            $user->billing_doc_type = $request->billing_doc_type;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'Perfil actualizado exitosamente',
            'user' => $user->fresh(),
        ]);
    }

    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json([
            'message' => 'Token FCM actualizado exitosamente',
        ]);
    }
}
