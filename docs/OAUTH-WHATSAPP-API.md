# Dashboard OAuth (Google & Facebook) — whatsappapi.iqpigeon.com

Separate from Meta WhatsApp Embedded Signup (`/oauth/meta/*`).

## Callback URLs (register in provider consoles)

When `APP_URL=https://whatsappapi.iqpigeon.com` and redirect env vars are empty, Laravel uses:

| Provider | Redirect URI |
|----------|----------------|
| Google | `https://whatsappapi.iqpigeon.com/auth/google/callback` |
| Facebook | `https://whatsappapi.iqpigeon.com/auth/facebook/callback` |

Optional overrides: `GOOGLE_REDIRECT_URI`, `FACEBOOK_REDIRECT_URI` (must match exactly).

## Environment variables (placeholders in `.env.example` only)

| Variable | Purpose |
|----------|---------|
| `GOOGLE_CLIENT_ID` | Google OAuth client ID |
| `GOOGLE_CLIENT_SECRET` | Google OAuth client secret |
| `GOOGLE_REDIRECT_URI` | Optional override |
| `FACEBOOK_APP_ID` | Facebook app ID (same naming as main IQPigeon) |
| `FACEBOOK_APP_SECRET` | Facebook app secret |
| `FACEBOOK_REDIRECT_URI` | Optional override |
| `FACEBOOK_LOGIN_CONFIG_ID` | Facebook Login for Business config ID (if required) |

## Reusing main IQPigeon provider apps

You may reuse the same Google/Facebook **app credentials** as main IQPigeon, but you **must add** the callback URLs above to the provider allowlists. Do not remove existing IQPigeon callback URLs.

## Routes

- `GET /auth/google/redirect`
- `GET /auth/google/callback`
- `GET /auth/facebook/redirect`
- `GET /auth/facebook/callback`

## Boundaries

- No shared sessions with iqpigeon.com
- Separate `users` / `oauth_identities` database in this Laravel app
- Post-login redirect: `/app` dashboard only
