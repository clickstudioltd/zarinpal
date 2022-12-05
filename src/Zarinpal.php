<?php

namespace Zarinpal;

use GuzzleHttp\Exception\RequestException;
use Zarinpal\Messages\Message;
use Zarinpal\Clients\IClient;

class Zarinpal
{
    /**
     * Merchant ID.
     *
     * @var string
     */
    public string $merchantID;

    /**
     * REST Client.
     *
     * @var IClient
     */
    public IClient $client;

    /**
     * Sandbox environment.
     *
     * @var bool
     */
    public bool $sandbox;

    /**
     * Zaringate portal.
     *
     * @var bool
     */
    public bool $zaringate;

    /**
     * Zaringate Psp.
     *
     * @var string
     */
    public string $zaringatePsp;

    /**
     * Messages language (en|fa).
     *
     * @var string
     */
    public string $lang;

    /**
     * Laravel environment.
     *
     * @var bool
     */
    public bool $laravel;

    /**
     * Zarinpal constructor.
     *
     * @param  string  $merchantID
     * @param  IClient  $client
     * @param  bool  $sandbox
     * @param  bool  $zaringate
     * @param  string  $zaringatePsp
     * @param  string  $lang
     * @param  bool  $laravel
     */
    public function __construct(string $merchantID, IClient $client, bool $sandbox = false, bool $zaringate = false, string $zaringatePsp = '', string $lang = 'fa', bool $laravel = true) {
        $this->merchantID = $merchantID;
        $this->client = $client;
        $this->sandbox = $sandbox;
        $this->zaringate = $zaringate;
        $this->zaringatePsp = $zaringatePsp;
        $this->lang = $lang;
        $this->laravel = $laravel;
    }

    /**
     * Request for new payment to get "Authority" if no error occurs.
     *
     * @param  array  $payload
     *
     * @see http://bit.ly/3sVkMU9
     * @throws RequestException
     * @return array
     */
    public function request(array $payload)
    {
        $response = $this->client->sendRequest($this->sandbox ? 'PaymentRequest' : 'request', $this->makePayload($payload));

        return $this->sandbox ? [
            'code' => $response['Status'] ?? null,
            'authority' => $response['Authority'] ?? '',
        ] : [
            'code' => $response['data']['code'] ?? null,
            'authority' => $response['data']['authority'] ?? '',
        ];
    }

    /**
     * Verify payment success.
     *
     * @param  array  $payload
     *
     * @see http://bit.ly/3a75K54
     * @throws RequestException
     * @return array
     */
    public function verify(array $payload)
    {
        $response = $this->client->sendRequest($this->sandbox ? 'PaymentVerification' : 'verify', $this->makePayload($payload));

        return $this->sandbox ? [
            'code' => $response['Status'] ?? null,
            'ref_id' => $response['RefID'] ?? '',
        ] : [
            'code' => $response['data']['code'] ?? null,
            'ref_id' => $response['data']['ref_id'] ?? '',
        ];
    }

    /**
     * Get message of status code.
     *
     * @param int $code
     *
     * @see http://bit.ly/2M5Ltoz
     * @return string
     */
    public function getCodeMessage(int $code)
    {
        return Message::get($this->lang, $code);
    }

    /**
     * Get generated redirect url.
     *
     * @param string $authority
     *
     * @see http://bit.ly/2MsIOF7
     * @return string
     */
    public function getRedirectUrl(string $authority)
    {
        $zaringateUrl = ($this->zaringate) ? '/ZarinGate' : '';

        if ($this->zaringate && trim($this->zaringatePsp) !== '' && in_array($this->zaringatePsp, ['Asan', 'Sep', 'Sad', 'Pec', 'Fan', 'Emz'])) {
            $zaringateUrl = '/' . $this->zaringatePsp;
        }

        return 'https://' . ($this->sandbox ? 'sandbox' : 'www') . '.zarinpal.com/pg/StartPay/' . $authority . $zaringateUrl;
    }

    /**
     * Redirect to payment page.
     *
     * @param string $authority
     *
     * @return mixed
     */
    public function redirect(string $authority)
    {
        $url = $this->getRedirectUrl($authority);

        if ($this->laravel) {
            return redirect($url);
        }

        header('Location: ' . $url);
        exit;
    }

    /**
     * Make payment request payload.
     *
     * @param  array  $payload
     *
     * @return array
     */
    private function makePayload(array $payload)
    {
        return array_merge($payload, $this->sandbox ? [
            'MerchantID' => $this->merchantID
        ] : [
            'merchant_id' => $this->merchantID
        ]);
    }
}
