<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MscTipo;
use App\Http\Requests\Msc\UploadRequest;
use App\Models\User;
use App\Services\MscValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

final class MscUploadController extends Controller
{
    public function __construct(
        private readonly MscValidationService $mscValidationService,
    ) {}

    public function store(UploadRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var UploadedFile $file */
        $file = $request->file('file');

        $payload = $this->mscValidationService->processUpload(
            $user,
            $file,
            $request->string('periodo')->toString(),
            MscTipo::from($request->string('tipo_msc')->toString()),
        );

        return response()->json($payload, JsonResponse::HTTP_CREATED);
    }
}
