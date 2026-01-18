<?php
/**
 * LogicPanel - Language Service
 * Handles multi-language application detection, base images, and build commands
 */

namespace LogicPanel\Services;

class LanguageService
{
    /**
     * Supported languages with their configurations
     */
    private static array $languages = [
        'nodejs' => [
            'name' => 'Node.js',
            'detect_files' => ['package.json'],
            'base_images' => [
                '22' => 'node:22-alpine',
                '20' => 'node:20-alpine',
                '18' => 'node:18-alpine',
                'default' => 'node:20-alpine'
            ],
            'default_version' => '20',
            'install_cmd' => 'npm install',
            'build_cmd' => 'npm run build --if-present',
            'start_cmd' => 'npm start',
            'package_manager_detect' => [
                'yarn.lock' => 'yarn',
                'pnpm-lock.yaml' => 'pnpm',
                'package-lock.json' => 'npm'
            ],
            'port' => 3000
        ],
        'python' => [
            'name' => 'Python',
            'detect_files' => ['requirements.txt', 'Pipfile', 'pyproject.toml'],
            'base_images' => [
                '3.12' => 'python:3.12-slim',
                '3.11' => 'python:3.11-slim',
                '3.10' => 'python:3.10-slim',
                'default' => 'python:3.12-slim'
            ],
            'default_version' => '3.12',
            'install_cmd' => 'pip install -r requirements.txt',
            'build_cmd' => '',
            'start_cmd' => 'python app.py',
            'port' => 8000
        ],
        'rust' => [
            'name' => 'Rust',
            'detect_files' => ['Cargo.toml'],
            'base_images' => [
                '1.75' => 'rust:1.75-slim',
                '1.74' => 'rust:1.74-slim',
                'default' => 'rust:1.75-slim'
            ],
            'default_version' => '1.75',
            'install_cmd' => '',
            'build_cmd' => 'cargo build --release',
            'start_cmd' => './target/release/${APP_NAME}',
            'port' => 8080
        ],
        'java' => [
            'name' => 'Java',
            'detect_files' => ['pom.xml', 'build.gradle', 'build.gradle.kts'],
            'base_images' => [
                '21' => 'eclipse-temurin:21-jdk',
                '17' => 'eclipse-temurin:17-jdk',
                '11' => 'eclipse-temurin:11-jdk',
                'default' => 'eclipse-temurin:21-jdk'
            ],
            'default_version' => '21',
            'install_cmd' => '',
            'build_cmd' => 'mvn package -DskipTests',
            'start_cmd' => 'java -jar target/*.jar',
            'build_tool_detect' => [
                'pom.xml' => 'maven',
                'build.gradle' => 'gradle'
            ],
            'port' => 8080
        ],
        'php' => [
            'name' => 'PHP',
            'detect_files' => ['composer.json', 'index.php'],
            'base_images' => [
                '8.3' => 'php:8.3-fpm',
                '8.2' => 'php:8.2-fpm',
                '8.1' => 'php:8.1-fpm',
                'default' => 'php:8.3-fpm'
            ],
            'default_version' => '8.3',
            'install_cmd' => 'composer install --no-dev',
            'build_cmd' => '',
            'start_cmd' => 'php -S 0.0.0.0:8000 -t public',
            'port' => 8000
        ],
        'go' => [
            'name' => 'Go',
            'detect_files' => ['go.mod'],
            'base_images' => [
                '1.22' => 'golang:1.22-alpine',
                '1.21' => 'golang:1.21-alpine',
                'default' => 'golang:1.22-alpine'
            ],
            'default_version' => '1.22',
            'install_cmd' => 'go mod download',
            'build_cmd' => 'go build -o app .',
            'start_cmd' => './app',
            'port' => 8080
        ],
        'ruby' => [
            'name' => 'Ruby',
            'detect_files' => ['Gemfile'],
            'base_images' => [
                '3.3' => 'ruby:3.3-slim',
                '3.2' => 'ruby:3.2-slim',
                'default' => 'ruby:3.3-slim'
            ],
            'default_version' => '3.3',
            'install_cmd' => 'bundle install',
            'build_cmd' => '',
            'start_cmd' => 'ruby app.rb',
            'port' => 4567
        ],
        'static' => [
            'name' => 'Static Files',
            'detect_files' => ['index.html'],
            'base_images' => [
                'default' => 'nginx:alpine'
            ],
            'default_version' => 'latest',
            'install_cmd' => '',
            'build_cmd' => '',
            'start_cmd' => 'nginx -g "daemon off;"',
            'port' => 80
        ]
    ];

    /**
     * Detect language from file list
     */
    public static function detectLanguage(array $files): ?string
    {
        // Check each language's detection files
        foreach (self::$languages as $langKey => $config) {
            foreach ($config['detect_files'] as $detectFile) {
                if (in_array($detectFile, $files)) {
                    return $langKey;
                }
            }
        }
        return null;
    }

    /**
     * Get language configuration
     */
    public static function getLanguageConfig(string $language): ?array
    {
        return self::$languages[$language] ?? null;
    }

    /**
     * Get all supported languages
     */
    public static function getSupportedLanguages(): array
    {
        $result = [];
        foreach (self::$languages as $key => $config) {
            $result[$key] = [
                'name' => $config['name'],
                'versions' => array_keys(array_filter(
                    $config['base_images'],
                    fn($k) => $k !== 'default',
                    ARRAY_FILTER_USE_KEY
                )),
                'default_version' => $config['default_version'] ?? 'latest',
                'port' => $config['port'] ?? 3000
            ];
        }
        return $result;
    }

    /**
     * Get base image for language and version
     */
    public static function getBaseImage(string $language, ?string $version = null): ?string
    {
        $config = self::$languages[$language] ?? null;
        if (!$config)
            return null;

        $images = $config['base_images'];
        if ($version && isset($images[$version])) {
            return $images[$version];
        }
        return $images['default'] ?? null;
    }

    /**
     * Get build commands for language
     */
    public static function getBuildCommands(string $language): array
    {
        $config = self::$languages[$language] ?? null;
        if (!$config)
            return [];

        return [
            'install' => $config['install_cmd'] ?? '',
            'build' => $config['build_cmd'] ?? '',
            'start' => $config['start_cmd'] ?? ''
        ];
    }

    /**
     * Get default port for language
     */
    public static function getDefaultPort(string $language): int
    {
        $config = self::$languages[$language] ?? null;
        return $config['port'] ?? 3000;
    }

    /**
     * Detect package manager for Node.js
     */
    public static function detectPackageManager(array $files): string
    {
        $nodeConfig = self::$languages['nodejs'];
        foreach ($nodeConfig['package_manager_detect'] as $file => $manager) {
            if (in_array($file, $files)) {
                return $manager;
            }
        }
        return 'npm';
    }

    /**
     * Get install command with correct package manager
     */
    public static function getInstallCommand(string $language, array $files = []): string
    {
        $config = self::$languages[$language] ?? null;
        if (!$config)
            return '';

        if ($language === 'nodejs') {
            $pm = self::detectPackageManager($files);
            switch ($pm) {
                case 'yarn':
                    return 'yarn install';
                case 'pnpm':
                    return 'pnpm install';
                default:
                    return 'npm install';
            }
        }

        return $config['install_cmd'] ?? '';
    }
}
