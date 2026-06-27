<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MscTipo;
use App\Http\Requests\Msc\UploadRequest;
use App\Models\MscUpload;
use App\Models\User;
use App\Services\MscDashboardService;
use App\Services\MscUploadFormatter;
use App\Services\MscValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final class MscUploadController extends Controller
{
    public function __construct(
        private readonly MscValidationService $mscValidationService,
        private readonly MscDashboardService $mscDashboardService,
    ) {}

    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        return response()->json($this->mscDashboardService->getDashboardForUser($user));
    }

    public function show(MscUpload $upload): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        if ($upload->user_id !== $user->id) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $upload->load('validationErrors');

        return response()->json([
            'upload' => MscUploadFormatter::format($upload),
            'errors' => MscUploadFormatter::formatValidationErrors($upload),
        ]);
    }

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
