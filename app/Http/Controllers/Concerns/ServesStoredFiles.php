<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ServesStoredFiles
{
    protected function serveLocalFile(Request $request, string $path, string $filename): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($path), 404);

        if ($request->boolean('download')) {
            return Storage::disk('local')->download($path, $filename);
        }

        return Storage::disk('local')->response($path, $filename);
    }
}
