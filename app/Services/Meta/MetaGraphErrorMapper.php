<?php

namespace App\Services\Meta;

/**
 * Maps Meta Graph / WhatsApp Cloud API error payloads to stable customer-facing codes and messages.
 */
class MetaGraphErrorMapper
{
    /**
     * @return array{failure_code: string, failure_message: string, provider_code: string|null}
     */
    public function mapSendFailure(int $httpStatus, mixed $errorBody): array
    {
        $providerCode = $this->extractProviderCode($errorBody);
        $providerMessage = $this->extractProviderMessage($errorBody);

        $failureCode = match ($providerCode) {
            '133010', '133010.0' => 'whatsapp_number_not_registered',
            '131037', '131037.0' => 'display_name_not_approved',
            '132001', '132001.0' => 'template_not_found',
            '132000', '132000.0' => 'template_param_mismatch',
            '131026', '131026.0' => 'recipient_not_on_whatsapp',
            '131047', '131047.0' => 're_engagement_required',
            '190', '190.0' => 'access_token_invalid',
            '200', '200.0' => 'permission_denied',
            '368', '368.0' => 'temporarily_blocked',
            '100', '100.0' => 'invalid_parameter',
            default => 'graph_request_failed',
        };

        $failureMessage = match ($failureCode) {
            'whatsapp_number_not_registered' => 'This WhatsApp number is not registered for Cloud API sending. Complete number registration in the IQPigeon dashboard (6-digit two-step verification PIN), then retry.',
            'display_name_not_approved' => 'Meta has not approved the display name for this sending number. Complete display name review in Meta Business Manager, then retry. Meta error 131037.',
            'template_not_found' => 'The template name or language does not exist on this WhatsApp Business Account. Use the Meta template element name (for example hello_world), not the human-readable title.',
            'template_param_mismatch' => 'Template parameters do not match the approved template. Check components and variable placeholders.',
            'recipient_not_on_whatsapp' => 'The recipient is not registered on WhatsApp or cannot receive messages.',
            're_engagement_required' => 'Outside the 24-hour session window. Send an approved template message to re-open the conversation.',
            'access_token_invalid' => 'Meta access token is invalid or expired. Reconnect WhatsApp in the IQPigeon dashboard.',
            'permission_denied' => 'This access token does not have permission for the requested WhatsApp action. Reconnect and grant required permissions.',
            'temporarily_blocked' => 'Meta temporarily blocked this action due to policy or quality limits. Try again later.',
            'invalid_parameter' => 'Meta rejected the request parameters. '.$this->safeProviderDetail($providerCode, $providerMessage, $httpStatus),
            default => $this->safeProviderDetail($providerCode, $providerMessage, $httpStatus),
        };

        return [
            'failure_code' => $failureCode,
            'failure_message' => substr($failureMessage, 0, 2000),
            'provider_code' => $providerCode,
        ];
    }

    /**
     * @return array{failure_code: string, failure_message: string, provider_code: string|null}
     */
    public function mapRegistrationFailure(int $httpStatus, mixed $errorBody): array
    {
        $mapped = $this->mapSendFailure($httpStatus, $errorBody);
        $providerCode = $mapped['provider_code'];

        if ($this->registrationAlreadyComplete($providerCode, $mapped['failure_message'])) {
            return [
                'failure_code' => 'already_registered',
                'failure_message' => 'This number is already registered for Cloud API sending.',
                'provider_code' => $providerCode,
            ];
        }

        return $mapped;
    }

    public function registrationAlreadyComplete(?string $providerCode, string $message): bool
    {
        $haystack = strtolower($message);

        if (str_contains($haystack, 'already registered')) {
            return true;
        }

        return in_array($providerCode, ['133015', '133016'], true);
    }

    private function extractProviderCode(mixed $errorBody): ?string
    {
        if (! is_array($errorBody)) {
            return null;
        }

        $code = data_get($errorBody, 'error.code');

        if ($code === null || $code === '') {
            return null;
        }

        return (string) $code;
    }

    private function extractProviderMessage(mixed $errorBody): string
    {
        if (! is_array($errorBody)) {
            return 'Meta Graph request failed.';
        }

        return trim((string) data_get($errorBody, 'error.message', 'Meta Graph request failed.'));
    }

    private function safeProviderDetail(?string $providerCode, string $providerMessage, int $httpStatus): string
    {
        $detail = trim($providerMessage);
        if ($providerCode !== null && $providerCode !== '') {
            $detail = 'Meta error '.$providerCode.': '.$detail;
        }

        return 'HTTP '.$httpStatus.'. '.$detail;
    }
}
