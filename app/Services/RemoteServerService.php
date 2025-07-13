<?php

namespace App\Services;

use DivineOmega\SSHConnection\SSHConnection;
use Exception;

class RemoteServerService
{
    protected ?SSHConnection $connection = null;

    protected ?string $lastOutput = null;

    protected ?string $lastError = null;

    /**
     * @param string $host
     * @param string $username
     * @param string|null $password
     * @param string|null $privateKey
     * @param int $port
     * @param int $timeout
     * @param string|null $expectedFingerprint
     * @param string $fingerprintType
     * @return $this
     * @throws Exception
     */
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
            $this->connection = (new SSHConnection)
                ->to($host)
                ->onPort($port)
                ->as($username);

            if ($privateKey) {
                $this->connection->withPrivateKey($privateKey);
            } elseif ($password) {
                $this->connection->withPassword($password);
            } else {
                throw new Exception('Provide password or private key.');
            }

            $this->connection->timeout($timeout)->connect();

            // Fingerprint validation
            if ($expectedFingerprint) {
                $actual = $this->connection->fingerprint($fingerprintType);
                if ($actual !== $expectedFingerprint) {
                    throw new Exception('SSH fingerprint mismatch!');
                }
            }

        } catch (Exception $e) {
            throw new Exception('SSH connection failed: '.$e->getMessage());
        }

        return $this;
    }

    /**
     * @param string $command
     * @return $this
     * @throws Exception
     */
    public function execute(string $command): static
    {
        $this->lastOutput = null;
        $this->lastError = null;

        if (! $this->connection) {
            throw new Exception('No SSH connection established.');
        }

        $result = $this->connection->run($command);

        $this->lastOutput = $result->getOutput();
        $this->lastError = $result->getError();

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOutput(): ?string
    {
        return $this->lastOutput;
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->lastError;
    }

    /**
     * @param string $command
     * @return string
     * @throws Exception
     */
    public function startService(string $command): string
    {
        return $this->execute($command)->getOutput();
    }

    /**
     * @param string $command
     * @return string
     * @throws Exception
     */
    public function stopService(string $command): string
    {
        return $this->execute($command)->getOutput();
    }

    /**
     * @param string $command
     * @return string
     * @throws Exception
     */
    public function getServiceStatus(string $command): string
    {
        return $this->execute($command)->getOutput();
    }

    /**
     * @param string $localPath
     * @param string $remotePath
     * @return bool
     * @throws Exception
     */
    public function upload(string $localPath, string $remotePath): bool
    {
        if (! $this->connection) {
            throw new Exception('No SSH connection established.');
        }

        return $this->connection->upload($localPath, $remotePath);
    }

    /**
     * @param string $remotePath
     * @param string $localPath
     * @return bool
     * @throws Exception
     */
    public function download(string $remotePath, string $localPath): bool
    {
        if (! $this->connection) {
            throw new Exception('No SSH connection established.');
        }

        return $this->connection->download($remotePath, $localPath);
    }

    /**
     * @param string $type
     * @return string
     * @throws Exception
     */
    public function getFingerprint(string $type = SSHConnection::FINGERPRINT_MD5): string
    {
        if (! $this->connection) {
            throw new Exception('No SSH connection established.');
        }

        return $this->connection->fingerprint($type);
    }
}
