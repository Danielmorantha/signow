<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SignNow\Api\Entity\Invite\Recipient;

use App\Lib\SignNow;


class SignNowController extends Controller
{
    public function __construct(Request $request)
    {
        $this->signNow = new SignNow($request);
    }

    public function authenticate(Request $request)
    {
        return response()->redirectTo($this->signNow->requestAccessUrl());
    }

    public function handleAuthCode(Request $request)
    {
        $response = $this->signNow->requestToken($request->query('code'));

        if ($response) {
            $this->signNow->setAuthTokens($response["access_token"], $response["refresh_token"]);
        }

        return response()->json(["status" => $response]);
    }

    public function getDocument($documentId, Request $request)
    {
        $document = $this->signNow->getDocument($documentId);

        if ($document->ok()) {
            return response()->json($document->json());
        }

        return response()->json(["status" => $document->ok()]);
    }

    public function updateDocument($documentId, Request $request)
    {
        $fields = $request->input('fields', []);
        $elements = $request->input('elements', []);

        $document = $this->signNow->updateDocument($documentId, $fields, $elements, time());

        return response()->json(["status" => $document->ok()]);
    }

    public function deleteDocument($documentId, Request $request)
    {
        $document = $this->signNow->deleteDocument($documentId);

        return response()->json(["status" => $document->ok()]);
    }

    public function signLink($documentId, Request $request)
    {
        $response = $this->signNow->createSigningLink($documentId);

        return response()->json(["status" => $response->ok(), "data" => $response->json()]);
    }

    public function getSigningLinks(Request $request)
    {
        return $this->signNow->getSigningLinks();
    }

    public function createInvite($documentId, Request $request)
    {
        $recipients = $request->input('recipients');

        return $this->signNow->createInvite($documentId, $recipients, @$from_email);
    }
}
