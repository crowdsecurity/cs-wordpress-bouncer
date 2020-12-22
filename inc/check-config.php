<?php

function checkCrowdSecConfig(): array
{
    $issues = ['errors' => [], 'warnings' => []];

    $bouncingLevel = esc_attr(get_option('crowdsec_bouncing_level'));
    $shouldBounce = (CROWDSEC_BOUNCING_LEVEL_DISABLED !== $bouncingLevel);

    if ($shouldBounce) {
        $apiUrl = esc_attr(get_option('crowdsec_api_url'));
        if (empty($apiUrl)) {
            $issues['errors'][] = [
                'type' => 'INCORRECT_API_URL',
                'message' => 'Bouncer enabled but no API URL provided',
            ];
        }

        $apiKey = esc_attr(get_option('crowdsec_api_key'));
        if (empty($apiKey)) {
            $issues['errors'][] = [
                'type' => 'INCORRECT_API_KEY',
                'message' => 'Bouncer enabled but no API key provided',
            ];
        }

        try {
            getCacheAdapterInstance();
        } catch (WordpressCrowdSecBouncerException $e) {
            $issues['errors'][] = [
                'type' => 'CACHE_CONFIG_ERROR',
                'message' => $e->getMessage(),
            ];
        }
    }

    return $issues;
}

function isBouncerConfigOk(): bool
{
    $issues = checkCrowdSecConfig();

    return !count($issues['errors']) && !count($issues['warnings']);
}
