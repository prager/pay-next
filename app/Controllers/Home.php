<?php
/* Updated v4.01 */
namespace App\Controllers;

use Stripe\Charge;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class Home extends BaseController
{
    public function index(): string
    {
        return view('public/mdarc_view', [
            'stripeKey' => env('mdarc_key'),
        ]);
    }

    public function mdarcPost(): string
    {
        log_message('info', 'MDARC payment POST received for {email}. Stripe token present: {token_present}', [
            'email' => (string) $this->request->getPost('email'),
            'token_present' => $this->request->getPost('stripeToken') ? 'yes' : 'no',
        ]);

        $validation = service('validation');
        $validation->setRules([
            'cc_name' => 'required|min_length[2]',
            'email' => 'required|valid_email',
            'stripeToken' => 'required',
            'ensubmit' => 'required',
        ]);

        if (! $validation->withRequest($this->request)->run()) {
            log_message('warning', 'MDARC payment validation failed: {errors}', [
                'errors' => json_encode($validation->getErrors()),
            ]);

            return $this->paymentView('Please complete the required payment fields.');
        }

        $payment = $this->buildPaymentData();

        if ($payment['total'] <= 0) {
            return $this->paymentView('Please select at least one payment item.');
        }

        if ($payment['donation_mdarc'] > 0 && $payment['donation_mdarc'] < 5) {
            return $this->paymentView('MDARC donation must be at least $5.00.');
        }

        if ($payment['donation_repeater'] > 0 && $payment['donation_repeater'] < 5) {
            return $this->paymentView('Repeater donation must be at least $5.00.');
        }

        $secretKey = env('mdarc_secret');

        if (empty($secretKey)) {
            log_message('error', 'Stripe secret key is missing from mdarc_secret.');

            return $this->paymentView('Payment configuration is missing. Please try again later.');
        }

        require_once APPPATH . 'ThirdParty/stripe-php/init.php';

        $caBundlePath = $this->stripeCaBundlePath();

        if ($caBundlePath === null) {
            log_message('error', 'Stripe CA bundle is missing. Checked bundled and common system certificate paths.');

            return $this->paymentView('Payment certificate configuration is missing. Please try again later.');
        }

        if (! is_readable($caBundlePath)) {
            log_message('error', 'Stripe CA bundle is not readable: {path}', [
                'path' => $caBundlePath,
            ]);

            return $this->paymentView('Payment certificate configuration is missing. Please try again later.');
        }

        Stripe::setCABundlePath($caBundlePath);
        Stripe::setApiKey($secretKey);

        try {
            log_message('info', 'Sending MDARC payment to Stripe for {email}. Amount cents: {amount}', [
                'email' => (string) $this->request->getPost('email'),
                'amount' => (string) ((int) round($payment['total'] * 100)),
            ]);

            $charge = Charge::create([
                'amount' => (int) round($payment['total'] * 100),
                'currency' => 'usd',
                'source' => $this->request->getPost('stripeToken'),
                'description' => 'MDARC payment for ' . $this->request->getPost('email'),
                'receipt_email' => $this->request->getPost('email'),
                'metadata' => [
                    'name_on_card' => $this->request->getPost('cc_name'),
                    'email' => $this->request->getPost('email'),
                    'student' => $payment['student'] ? 'yes' : 'no',
                    'membership' => number_format($payment['membership'], 2, '.', ''),
                    'carrier' => number_format($payment['carrier'], 2, '.', ''),
                    'donation_mdarc' => number_format($payment['donation_mdarc'], 2, '.', ''),
                    'donation_repeater' => number_format($payment['donation_repeater'], 2, '.', ''),
                ],
            ]);
        } catch (ApiErrorException $exception) {
            log_message('error', 'Stripe charge failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return $this->paymentView($exception->getMessage());
        }

        log_message('info', 'Stripe charge succeeded for MDARC payment. Charge ID: {charge_id}', [
            'charge_id' => $charge->id,
        ]);

        return $this->paymentView(null, sprintf(
            'Payment successful. Stripe charge ID: %s',
            $charge->id
        ));
    }

    private function paymentView(?string $error = null, ?string $success = null): string
    {
        return view('public/mdarc_view', [
            'stripeKey' => env('mdarc_key'),
            'paymentError' => $error,
            'paymentSuccess' => $success,
        ]);
    }

    private function stripeCaBundlePath(): ?string
    {
        $paths = [
            APPPATH . 'ThirdParty/stripe-php/data/ca-certificates.crt',
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/etc/ssl/ca-bundle.pem',
            '/usr/local/share/certs/ca-root-nss.crt',
            '/usr/local/etc/openssl/cert.pem',
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        $curlCaInfo = ini_get('curl.cainfo');

        if (is_string($curlCaInfo) && $curlCaInfo !== '' && is_file($curlCaInfo)) {
            return $curlCaInfo;
        }

        $opensslCaFile = ini_get('openssl.cafile');

        if (is_string($opensslCaFile) && $opensslCaFile !== '' && is_file($opensslCaFile)) {
            return $opensslCaFile;
        }

        return null;
    }

    /**
     * Recalculate payable amounts on the server instead of trusting browser totals.
     *
     * @return array<string, bool|float>
     */
    private function buildPaymentData(): array
    {
        $student = (bool) $this->request->getPost('student');
        $membership = $this->request->getPost('mem') ? ($student ? 15.00 : 45.00) : 0.00;
        $carrier = $this->request->getPost('carrier') ? 18.00 : 0.00;
        $donationMdarc = $this->request->getPost('donation') ? $this->moneyToFloat($this->request->getPost('donamnt')) : 0.00;
        $donationRepeater = $this->request->getPost('don_rep') ? $this->moneyToFloat($this->request->getPost('repamnt')) : 0.00;

        return [
            'student' => $student,
            'membership' => $membership,
            'carrier' => $carrier,
            'donation_mdarc' => $donationMdarc,
            'donation_repeater' => $donationRepeater,
            'total' => $membership + $carrier + $donationMdarc + $donationRepeater,
        ];
    }

    private function moneyToFloat(?string $value): float
    {
        $amount = preg_replace('/[^0-9.]/', '', (string) $value);

        return $amount === '' ? 0.00 : round((float) $amount, 2);
    }
}
