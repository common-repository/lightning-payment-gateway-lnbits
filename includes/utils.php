<?php
namespace LNBitsPlugin;


class Utils {
    public static function convert_to_satoshis($amount, $currency) {
        if(strtolower($currency) !== 'btc') {
            $currency = strtoupper($currency);
            error_log($amount . " " . $currency);
            $c    = new CurlWrapper();
            $resp = $c->get('https://api.opennode.co/v1/rates', array(), array());

            if ($resp['status'] != 200) {
                throw new \Exception('Opennode.co request for currency conversion failed. Got status ' . $resp['status']);
            }

            if(!isset($resp['response']['data'])) {
                throw new \Exception('Opennode.co request for currency conversion failed. Got bad response.');
            }

             if(!isset($resp['response']['data']['BTC' . $currency])) {
                throw new \Exception('Opennode.co request for currency conversion failed. Your currency was not found in the response.');
            }

            if(!isset($resp['response']['data']['BTC' . $currency][$currency])) {
                throw new \Exception('Opennode.co request for currency conversion failed. An unexpected error has occurred.');
            }

            $price = $resp['response']['data']['BTC' . $currency][$currency];

            return (int) round(($amount / $price) * 100000000);
        }
        else {
            return intval($amount * 100000000);
        }
    }
}

class CurlWrapper {

    private function request($method, $url, $params, $headers, $data) {
        $url = add_query_arg($params, $url);
        $r = wp_remote_request($url, array(
            'method' => $method,
            'headers' => $headers,
            'body' => $data ? json_encode($data) : ''
        ));

        if (is_wp_error($r)) {
            error_log('WP_Error: '.$r->get_error_message());
            return array(
                'status' => 500,
                'response' => $r->get_error_message()
            );
        }

        return array(
            'status' => $r['response']['code'],
            'response' => json_decode($r['body'], true)
        );
    }

    public function get($url, $params, $headers) {
        return $this->request('GET', $url, $params, $headers, null);
    }

    public function post($url, $params, $data, $headers) {
        return $this->request('POST', $url, $params, $headers, $data);
    }
}
