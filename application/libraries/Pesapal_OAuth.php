<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pesapal_OAuth
{
    /**
     * Generate OAuth signature
     */
    public static function getSignature($method, $url, $params, $consumer_secret)
    {
        $parts = [$method, $url];
        $sorted_params = $params;
        ksort($sorted_params);
        $parts[] = http_build_query($sorted_params, '', '&', PHP_QUERY_RFC3986);
        $base_string = implode('&', array_map('rawurlencode', $parts));
        return base64_encode(hash_hmac('sha1', $base_string, $consumer_secret . '&', true));
    }
    
    /**
     * Generate nonce (random string)
     */
    public static function generateNonce()
    {
        return md5(uniqid(rand(), true));
    }
    
    /**
     * Get OAuth Authorization header
     */
    public static function getAuthorizationHeader($method, $url, $consumer_key, $consumer_secret, $callback_url = null)
    {
        $params = [
            'oauth_callback' => $callback_url ?: base_url('pesapal/ipn'),
            'oauth_consumer_key' => $consumer_key,
            'oauth_nonce' => self::generateNonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        ];
        
        // Remove callback if not needed for query
        if ($callback_url === null) {
            unset($params['oauth_callback']);
        }
        
        $signature = self::getSignature($method, $url, $params, $consumer_secret);
        $params['oauth_signature'] = $signature;
        
        $header = 'Authorization: OAuth ';
        $parts = [];
        foreach ($params as $key => $value) {
            $parts[] = $key . '="' . rawurlencode($value) . '"';
        }
        $header .= implode(', ', $parts);
        
        return $header;
    }
}