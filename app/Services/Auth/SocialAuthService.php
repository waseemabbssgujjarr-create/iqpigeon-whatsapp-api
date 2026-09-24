<?php

namespace App\Services\Auth;

use App\Models\OAuthIdentity;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialAuthService
{
    public function __construct(
        private PartnerProvisioner $partnerProvisioner,
    ) {}

    public function findOrCreateUser(string $provider, SocialiteUser $oauthUser): User
    {
        $providerUserId = (string) $oauthUser->getId();
        $email = strtolower(trim((string) ($oauthUser->getEmail() ?? '')));
        $name = trim((string) ($oauthUser->getName() ?: 'Platform User'));
        $avatar = $oauthUser->getAvatar();

        if ($providerUserId === '') {
            throw new \InvalidArgumentException('Provider did not return a user id.');
        }

        if ($email === '') {
            throw new \InvalidArgumentException('Provider did not return an email address.');
        }

        return DB::transaction(function () use ($provider, $providerUserId, $email, $name, $avatar, $oauthUser) {
            $identity = OAuthIdentity::query()
                ->where('provider', $provider)
                ->where('provider_user_id', $providerUserId)
                ->first();

            if ($identity !== null) {
                $this->touchIdentity($identity, $avatar);

                return $identity->user;
            }

            $user = User::query()->where('email', $email)->first();

            if ($user !== null) {
                $this->attachIdentity($user, $provider, $providerUserId, $avatar);
                $this->markEmailVerifiedIfNeeded($user, $oauthUser);

                return $user->refresh();
            }

            $verificationEnabled = (bool) config('auth.email_verification_enabled');
            $emailVerifiedAt = (! $verificationEnabled || $this->providerMarksEmailVerified($oauthUser)) ? now() : null;

            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(48)),
                'email_verified_at' => $emailVerifiedAt,
            ]);

            $company = $name.' API';
            $this->partnerProvisioner->createForOwner($user, $company);
            $this->attachIdentity($user, $provider, $providerUserId, $avatar);

            event(new Registered($user));

            return $user->refresh();
        });
    }

    private function attachIdentity(User $user, string $provider, string $providerUserId, ?string $avatar): void
    {
        OAuthIdentity::query()->updateOrCreate(
            [
                'provider' => $provider,
                'provider_user_id' => $providerUserId,
            ],
            [
                'user_id' => $user->id,
                'avatar_url' => $avatar,
            ],
        );
    }

    private function touchIdentity(OAuthIdentity $identity, ?string $avatar): void
    {
        if ($avatar !== null && $avatar !== $identity->avatar_url) {
            $identity->forceFill(['avatar_url' => $avatar])->save();
        }
    }

    private function markEmailVerifiedIfNeeded(User $user, SocialiteUser $oauthUser): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        if ((bool) config('auth.email_verification_enabled') && ! $this->providerMarksEmailVerified($oauthUser)) {
            return;
        }

        $user->forceFill(['email_verified_at' => now()])->save();
    }

    private function providerMarksEmailVerified(SocialiteUser $oauthUser): bool
    {
        $raw = $oauthUser->getRaw();
        if (is_array($raw) && array_key_exists('email_verified', $raw)) {
            return (bool) $raw['email_verified'];
        }

        return true;
    }
}
