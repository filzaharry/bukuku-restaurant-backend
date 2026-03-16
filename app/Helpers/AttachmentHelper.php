<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentHelper
{
    /**
     * Handle file attachment (upload/update/delete)
     *
     * @param UploadedFile|null $file        // File dari form-data
     * @param string $folder                 // Target folder (misal: 'fnb/images')
     * @param string|null $existingFilePath  // Path file lama (jika update/delete)
     * @param string $action                 // create, update, or delete
     * @return array|null                    // Informasi file (filename, path, etc)
     */
    public static function handleAttachment(?UploadedFile $file, string $folder, ?string $existingFilePath = null, string $action = 'create'): ?array
    {
        try {
            // Hapus file lama jika ada dan aksi update/delete
            if (in_array($action, ['update', 'delete']) && $existingFilePath && Storage::disk('public')->exists($existingFilePath)) {
                Storage::disk('public')->delete($existingFilePath);
            }

            // Jika delete saja, tidak upload baru
            if ($action === 'delete') {
                return null;
            }

            // Jika file baru tidak diberikan, kembalikan null
            if (!$file instanceof UploadedFile) {
                return null;
            }

            // Simpan file ke folder yang ditentukan di disk 'public'
            $storedPath = $file->store($folder, 'public');

            // Buat response metadata file
            return [
                'file_name'   => basename($storedPath),
                'path'        => $storedPath,
                'size'        => $file->getSize(),
                'file_format' => $file->getClientOriginalExtension(),
            ];
        } catch (\Exception $e) {
            // Handle error log kalau perlu
            return null;
        }
    }

    public static function deleteAttachment($path)
    {
        if (Storage::exists($path)) {
            Storage::delete($path);
        }
    }
}
