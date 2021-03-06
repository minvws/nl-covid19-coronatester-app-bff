<?php

namespace App\Http\Middleware;

use App\Services\CMSSignatureService;
use Illuminate\Http\Request;
use Closure;

class CMSSignature
{
    private CMSSignatureService $cmsSignatureService;

    public function __construct(CMSSignatureService $cmsSignatureService) {
        $this->cmsSignatureService = $cmsSignatureService;
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        $data = trim($response->getContent());
        $signature = $this->cmsSignatureService->signData($data);

        if(config('app.signature_format') == "inline") {
            $response->setData(["signature" => $signature, "payload" => base64_encode($data)]);
            $response->header('x-vws-signed','True');
            return $response;
        }
        else if(config('app.signature_format') == "inline-double") {
            $response->setData(
                ["signature" => $signature, "payload" => base64_encode($data), "_payload" => $data]
            );
            $response->header('x-vws-signed','True');
            return $response;
        }
        else if(config('app.signature_format') == "header") {
            return $response
                ->header('x-vws-signed','True')
                ->header('Signature', $signature);
        }
        else {
            return $response->header('x-vws-signed','False');
        }
    }

}
