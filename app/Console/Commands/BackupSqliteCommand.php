<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupSqliteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-sqlite';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a safe hot backup of the SQLite database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dbPath = database_path('database.sqlite');
        $backupDir = storage_path('app/backups');

        if (!file_exists($dbPath)) {
            $this->error('Database file not found at: ' . $dbPath);
            return;
        }

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . '/database_backup_' . $timestamp . '.sqlite';

        // Intentar usar sqlite3 nativo para backup seguro en caliente
        $sqliteCmd = "sqlite3 {$dbPath} \".backup '{$backupFile}'\" 2>&1";
        exec($sqliteCmd, $output, $returnVar);

        if ($returnVar === 0) {
            $this->info("Backup completed successfully! Saved to: {$backupFile}");
        } else {
            // Fallback a una copia manual (puede tener problemas si hay escrituras simultáneas)
            $this->warn("sqlite3 command failed or not found. Falling back to copy().");
            if (copy($dbPath, $backupFile)) {
                $this->info("Fallback copy completed successfully! Saved to: {$backupFile}");
            } else {
                $this->error("Failed to copy database file.");
            }
        }
        
        // Retener solo los últimos 7 backups (ejemplo)
        $this->cleanOldBackups($backupDir, 7);
    }
    
    protected function cleanOldBackups(string $dir, int $keepCount): void
    {
        $files = glob($dir . '/database_backup_*.sqlite');
        if (count($files) > $keepCount) {
            usort($files, function($a, $b) {
                return filemtime($a) <=> filemtime($b);
            });
            
            $toDelete = array_slice($files, 0, count($files) - $keepCount);
            foreach ($toDelete as $file) {
                unlink($file);
                $this->info("Deleted old backup: " . basename($file));
            }
        }
    }
}
