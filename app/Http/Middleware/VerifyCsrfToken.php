<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Symfony\Component\HttpFoundation\Cookie;
class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'accept_url',
        'threeDsecureReturn',
        'processing-return-url',
        'hosted-voguepay-payment-redirect',
        'hosted-pay/payment-request',
        'hosted-pay/input-card/*',
        'hosted-vougepay/payment-request',
        'redirect-after-payment',
        'redirect-after-payment-api',
        'hosted-pay',
        'payment/test-transaction/*',
        'wonderland-checkout/response/*',
        'gatewayservice-return',
        'secure-gateway/notify/*',
        'order-verify-page-submit',
        'checkout-form/*',
        'opay/waiting-submit/*',
        'opay/input-submit/*',
        'opay/callback',
        'onlinenaira-notify',   
        'triplea-webhook-url',
        'wyre/form-submit',
        'wyre/callback/notify',
        'bitbaypay/notify',
        'secure-gateway/notify/*',
        'cryptoxa/callback/*',
        'interkassa/callback/*',
        'interkassa/success/*',
        'interkassa/fail/*',
        'paycos/callback/*',
        'trust/notification/*',
        'opennode-callbackUrl/*',
        'interkassa-upi/success/*',
        'interkassa-upi/fail/*',
        'interkassa-net-banking/success/*',
        'interkassa-net-banking/fail/*',
        'vippass/callback/*',
        'vippass/webhook/*',
        'qartpay/callback/*',
        'paythone/callback',
        'chakra/callback/*'
    ];

    /**
 * Add the CSRF token to the response cookies.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  \Symfony\Component\HttpFoundation\Response  $response
 * @return \Symfony\Component\HttpFoundation\Response
 */
protected function addCookieToResponse($request, $response)
{
    $config = config('session');

    $response->headers->setCookie(
        new Cookie(
            'XSRF-TOKEN', $request->session()->token(), $this->availableAt(60 * $config['lifetime']),
            $config['path'], $config['domain'], $config['secure'], true, true, $config['same_site'] ?? null
        )
    );
    return $response;
}
}
