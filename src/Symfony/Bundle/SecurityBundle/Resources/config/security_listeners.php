<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\Security\Core\Authentication\Provider\AnonymousAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Provider\LdapBindAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Provider\PreAuthenticatedAuthenticationProvider;
use Symfony\Component\Security\Http\AccessMap;
use Symfony\Component\Security\Http\Authentication\CustomAuthenticationFailureHandler;
use Symfony\Component\Security\Http\Authentication\CustomAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\EntryPoint\BasicAuthenticationEntryPoint;
use Symfony\Component\Security\Http\EntryPoint\FormAuthenticationEntryPoint;
use Symfony\Component\Security\Http\EntryPoint\RetryAuthenticationEntryPoint;
use Symfony\Component\Security\Http\EventListener\CookieClearingLogoutListener;
use Symfony\Component\Security\Http\EventListener\DefaultLogoutListener;
use Symfony\Component\Security\Http\EventListener\SessionLogoutListener;
use Symfony\Component\Security\Http\Firewall\AccessListener;
use Symfony\Component\Security\Http\Firewall\AnonymousAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\BasicAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\ChannelListener;
use Symfony\Component\Security\Http\Firewall\ContextListener;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\Firewall\LogoutListener;
use Symfony\Component\Security\Http\Firewall\RemoteUserAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordJsonAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\X509AuthenticationListener;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('security.authentication.listener.anonymous', AnonymousAuthenticationListener::class)
            ->args([
                service('security.untracked_token_storage'),
                abstract_arg('Key'),
                service('logger')->nullOnInvalid(),
                service('security.authentication.manager'),
            ])
            ->tag('monolog.logger', ['channel' => 'security'])
            ->deprecate('symfony/security-bundle', '5.3', 'The "%service_id%" service is deprecated, use the new authenticator system instead.')

        ->set('security.authentication.provider.anonymous', AnonymousAuthenticationProvider::class)
            ->args([abstract_arg('Key')])
            ->deprecate('symfony/security-bundle', '5.3', 'The "%service_id%" service is deprecated, use the new authenticator system instead.')

        ->set('security.authentication.retry_entry_point', RetryAuthenticationEntryPoint::class)
            ->args([
                inline_service('int')->factory([service('router.request_context'), 'getHttpPort']),
                inline_service('int')->factory([service('router.request_context'), 'getHttpsPort']),
            ])

        ->set('security.authentication.basic_entry_point', BasicAuthenticationEntryPoint::class)

        ->set('security.channel_listener', ChannelListener::class)
            ->args([
                service('security.access_map'),
                service('security.authentication.retry_entry_point'),
                service('logger')->nullOnInvalid(),
            ])
            ->tag('monolog.logger', ['channel' => 'security'])

        ->set('security.access_map', AccessMap::class)

        ->set('security.context_listener', ContextListener::class)
            ->args([
                service('security.untracked_token_storage'),
                [],
                abstract_arg('Provider Key'),
                service('logger')->nullOnInvalid(),
                service('event_dispatcher')->nullOnInvalid(),
                service('security.authentication.trust_resolver'),
            ])
            ->tag('monolog.logger', ['channel' => 'security'])

        ->set('security.logout_listener', LogoutListener::class)
            ->abstract()
            ->args([
                service('security.token_storage'),
                service('security.http_utils'),
                abstract_arg('event dispatcher'),
                [], // Options
            ])

        ->set('security.logout.listener.session', SessionLogoutListener::class)
            ->abstract()

        ->set('security.logout.listener.cookie_clearing', CookieClearingLogoutListener::class)
            ->abstract()

        ->set('security.logout.listener.default', DefaultLogoutListener::class)
            ->abstract()
            ->args([
                service('security.http_utils'),
                abstract_arg('target url'),
            ])

        ->set('security.authentication.form_entry_point', FormAuthenticationEntryPoint::class)
            ->abstract()
            ->args([
                service('http_kernel'),
            ])

        ->set('security.authentication.listener.abstract')
            ->abstract()
            ->args([
                service('security.token_storage'),
                service('security.authentication.manager'),
                service('security.authentication.session_strategy'),
                service('security.http_utils'),
                abstract_arg('Provider-shared Key'),
                service('security.authentication.success_handler'),
                service('security.authentication.failure_handler'),
                [],
                service('logger')->nullOnInvalid(),
                service('event_dispatcher')->nullOnInvalid(),
            ])
            ->tag('monolog.logger', ['channel' => 'security'])

        ->set('security.authentication.custom_success_handler', CustomAuthenticationSuccessHandler::class)
            ->abstract()
            ->args([
                abstract_arg('The custom success handler service'),
                [], // Options
                abstract_arg('Provider-shared Key'),
            ])

        ->set('security.authentication.success_handler', DefaultAuthenticationSuccessHandler::class)
            ->abstract()
            ->args([
                service('security.http_utils'),
                [], // Options
            ])

        ->set('security.authentication.custom_failure_handler', CustomAuthenticationFailureHandler::class)
            ->abstract()
            ->args([
                abstract_arg('The custom failure handler service'),
                [], // Options
            ])

        ->set('security.authentication.failure_handler', DefaultAuthenticationFailureHandler::class)
            ->abstract()
            ->args([
                service('http_kernel'),
                service('security.http_utils'),
                [], // Options
                service('logger')->nullOnInvalid(),
            ])
            ->tag('monolog.logger', ['channel' => 'security'])

        ->set('security.authentication.listener.form', UsernamePasswordFormAuthenticationListener::class)
            ->parent('security.authentication.listener.abstract')
            ->abstract()
            ->deprecate('symfony/security-bundle', '5.3', 'The "%service_id%" service is deprecated, use the new authenticator system instead.')

        ->set('security.authentication.listener.x509', X509AuthenticationListener::class)
            ->abstract()
            ->args([
                service('security.token_storage'),
                service('security.authentication.manager'),
                abstract_arg('Provider-shared Key'),
                abstract_arg('x509 user'),
                abstract_arg('x509 credentials'),
                service('logger')->nullOnInvalid(),
                service('event_dispatcher')->nullOnInvalid(),
            ])
            ->tag('monolog.logger', ['channel' => 'security'])
            ->deprecate('symfony/security-bundle', '5.3', 'The "%service_id%" service is deprecated, use the new authenticator system instead.')

        ->set('security.authentication.listener.json', UsernamePasswordJsonAuthenticationListener::class)
            ->abstract()
            ->args([
                service('security.token_storage'),
                service('security.authentication.manager'),
                service('security.http_utils'),
                abstract_arg('Provider-shared Key'),
                abstract_arg('Failure handler'),
                abstract_arg('Success Handler'),
                [], // Options
                service('logger')->nullOnInvalid(),
                service('event_dispatcher')->nullOnInvalid(),
                service('property_accessor')->nullOnInvalid(),
            ])
            ->call('setTranslator', [service('translator')->ignoreOnInvalid()])
            ->tag('monolog.logger', ['channel' => 'security'])
            ->deprecate('symfony/security-bundle', '5.3', 'The "%service_id%" service is deprecated, use the new authenticator system instead.')

        ->set('security.authentication.listener.remote_user', RemoteUserAuthenticationListener::class)
            ->abstract()
            ->args([
                service('security.token_storage'),
                service('security.authentication.manager'),
                abstract_arg('Provider-shared Key'),
                abstract_arg('REMOTE_USER server env var'),
                service('logger')->nullOnInvalid(),
                service('event_dispatcher')->nullOnInvalid(),
            ])
            ->tag('monolog.logger', ['channel' => 'security'])
            ->deprecate('symfony/security-bundle', '5.3', 'The "%service_id%" service is deprecated, use the new authenticator system instead.')

        ->set('security.authentication.listener.basic', BasicAuthenticationListener::class)
            ->abstract()
            ->args([
                service('security.token_storage'),
                service('security.authentication.manager'),
                abstract_arg('Provider-shared Key'),
                abstract_arg('Entry Point'),
                service('logger')->nullOnInvalid(),
            ])
            ->tag('monolog.logger', ['channel' => 'security'])
            ->deprecate('symfony/security-bundle', '5.3', 'The "%service_id%" service is deprecated, use the new authenticator system instead.')

        ->set('security.authentication.provider.dao', DaoAuthenticationProvider::class)
            ->abstract()
            ->args([
                abstract_arg('User Provider'),
                abstract_arg('User Checker'),
                abstract_arg('Provider-shared Key'),
                service('security.password_hasher_factory'),
                param('security.authentication.hide_user_not_found'),
            ])
            ->deprecate('symfony/security-bundle', '5.3', 'The "%service_id%" service is deprecated, use the new authenticator system instead.')

        ->set('security.authentication.provider.ldap_bind', LdapBindAuthenticationProvider::class)
            ->abstract()
            ->args([
                abstract_arg('User Provider'),
                abstract_arg('UserChecker'),
                abstract_arg('Provider-shared Key'),
                abstract_arg('LDAP'),
                abstract_arg('Base DN'),
                param('security.authentication.hide_user_not_found'),
                abstract_arg('search dn'),
                abstract_arg('search password'),
            ])
            ->deprecate('symfony/security-bundle', '5.3', 'The "%service_id%" service is deprecated, use the new authenticator system instead.')

        ->set('security.authentication.provider.pre_authenticated', PreAuthenticatedAuthenticationProvider::class)
            ->abstract()
            ->args([
                abstract_arg('User Provider'),
                abstract_arg('UserChecker'),
            ])
            ->deprecate('symfony/security-bundle', '5.3', 'The "%service_id%" service is deprecated, use the new authenticator system instead.')

        ->set('security.exception_listener', ExceptionListener::class)
            ->abstract()
            ->args([
                service('security.token_storage'),
                service('security.authentication.trust_resolver'),
                service('security.http_utils'),
                abstract_arg('Provider-shared Key'),
                service('security.authentication.entry_point')->nullOnInvalid(),
                param('security.access.denied_url'),
                service('security.access.denied_handler')->nullOnInvalid(),
                service('logger')->nullOnInvalid(),
                false, // Stateless
            ])
            ->tag('monolog.logger', ['channel' => 'security'])

        ->set('security.authentication.switchuser_listener', SwitchUserListener::class)
            ->abstract()
            ->args([
                service('security.token_storage'),
                abstract_arg('User Provider'),
                abstract_arg('User Checker'),
                abstract_arg('Provider Key'),
                service('security.access.decision_manager'),
                service('logger')->nullOnInvalid(),
                '_switch_user',
                'ROLE_ALLOWED_TO_SWITCH',
                service('event_dispatcher')->nullOnInvalid(),
                false, // Stateless
            ])
            ->tag('monolog.logger', ['channel' => 'security'])

        ->set('security.access_listener', AccessListener::class)
            ->args([
                service('security.token_storage'),
                service('security.access.decision_manager'),
                service('security.access_map'),
                service('security.authentication.manager'),
            ])
            ->tag('monolog.logger', ['channel' => 'security'])
    ;
};
