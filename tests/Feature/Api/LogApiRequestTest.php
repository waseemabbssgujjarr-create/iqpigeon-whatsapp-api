<?php

namespace Tests\Feature\Api;

use App\Http\Middleware\LogApiRequest;
use App\Models\ApiRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\Concerns\CreatesApiPartners;
use Tests\TestCase;

class LogApiRequestTest extends TestCase
{
    use CreatesApiPartners;

    public function test_api_request_duration_uses_elapsed_ms_not_epoch_scale(): void
    {
        ['secret' => $secret] = $this->createActivePartnerWithApiKey(['connections.read']);

        $this->getJson('/api/v1/connections', $this->withBearer($secret))
            ->assertOk();

        $logged = ApiRequest::query()->orderByDesc('id')->first();

        $this->assertNotNull($logged);
        $this->assertNotNull($logged->duration_ms);
        $this->assertLessThan(60_000, $logged->duration_ms);
        $this->assertGreaterThanOrEqual(0, $logged->duration_ms);
    }

    public function test_terminate_on_fresh_middleware_instance_still_reads_start_time(): void
    {
        $middleware = new LogApiRequest;
        $request = Request::create('/api/v1/me', 'GET');
        $request->attributes->set('request_id', 'req-terminate-isolation-'.uniqid());

        $middleware->handle($request, fn () => new Response('ok', 200));

        $freshInstance = new LogApiRequest;
        $freshInstance->terminate($request, new Response('ok', 200));

        $logged = ApiRequest::query()->where('request_id', $request->attributes->get('request_id'))->first();

        $this->assertNotNull($logged);
        $this->assertLessThan(60_000, (int) $logged->duration_ms);
    }
}
