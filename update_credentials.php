<?php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = 'e2vadm@evbol.com';
$password = 'E2v#Secure!2026';

$user = User::where('email', 'admin@admin.com')->first();
if (!$user) {
    $user = new User();
}

$user->name = 'e2vadm';
$user->email = $email;
$user->password = Hash::make($password);
$user->save();

echo "SUCCESS: CMS Admin updated. User: $email, Password: $password\n";
