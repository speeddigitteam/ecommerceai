<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateAiContentRequest;
use App\Services\OpenAiContentGenerator;
use Illuminate\Http\JsonResponse;

class AiContentController extends Controller
{
    public function __invoke(GenerateAiContentRequest $request, OpenAiContentGenerator $generator): JsonResponse
    {
        return response()->json(['content' => $generator->generate($request->validated())]);
    }
}
