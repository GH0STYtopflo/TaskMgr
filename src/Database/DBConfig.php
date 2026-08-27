<?php

namespace Gh0stytopflo\Taskmgr\Database;

use Exception;
use Gh0stytopflo\Taskmgr\Exception\InvalidSettingsException;
use JsonSerializable;
use PDO;
use Reflection;
use ReflectionClass;

class DBConfig implements JsonSerializable
{
    private string $dbms;
    private string $host;
    private int $port;
    private string $dbname;
    private string $username;
    private string $password;
    private array $options;

    private static array $optionsDictionary;

    /**
     * @param string $dbms
     * @param string $host
     * @param int $port
     * @param string $dbname
     * @param string $username
     * @param string $password
     * @param array $options
     * @param bool $translated
     */
    public function __construct(
        string $dbms,
        string $host,
        int    $port,
        string $dbname,
        string $username,
        string $password,
        array  $options,
        bool   $translated = false
    )
    {
        $this->dbms = $dbms;
        $this->host = $host;
        $this->port = $port;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;

        if ($translated) {
            $this->options = $options;
        } else {
            $this->options = self::translateOptions($options);
        }
    }

    // Translate strings into codes that pdo understands
    private static function translateOptions(array $options): array
    {
        $translatedOptions = [];

        foreach ($options as $option => $value) {
            if ($option == "ATTR_EMULATE_PREPARES") {
                $translatedOptions[self::getOptionsDictionary()[$option]] = $value;
                continue;
            }

            if (!isset(self::getOptionsDictionary()[$option])) {
                echo "invalid option $option\n";
                throw new InvalidSettingsException($option, line: __LINE__);
            }

            if (!isset(self::getOptionsDictionary()[$value])) {
                echo "invalid value $value\n";
                throw new InvalidSettingsException($value, line: __LINE__);
            }

            $trOption = self::getOptionsDictionary()[$option];
            $trValue = self::getOptionsDictionary()[$value];

            $translatedOptions[$trOption] = $trValue;
        }

        return $translatedOptions;
    }

    /**
     * Static method for turning associative arrays into config instances
     *
     * @param array $config
     * @return bool|self
     */
    public static function fromArray(array $config): self|bool
    {
        try {
            return new self(
                $config['dbms'],
                $config['host'],
                (int)$config['port'],
                $config['dbname'],
                $config['username'],
                $config['password'],
                $config['options'] ?? [],
                true
            );
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Static method for reading the config file located at
     * './config/dbconf.json' relative to the project root
     *
     * @return self|null
     */
    public static function readConfig(): self|null
    {
        $stream = fopen(__DIR__ . '/../../config/dbconf.json', 'r');
        $json = fread($stream, filesize(__DIR__ . '/../../config/dbconf.json'));

        $confObj = self::fromArray(json_decode($json, true));

        fclose($stream);

        if ($confObj === false) {
            return null;
        } else return $confObj;
    }

    private static function getOptionsDictionary(): array
    {
        return self::$optionsDictionary ??= (new ReflectionClass(PDO::class))->getConstants();
    }

    /**
     * Method for making a custom config persist. It stores configuration
     * at './config/dbconf.json' relative to the project root
     *
     * @return void
     */
    public function writeConfig(): void
    {
        $stream = fopen(__DIR__ . '/../../config/dbconf.json', 'w');
        $json = json_encode($this, JSON_PRETTY_PRINT);

        fwrite($stream, $json);

        fclose($stream);
    }

    public function getDbms(): string
    {
        return $this->dbms;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getDbname(): string
    {
        return $this->dbname;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }


}