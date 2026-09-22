<?php

namespace Tests\Feature\Stripe;

use App\Enums\ProvisioningStatus;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class StripeCheckoutReturnTest extends TestCase
{
    use CreatesDashboardUsers;

    public function test_billing_return_url_does_not_activate_partner_without_webhook(): void
    {
        ['user' => $user, 'partner' => $partner] = $this->createVerifiedOwner();

        $partner->forceFill([
            'provisioning_status' => ProvisioningStatus::CheckoutPending,
        ])->save();

        $this->actingAs($user)
            ->get('/app/billing?checkout=returned')
            ->assertOk();

        $this->assertNotSame(
            ProvisioningStatus::Active,
            $partner->fresh()->provisioning_status,
        );
    }
}
