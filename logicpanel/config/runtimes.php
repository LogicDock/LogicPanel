<?php
/**
 * LogicPanel - Runtime Configuration
 * Supported runtimes with Docker images and default commands
 */

return [
    'nodejs' => [
        'name' => 'Node.js',
        'versions' => [
            '18' => [
                'label' => 'Node.js 18 LTS',
                'image' => 'node:18-alpine',
                'install_cmd' => 'npm install',
                'build_cmd' => 'npm run build',
                'start_cmd' => 'npm start',
                'port' => 3000
            ],
            '20' => [
                'label' => 'Node.js 20 LTS',
                'image' => 'node:20-alpine',
                'install_cmd' => 'npm install',
                'build_cmd' => 'npm run build',
                'start_cmd' => 'npm start',
                'port' => 3000
            ],
            '22' => [
                'label' => 'Node.js 22',
                'image' => 'node:22-alpine',
                'install_cmd' => 'npm install',
                'build_cmd' => 'npm run build',
                'start_cmd' => 'npm start',
                'port' => 3000
            ]
        ]
    ],
    'python' => [
        'name' => 'Python',
        'versions' => [
            '3.10' => [
                'label' => 'Python 3.10',
                'image' => 'python:3.10-slim',
                'install_cmd' => 'pip install -r requirements.txt',
                'build_cmd' => '',
                'start_cmd' => 'python main.py',
                'port' => 8000
            ],
            '3.11' => [
                'label' => 'Python 3.11',
                'image' => 'python:3.11-slim',
                'install_cmd' => 'pip install -r requirements.txt',
                'build_cmd' => '',
                'start_cmd' => 'python main.py',
                'port' => 8000
            ],
            '3.12' => [
                'label' => 'Python 3.12',
                'image' => 'python:3.12-slim',
                'install_cmd' => 'pip install -r requirements.txt',
                'build_cmd' => '',
                'start_cmd' => 'python main.py',
                'port' => 8000
            ]
        ]
    ],
    'java' => [
        'name' => 'Java',
        'versions' => [
            '17' => [
                'label' => 'Java 17 LTS',
                'image' => 'openjdk:17-slim',
                'install_cmd' => './mvnw install',
                'build_cmd' => './mvnw package',
                'start_cmd' => 'java -jar target/*.jar',
                'port' => 8080
            ],
            '21' => [
                'label' => 'Java 21 LTS',
                'image' => 'openjdk:21-slim',
                'install_cmd' => './mvnw install',
                'build_cmd' => './mvnw package',
                'start_cmd' => 'java -jar target/*.jar',
                'port' => 8080
            ]
        ]
    ],
    'rust' => [
        'name' => 'Rust',
        'versions' => [
            'latest' => [
                'label' => 'Rust (Latest)',
                'image' => 'rust:latest',
                'install_cmd' => '',
                'build_cmd' => 'cargo build --release',
                'start_cmd' => './target/release/app',
                'port' => 8000
            ]
        ]
    ],
    'go' => [
        'name' => 'Go',
        'versions' => [
            '1.21' => [
                'label' => 'Go 1.21',
                'image' => 'golang:1.21-alpine',
                'install_cmd' => 'go mod download',
                'build_cmd' => 'go build -o app',
                'start_cmd' => './app',
                'port' => 8080
            ],
            '1.22' => [
                'label' => 'Go 1.22',
                'image' => 'golang:1.22-alpine',
                'install_cmd' => 'go mod download',
                'build_cmd' => 'go build -o app',
                'start_cmd' => './app',
                'port' => 8080
            ]
        ]
    ],
    'php' => [
        'name' => 'PHP',
        'versions' => [
            '8.2' => [
                'label' => 'PHP 8.2',
                'image' => 'php:8.2-fpm-alpine',
                'install_cmd' => 'composer install',
                'build_cmd' => '',
                'start_cmd' => 'php -S 0.0.0.0:8000',
                'port' => 8000
            ],
            '8.3' => [
                'label' => 'PHP 8.3',
                'image' => 'php:8.3-fpm-alpine',
                'install_cmd' => 'composer install',
                'build_cmd' => '',
                'start_cmd' => 'php -S 0.0.0.0:8000',
                'port' => 8000
            ]
        ]
    ]
];
