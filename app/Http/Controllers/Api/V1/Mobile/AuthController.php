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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            // Optional device NFC identifier (when available on device/platform)
            'nfc_id' => 'nullable|string|max:255',
        ]);

        $result = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
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
                $tagCode = 'APP' . strtoupper(Str::random(10));
            }

            // Guarantee uniqueness
            while (RfidTag::where('tag_code', $tagCode)->exists()) {
                $tagCode = 'APP' . strtoupper(Str::random(10));
            }

            $tag = RfidTag::create([
                'tag_code' => $tagCode,
                'user_id' => $user->id,
                'name' => 'Mobile Tag',
                'is_active' => true,
            ]);

            $token = $user->createToken('mobile-app')->plainTextToken;

            return compact('user', 'wallet', 'tag', 'token');
        });

        return response()->json([
            'message' => 'User registered successfully',
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
            'message' => 'Login successful',
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
            return response()->json(['message' => 'Invalid Google Token'], 401);
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

                $tagCode = 'APP' . strtoupper(Str::random(10));
                while (RfidTag::where('tag_code', $tagCode)->exists()) {
                    $tagCode = 'APP' . strtoupper(Str::random(10));
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
            'message' => 'Login successful',
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
            'name' => 'sometimes|string|max:255',
            'billing_document' => 'sometimes|nullable|string|max:50',
            'billing_razon_social' => 'sometimes|nullable|string|max:255',
            'billing_doc_type' => 'sometimes|nullable|string|max:20',
            'password' => 'sometimes|nullable|string|min:8|confirmed',
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
            'message' => 'Profile updated successfully',
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
            'message' => 'FCM token updated successfully',
        ]);
    }
}
