<?php
/**
 * LogicPanel - Auto Migration Service
 * Automatically runs database migrations on first boot or version upgrade
 */

namespace LogicPanel\Services;

use Illuminate\Database\Capsule\Manager as DB;

class MigrationService
{
    private string $migrationsPath;
    private string $migrationTable = 'migrations';

    public function __construct()
    {
        $this->migrationsPath = dirname(__DIR__, 2) . '/database/migrations';
    }

    /**
     * Check and run pending migrations
     */
    public function runPendingMigrations(): array
    {
        $results = [];

        try {
            // Ensure migrations table exists
            $this->ensureMigrationsTable();

            // Get already run migrations
            $ran = $this->getRanMigrations();

            // Get all migration files
            $files = $this->getMigrationFiles();

            // Run pending migrations
            foreach ($files as $file) {
                $migrationName = basename($file, '.sql');

                if (!in_array($migrationName, $ran)) {
                    $result = $this->runMigration($file, $migrationName);
                    $results[] = $result;
                }
            }

        } catch (\Exception $e) {
            $results[] = [
                'migration' => 'error',
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        return $results;
    }

    /**
     * Ensure migrations tracking table exists
     */
    private function ensureMigrationsTable(): void
    {
        $tableExists = DB::select("SHOW TABLES LIKE 'lp_migrations'");

        if (empty($tableExists)) {
            DB::statement("
                CREATE TABLE `lp_migrations` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `migration` VARCHAR(255) NOT NULL UNIQUE,
                    `batch` INT NOT NULL DEFAULT 1,
                    `ran_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }

    /**
     * Get list of already run migrations
     */
    private function getRanMigrations(): array
    {
        try {
            return DB::table('migrations')
                ->pluck('migration')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get all migration files sorted by name
     */
    private function getMigrationFiles(): array
    {
        $files = [];

        // Get schema.sql first (base schema)
        $schemaFile = dirname($this->migrationsPath) . '/schema.sql';
        if (file_exists($schemaFile)) {
            $files['000_schema'] = $schemaFile;
        }

        // Get migration files
        if (is_dir($this->migrationsPath)) {
            $migrationFiles = glob($this->migrationsPath . '/*.sql');
            foreach ($migrationFiles as $file) {
                $name = basename($file, '.sql');
                $files[$name] = $file;
            }
        }

        // Sort by name (migration prefix numbers ensure order)
        ksort($files);

        return $files;
    }

    /**
     * Run a single migration file
     */
    private function runMigration(string $filePath, string $migrationName): array
    {
        try {
            $sql = file_get_contents($filePath);
            if (empty($sql)) {
                throw new \Exception('Empty migration file');
            }

            // Split by semicolon for multiple statements
            $statements = $this->splitSqlStatements($sql);

            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && !$this->isComment($statement)) {
                    DB::statement($statement);
                }
            }

            // Record migration as complete
            DB::table('migrations')->insert([
                'migration' => $migrationName,
                'batch' => $this->getNextBatch()
            ]);

            return [
                'migration' => $migrationName,
                'success' => true
            ];

        } catch (\Exception $e) {
            return [
                'migration' => $migrationName,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Split SQL into individual statements
     */
    private function splitSqlStatements(string $sql): array
    {
        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        // Split by semicolon (not inside quotes)
        $statements = preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql);

        return array_filter($statements, function ($s) {
            return !empty(trim($s));
        });
    }

    /**
     * Check if statement is just a comment
     */
    private function isComment(string $statement): bool
    {
        $statement = trim($statement);
        return strpos($statement, '--') === 0 || strpos($statement, '/*') === 0;
    }

    /**
     * Get next batch number
     */
    private function getNextBatch(): int
    {
        try {
            $maxBatch = DB::table('migrations')->max('batch');
            return ($maxBatch ?? 0) + 1;
        } catch (\Exception $e) {
            return 1;
        }
    }

    /**
     * Check if database is initialized
     */
    public static function isDatabaseInitialized(): bool
    {
        try {
            $tables = DB::select("SHOW TABLES LIKE 'lp_users'");
            return !empty($tables);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create initial admin user if not exists
     */
    public function createInitialAdmin(string $username, string $email, string $password): bool
    {
        try {
            // Check if any admin exists
            $adminExists = DB::table('users')
                ->where('role', 'admin')
                ->exists();

            if (!$adminExists) {
                DB::table('users')->insert([
                    'username' => $username,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                    'name' => 'Administrator',
                    'role' => 'admin',
                    'is_active' => true,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            error_log('Failed to create admin: ' . $e->getMessage());
            return false;
        }
    }
}
