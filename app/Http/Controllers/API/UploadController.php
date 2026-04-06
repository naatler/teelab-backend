<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB
        ]);

        try {
            $file = $request->file('file');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            
            // Store in public/uploads
            $path = $file->storeAs('uploads', $filename, 'public');
            
            $url = asset('storage/' . $path);

            return response()->json([
                'message' => 'File uploaded successfully',
                'filename' => $filename,
                'url' => $url,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteImage(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
        ]);

        try {
            $path = 'uploads/' . $request->filename;
            
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                return response()->json(['message' => 'File deleted successfully']);
            }

            return response()->json(['message' => 'File not found'], 404);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete file',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}