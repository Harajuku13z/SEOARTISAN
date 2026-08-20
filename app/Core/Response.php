<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /** @param array<string,string> $headers */
    public function __construct(
        private string $body = '',
        private int $status = 200,
        private array $headers = []
    ) {
    }

    public static function html(string $content, int $status = 200): self
    {
        return (new self($content, $status))->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public static function json(mixed $data, int $status = 200): self
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return (new self($json === false ? '{}' : $json, $status))
            ->withHeader('Content-Type', 'application/json; charset=UTF-8');
    }

    public static function xml(string $content, int $status = 200): self
    {
        return (new self($content, $status))->withHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    public static function text(string $content, int $status = 200): self
    {
        return (new self($content, $status))->withHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    public static function redirect(string $to, int $status = 302): self
    {
        return (new self('', $status))->withHeader('Location', $to);
    }

    public static function noContent(): self
    {
        return new self('', 204);
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function withStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function content(): string
    {
        return $this->body;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->body;
    }
}
