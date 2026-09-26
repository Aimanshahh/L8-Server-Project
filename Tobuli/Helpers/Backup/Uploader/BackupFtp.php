<?php

namespace Tobuli\Helpers\Backup\Uploader;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Tobuli\Entities\Backup;
use Tobuli\Entities\BackupProcess;
use Tobuli\Entities\TraccarDevice;
use Tobuli\Helpers\Backup\Process\DatabaseBackuper;
use Tobuli\Helpers\Backup\Process\DevicesPositionsBackuper;
use Tobuli\Helpers\Backup\Process\FilesBackuper;
use Tobuli\Helpers\Backup\FileMeta;

class BackupFtp implements BackupUploaderInterface
{
    protected static array $devicePositionsSubfolders;

    protected $host;
    protected $user;
    protected $pass;
    protected $port;
    protected $path;

    /** @var resource|false */
    private $conn;

    public function __construct($host, $user, $pass, $port, $path)
    {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->port = $port;
        $this->path = rtrim($path, '/') . '/';
    }

    public function getHost()
    {
        return $this->host;
    }

    public function check(): bool
    {
        return $this->getConnection() !== false;
    }

    /**
     * @return resource|false
     */
    private function getConnection()
    {
        if (isset($this->conn)) {
            return $this->conn;
        }

        $connection = ftp_connect($this->host, $this->port, 30);

        if ($connection === false) {
            return $this->conn = false;
        }

        $this->conn = @ftp_login($connection, $this->user, $this->pass) ? $connection : false;

        if ($this->conn) {
            @ftp_pasv($this->conn, true);
        }

        return $this->conn;
    }

    public function testCommand()
    {
        $filename = time() . 'test.txt';
        $command = "ncftpput -m -c -u '{$this->user}' -p '{$this->pass}' -P {$this->port} {$this->host} {$this->path}$filename";

        $this->run('echo "test"' . " | $command");
    }

    public function process($commands, BackupProcess $process, $item, bool $gzip = true)
    {
        $this->run($this->buildBackupCommand($commands, $process, $item, $gzip));
    }

    protected function buildBackupCommand($commands, BackupProcess $process, $item, bool $gzip = true): string
    {
        $filename = $this->resolveItemFilename($process, $item);
        $filename = self::getRootPath($process->backup) . $filename;

        if (is_string($commands)) {
            $commands = [$commands];
        }

        if ($gzip) {
            $filename .= '.gz';
            $commands[] = 'gzip -9';
        }

        $commands[] = "ncftpput -m -c -u '{$this->user}' -p '{$this->pass}' -P {$this->port} {$this->host} {$this->path}$filename";

        return implode(' | ', $commands);
    }

    protected function resolveItemFilename(BackupProcess $process, $item): string
    {
        switch ($process->type) {
            case FilesBackuper::class:
                return basename($item) . '.tar';
            case DatabaseBackuper::class:
                return "{$item['host']}-{$item['database']}-db.sql";
            case DevicesPositionsBackuper::class:
                return self::getTraccarFilename($item);
            default:
                throw new \InvalidArgumentException('Unsupported backup process type: ' . $process->type);
        }
    }

    protected function run($command)
    {
        $process = Process::fromShellCommandline($command);
        $process->start();

        while ($process->isRunning()) {
            // waiting for process to finish
        }

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    public static function getTraccarFilename(TraccarDevice $device): string
    {
        $table = $device->positions()->getRelated()->getTable();

        return self::getDevicePositionsRemoteDir($device->id) . $table . '.sql';
    }

    /**
     * if id = 312456, $path = <...>/10000000/1000000/400000/20000/3000/500/
     */
    public static function getDevicePositionsRemoteDir($id): string
    {
        $path = '';
        $dirDecimals = self::getDevicePositionsSubfolders();

        foreach ($dirDecimals as $i) {
            $path .= (ceil($id / $i) % 10 * $i) . '/';
        }

        return $path;
    }

    protected static function getDevicePositionsSubfolders(): array
    {
        if (isset(self::$devicePositionsSubfolders)) {
            return self::$devicePositionsSubfolders;
        }

        $decimal = '100';
        $dirDecimals = [];

        while (strlen($decimal) <= 10) { // avoiding 20-digit numbers, because they are stored as 1.0E+19
            array_unshift($dirDecimals, $decimal);
            $decimal .= '0';
        }

        return self::$devicePositionsSubfolders = $dirDecimals;
    }

    /**
     * @param  Backup|string  $backupDate
     */
    public static function getRootPath($backupDate): string
    {
        if ($backupDate instanceof Backup) {
            $backupDate = $backupDate->created_at;
        }

        $date = \Carbon::parse($backupDate)->format('Y-m-d');
        $timestamp = \Carbon::parse($backupDate)->timestamp;

        return "backup_$date-$timestamp/";
    }

    public function findBackupFolders(): array
    {
        $conn = $this->getConnection();

        $list = ftp_rawlist($conn, $this->path . 'backup_*') ?: [];

        return $this->parseRawFileList($list, $this->path);
    }

    public function findFiles(string $pathPattern): array
    {
        $conn = $this->getConnection();

        $list = ftp_rawlist($conn, $pathPattern);
        $dir = dirname($pathPattern);

        return $this->parseRawFileList($list, $dir);
    }

    public function findFirstFile(string $pathPattern): ?FileMeta
    {
        $files = $this->findFiles($pathPattern);

        return $files[0] ?? null;
    }

    /**
     * @param string|array $rows
     * @return FileMeta[]
     */
    private function parseRawFileList($rows, string $dir): array
    {
        if (is_string($rows)) {
            $rows = [$rows];
        }

        return array_map(fn ($row) => FileMeta::fromFtpRaw($row, $dir), $rows);
    }

    public function downloadFile(string $path): string
    {
        $conn = $this->getConnection();

        $localPath = storage_path('cache/' . basename($path));

        ftp_get($conn, $localPath, $path);

        return $localPath;
    }
}