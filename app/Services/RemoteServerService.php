<?php

namespace App\Services;

use DivineOmega\SSHConnection\SSHConnection;
use Exception;

/**
 * Service for managing SSH connections and remote server operations.
 */
class RemoteServerService
{
    /**
     * The current SSH connection instance.
     */
    protected ?SSHConnection $connection = null;

    /**
     * The output from the last executed SSH command.
     */
    protected ?string $lastOutput = null;

    /**
     * The error output from the last executed SSH command.
     */
    protected ?string $lastError = null;

    /**
     * Establish an SSH connection to the remote server.
     *
     * @param string $host The hostname or IP address of the server.
     * @param string $username The SSH username.
     * @param string|null $password The SSH password (optional).
     * @param string|null $privateKey The private key path or contents (optional).
     * @param int $port The SSH port (default is 22).
     * @param int $timeout Connection timeout in seconds (default is 10).
     * @param string|null $expectedFingerprint Expected fingerprint of the remote server (optional).
     * @param string $fingerprintType Fingerprint type (e.g., md5, sha256).
     * @return $this
     *
     * @throws Exception If connection fails or authentication is not provided.
     */
    public function connect(
        string  $host,
        string  $username,
        ?string $password = null,
        ?string $privateKey = null,
        int     $port = 22,
        int     $timeout = 10,
        ?string $expectedFingerprint = null,
        string  $fingerprintType = SSHConnection::FINGERPRINT_MD5
    ): static
    {
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

            if ($expectedFingerprint) {
                $actual = $this->connection->fingerprint($fingerprintType);
                if ($actual !== $expectedFingerprint) {
                    throw new Exception('SSH fingerprint mismatch!');
                }
            }

        } catch (Exception $e) {
            throw new Exception('SSH connection failed: ' . $e->getMessage());
        }

        return $this;
    }

    /**
     * Start a service on the remote server using a command.
     *
     * @param string $command The command to start the service.
     * @return string Output of the executed command.
     *
     * @throws Exception
     */
    public function startService(string $command): string
    {
        return $this->execute($command)->getOutput();
    }

    /**
     * Get the output of the last executed command.
     */
    public function getOutput(): ?string
    {
        return $this->lastOutput;
    }

    /**
     * Run a shell command on the remote server.
     *
     * @param string $command The command to execute.
     * @return $this
     *
     * @throws Exception If connection is not established.
     */
    public function execute(string $command): static
    {
        $this->lastOutput = null;
        $this->lastError = null;

        if (!$this->connection) {
            throw new Exception('No SSH connection established.');
        }

        $result = $this->connection->run($command);

        $this->lastOutput = $result->getOutput();
        $this->lastError = $result->getError();

        return $this;
    }

    /**
     * Get the error output of the last executed command.
     */
    public function getError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Stop a service on the remote server using a command.
     *
     * @param string $command The command to stop the service.
     * @return string Output of the executed command.
     *
     * @throws Exception
     */
    public function stopService(string $command): string
    {
        return $this->execute($command)->getOutput();
    }

    /**
     * Get the status of a remote service using a command.
     *
     * @param string $command The command to check the service status.
     * @return string Output of the executed command.
     *
     * @throws Exception
     */
    public function getServiceStatus(string $command): string
    {
        return $this->execute($command)->getOutput();
    }

    /**
     * Upload a local file to the remote server.
     *
     * @param string $localPath Full path of the local file.
     * @param string $remotePath Destination path on the remote server.
     * @return bool True on success, false otherwise.
     *
     * @throws Exception If no connection is established.
     */
    public function upload(string $localPath, string $remotePath): bool
    {
        if (!$this->connection) {
            throw new Exception('No SSH connection established.');
        }

        return $this->connection->upload($localPath, $remotePath);
    }

    /**
     * Download a file from the remote server.
     *
     * @param string $remotePath Path of the file on the remote server.
     * @param string $localPath Destination path on the local machine.
     * @return bool True on success, false otherwise.
     *
     * @throws Exception If no connection is established.
     */
    public function download(string $remotePath, string $localPath): bool
    {
        if (!$this->connection) {
            throw new Exception('No SSH connection established.');
        }

        return $this->connection->download($remotePath, $localPath);
    }

    /**
     * Get the fingerprint of the remote server.
     *
     * @param string $type The fingerprint type (e.g., md5, sha256).
     * @return string The fingerprint string.
     *
     * @throws Exception If no connection is established.
     */
    public function getFingerprint(string $type = SSHConnection::FINGERPRINT_MD5): string
    {
        if (!$this->connection) {
            throw new Exception('No SSH connection established.');
        }

        return $this->connection->fingerprint($type);
    }
}
