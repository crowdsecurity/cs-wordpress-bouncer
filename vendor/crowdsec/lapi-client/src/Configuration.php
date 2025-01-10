<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient;

use CrowdSec\Common\Configuration\AbstractConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * The LAPI client configuration.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Configuration extends AbstractConfiguration
{
    /** @var array<string> The list of each configuration tree key */
    protected $keys = [
        'user_agent_suffix',
        'user_agent_version',
        'api_url',
        'appsec_url',
        'auth_type',
        'api_key',
        'tls_cert_path',
        'tls_key_path',
        'tls_ca_cert_path',
        'tls_verify_peer',
        'api_timeout',
        'api_connect_timeout',
        'appsec_timeout_ms',
        'appsec_connect_timeout_ms',
    ];

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('config');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()
            ->scalarNode('user_agent_suffix')
                ->validate()
                    ->ifTrue(function (string $value) {
                        return 1 !== preg_match('#^[A-Za-z0-9]{0,16}$#', $value);
                    })
                    ->thenInvalid('Invalid user agent suffix. Length must be <= 16. Allowed chars are A-Za-z0-9')
                ->end()
            ->end()
            ->scalarNode('user_agent_version')
                ->validate()
                    ->ifTrue(function (string $value) {
                        if (!empty($value)) {
                            return 1 !== preg_match('#^v\d{1,4}(\.\d{1,4}){2}$#', $value);
                        }

                        return true;
                    })
                    ->thenInvalid('Invalid user agent version. Must match vX.Y.Z format')
                ->end()
            ->defaultValue(Constants::VERSION)
            ->end()
        ->end()
        ;
        $this->addConnectionNodes($rootNode);
        $this->addAppSecNodes($rootNode);
        $this->validate($rootNode);

        return $treeBuilder;
    }

    /**
     * AppSec settings.
     *
     * @param NodeDefinition|ArrayNodeDefinition $rootNode
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function addAppSecNodes($rootNode)
    {
        $rootNode->children()
            ->scalarNode('appsec_url')->cannotBeEmpty()->defaultValue(Constants::DEFAULT_APPSEC_URL)->end()
            ->integerNode('appsec_timeout_ms')->defaultValue(Constants::APPSEC_TIMEOUT_MS)->end()
            ->integerNode('appsec_connect_timeout_ms')->defaultValue(Constants::APPSEC_CONNECT_TIMEOUT_MS)->end()
        ->end();
    }

    /**
     * LAPI connection settings.
     *
     * @param NodeDefinition|ArrayNodeDefinition $rootNode
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function addConnectionNodes($rootNode)
    {
        $rootNode->children()
            ->scalarNode('api_url')->cannotBeEmpty()->defaultValue(Constants::DEFAULT_LAPI_URL)->end()
            ->enumNode('auth_type')
                ->values(
                    [
                        Constants::AUTH_KEY,
                        Constants::AUTH_TLS,
                    ]
                )
                ->defaultValue(Constants::AUTH_KEY)
            ->end()
            ->scalarNode('api_key')->end()
            ->scalarNode('tls_cert_path')
                ->info('Absolute path to the Bouncer certificate')->defaultValue('')
            ->end()
            ->scalarNode('tls_key_path')
                ->info('Absolute path to the Bouncer key')->defaultValue('')
            ->end()
            ->scalarNode('tls_ca_cert_path')
                ->info('Absolute path to the CA used to process TLS handshake')->defaultValue('')
            ->end()
            ->booleanNode('tls_verify_peer')->defaultValue(false)->end()
            ->integerNode('api_timeout')->defaultValue(Constants::API_TIMEOUT)->end()
            ->integerNode('api_connect_timeout')->defaultValue(Constants::API_CONNECT_TIMEOUT)->end()
        ->end();
    }

    /**
     * Conditional validation.
     *
     * @param NodeDefinition|ArrayNodeDefinition $rootNode
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    private function validate($rootNode)
    {
        $rootNode
            ->validate()
                ->ifTrue(function (array $v) {
                    if (Constants::AUTH_KEY === $v['auth_type'] && empty($v['api_key'])) {
                        return true;
                    }

                    return false;
                })
                ->thenInvalid('Api key is required as auth type is api_key')
            ->end()
            ->validate()
                ->ifTrue(function (array $v) {
                    if (Constants::AUTH_TLS === $v['auth_type']) {
                        return empty($v['tls_cert_path']) || empty($v['tls_key_path']);
                    }

                    return false;
                })
                ->thenInvalid('Bouncer certificate and key paths are required for tls authentification.')
            ->end()
            ->validate()
                ->ifTrue(function (array $v) {
                    if (Constants::AUTH_TLS === $v['auth_type'] && true === $v['tls_verify_peer']) {
                        return empty($v['tls_ca_cert_path']);
                    }

                    return false;
                })
                ->thenInvalid('CA path is required for tls authentification with verify_peer.')
        ->end();
    }
}
