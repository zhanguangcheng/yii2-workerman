<?php

namespace server;

use Exception;

class DotEnv
{
    protected array $env = [];

    /**
     * @var self[]
     */
    public static array $instances = [];

    /**
     * @throws Exception
     */
    public function load(string $filePath, string $templateFileExt = '.example'): array
    {
        if (!file_exists($filePath)) {
            if (!file_exists($filePath . $templateFileExt)) {
                throw new Exception("File $filePath not found");
            }
            if (!copy($filePath . $templateFileExt, $filePath)) {
                throw new Exception("Copy $filePath failed");
            }
        }
        $result = parse_ini_file($filePath);
        if (false === $result) {
            throw new Exception("Parse $filePath failed");
        }
        $this->env = $result;
        return $result;
    }

    public function get($key, $default = null): ?string
    {
        return $this->env[$key] ?? $default;
    }
}
