# Mobile App Plan (Flutter) - V1

## Architecture (recommended)

- Flutter app (iOS/Android/Web) talks to Laravel API only.
- Laravel handles all business logic (wallet, tariffs, roles, sessions).
- Redis/observer remains backend-only.
- No direct DB access from app.

## Implemented API behavior

### Register
`POST /api/v1/mobile/register`

Body:
```json
{
  "name": "Jorge",
  "email": "jorge@example.com",
  "password": "secret123",
  "nfc_id": "optional-device-nfc-id"
}
```

Now does:
1. create user
2. assign role `client`
3. create wallet (balance 0)
4. create RFID tag (from `nfc_id` if present; otherwise generated)
5. return Sanctum token

### Login
`POST /api/v1/mobile/login`

### Profile
`GET /api/v1/mobile/profile` (auth:sanctum)

Now returns user + roles + wallet + rfid_tag.

## Next steps (backend)

1. Wallet top-up endpoint (local mode):
   - `POST /api/v1/mobile/wallet/topup`
2. Station list with live status enriched for app UX.
3. Start/stop session endpoint wired to existing remote start/stop logic.
4. Session detail endpoint with live accumulated pricing.

## Next steps (Flutter)

1. Create Flutter project (multi-platform enabled).
2. Add Dio + Riverpod/Bloc + secure storage.
3. Implement auth flow:
   - register/login/store token
   - profile screen (wallet + tag)
4. Implement stations list and start/stop actions.
