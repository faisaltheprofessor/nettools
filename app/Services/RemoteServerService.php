<?php
namespace App\Services;

use DivineOmega\SSHConnection\SSHConnection;
use DivineOmega\SSHConnection\Exceptions\SSHConnectionException;
use Exception;

class RemoteServerService
{
    protected ?SSHConnection $connection = null;
    protected ?string $lastOutput = null;
    protected ?string $lastError = null;

    public function connect(
        string $host,
        string $username,
        ?string $password = null,
        ?string $privateKey = null,
        int $port = 22,
        int $timeout = 10,
        ?string $expectedFingerprint = null,
        string $fingerprintType = SSHConnection::FINGERPRINT_MD5
    ): static {
        try {
            $this->connection = (new SSHConnection())
                ->to($host)
                ->onPort($port)
                ->as($username);

            if ($privateKey) {
                $this->connection->withPrivateKey($privateKey);
            } elseif ($password) {
                $this->connection->withPassword($password);
            } else {
                throw new Exception("Provide password or private key.");
            }

            $this->connection->timeout($timeout)->connect();

            // Fingerprint validation
            if ($expectedFingerprint) {
                $actual = $this->connection->fingerprint($fingerprintType);
                if ($actual !== $expectedFingerprint) {
                    throw new Exception("SSH fingerprint mismatch!");
                }
            }

        } catch (SSHConnectionException | Exception $e) {
            throw new Exception("SSH connection failed: " . $e->getMessage());
        }

        return $this;
    }

    public function execute(string $command): static
    {
        $this->lastOutput = null;
        $this->lastError = null;

        if (!$this->connection) {
            throw new Exception("No SSH connection established.");
        }

        $result = $this->connection->run($command);

        $this->lastOutput = $result->getOutput();
        $this->lastError = $result->getError();

        return $this;
    }

    public function getOutput(): ?string
    {
        return $this->lastOutput;
    }

    public function getError(): ?string
    {
        return $this->lastError;
    }

    public function startService(string $command): string
    {
        return $this->execute($command)->getOutput();
    }

    public function stopService(string $command): string
    {
        return $this->execute($command)->getOutput();
    }

    public function getServiceStatus(string $command): string
    {
        return $this->execute($command)->getOutput();
    }

    public function upload(string $localPath, string $remotePath): bool
    {
        if (!$this->connection) {
            throw new Exception("No SSH connection established.");
        }

        return $this->connection->upload($localPath, $remotePath);
    }

    public function download(string $remotePath, string $localPath): bool
    {
        if (!$this->connection) {
            throw new Exception("No SSH connection established.");
        }

        return $this->connection->download($remotePath, $localPath);
    }

    public function getFingerprint(string $type = SSHConnection::FINGERPRINT_MD5): string
    {
        if (!$this->connection) {
            throw new Exception("No SSH connection established.");
        }

        return $this->connection->fingerprint($type);
    }
}
