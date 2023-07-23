<?php

namespace App\Lib;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class SignNow {
    private $http = null;

    public function __construct(Request $request) {
        $this->baseUrl = config('sign-now.base-url');

        $this->http = Http::withHeaders([
            "Authorization" => "Basic " . config('sign-now.basic-token')
        ]);

        if (
            $request instanceof Request &&
            ! empty($request->header('Authorization'))
        ) {
            $this->http = Http::withToken(
                ltrim($request->header('Authorization'), 'Bearer ')
            );
        }
    }

    public function requestAccessUrl() {
        $client_id = config('sign-now.client-id');
        $redirect_url = urlencode(route('sign-now-handle-auth-code'));

        return "https://app.signnow.com/webapp/static/grant-permission?client_id={$client_id}&redirect_uri={$redirect_url}&response_type=code";
    }

    private function genUrl(string $path) {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
    }

    public function requestToken(string $authorization_code) {
        $response = $this->http->post(
            $this->genUrl("/oauth2/token"),
            [
                "grant_type" => "authorization_code",
                "code" => $authorization_code,
            ]
        );

        if ($response->ok()) return $response->json();

        return false;
    }

    public function refreshToken(string $refresh_token) {
        $response = $this->http->post(
            $this->genUrl("/oauth2/token"),
            [
                "grant_type" => "refresh_token",
                "refresh_token" => $refresh_token,
            ]
        );

        if ($response->ok()) return $response->json();

        return false;
    }

    public function setAuthTokens(string $access_token, string $refresh_token) {
        config([
            'sign-now.bearer-token' => $access_token,
            'sign-now.refresh-token' => $refresh_token,
        ]);
    }

    public function getDocument($documentId) {
        return $this->http->get(
            $this->genUrl("/document/{$documentId}")
        );
    }

    public function updateDocument(
        string $documentId,
        array $fields,
        array $elements = [],
        $client_timestamp = null
    ) {
        return $this->http->put(
            $this->genUrl("/document/{$documentId}"),
            [
                "fields"           => $fields,
                "elements"         => $elements,
                "client_timestamp" => $client_timestamp,
            ]
        );
    }

    public function deleteDocument($documentId) {
        return $this->http->delete(
            $this->genUrl("/document/{$documentId}")
        );
    }

    public function createSigningLink($documentId) {
        return $this->http->post(
            $this->genUrl("/link"),
            [
                "document_id" => $documentId
            ]
        );
    }

    public function getSigningLinks(array $queryParams = [], $headers = []) {
        return $this->http->get(
            $this->genUrl("/v2/application/signing-links"),
            $queryParams
        );
    }

    public function createInvite($documentId, $recipients, $from) {
        return $this->http->post(
            $this->genUrl("/document/{$documentId}/invite"),
            [
                "from" => $from,
                "to" => $recipients
            ]
        );
    }
}
