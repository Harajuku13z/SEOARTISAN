<?php

declare(strict_types=1);

namespace App\Services\Tracking;

/**
 * Classification des visiteurs à partir du User-Agent.
 *
 * Catégories :
 *  - human  : navigateur réel
 *  - search : moteurs de recherche (Googlebot, Bingbot…) — toujours autorisés (SEO)
 *  - ai     : crawlers d'IA (GPTBot, ClaudeBot, PerplexityBot…) — autorisés si réglage actif
 *  - social : générateurs d'aperçus de liens (Facebook, WhatsApp…) — autorisés
 *  - tool   : outils, scrapers et bots agressifs — bloqués si réglage actif
 */
final class BotDetector
{
    /** @var array<string,string> */
    private const SEARCH = [
        'googlebot' => 'Googlebot', 'google-inspectiontool' => 'Google Inspection Tool',
        'bingbot' => 'Bingbot', 'duckduckbot' => 'DuckDuckGo', 'yandexbot' => 'Yandex',
        'yandeximages' => 'Yandex Images', 'baiduspider' => 'Baidu', 'applebot' => 'Applebot',
        'mojeekbot' => 'Mojeek', 'seznambot' => 'Seznam', 'qwantify' => 'Qwant',
    ];

    /** @var array<string,string> */
    private const AI = [
        'gptbot' => 'GPTBot (OpenAI)', 'oai-searchbot' => 'OAI-SearchBot (ChatGPT search)',
        'chatgpt-user' => 'ChatGPT-User', 'claudebot' => 'ClaudeBot (Anthropic)',
        'claude-web' => 'Claude-Web', 'anthropic-ai' => 'Anthropic-ai',
        'perplexitybot' => 'PerplexityBot', 'perplexity-user' => 'Perplexity-User',
        'google-extended' => 'Google-Extended (Gemini)', 'applebot-extended' => 'Applebot-Extended',
        'ccbot' => 'CCBot (Common Crawl)', 'bytespider' => 'Bytespider (ByteDance)',
        'cohere-ai' => 'Cohere-ai', 'meta-externalagent' => 'Meta-ExternalAgent',
        'meta-externalfetcher' => 'Meta-ExternalFetcher', 'amazonbot' => 'Amazonbot',
        'diffbot' => 'Diffbot', 'youbot' => 'YouBot', 'imagesiftbot' => 'ImageSiftBot',
        'fleexcrawler' => 'Fleexcrawler', 'omgili' => 'Omgili/Webz.io',
    ];

    /** @var array<string,string> */
    private const SOCIAL = [
        'facebookexternalhit' => 'Facebook', 'twitterbot' => 'Twitter/X',
        'linkedinbot' => 'LinkedIn', 'whatsapp' => 'WhatsApp', 'telegrambot' => 'Telegram',
        'slackbot' => 'Slack', 'discordbot' => 'Discord', 'embedly' => 'Embedly',
        'skypeuripreview' => 'Skype', 'vkshare' => 'VK', 'pinterestbot' => 'Pinterest',
    ];

    /** @var array<string,string> */
    private const TOOLS = [
        'curl' => 'curl', 'wget' => 'wget', 'python-requests' => 'Python requests',
        'python-urllib' => 'Python urllib', 'scrapy' => 'Scrapy', 'httpclient' => 'HttpClient',
        'java/' => 'Java', 'go-http-client' => 'Go-http-client', 'libwww' => 'libwww',
        'headless' => 'Headless browser', 'phantomjs' => 'PhantomJS', 'selenium' => 'Selenium',
        'puppeteer' => 'Puppeteer', 'playwright' => 'Playwright', 'zgrab' => 'zgrab',
        'masscan' => 'masscan', 'nmap' => 'nmap', 'sqlmap' => 'sqlmap', 'nikto' => 'Nikto',
        'semrushbot' => 'SemrushBot', 'ahrefsbot' => 'AhrefsBot', 'mj12bot' => 'MJ12bot',
        'dotbot' => 'DotBot', 'petalbot' => 'PetalBot', 'serpstatbot' => 'SerpstatBot',
        'dataforseo' => 'DataForSeo', 'megaindex' => 'MegaIndex', 'blexbot' => 'BLEXBot',
        'zoominfobot' => 'ZoomInfoBot', 'clickagy' => 'Clickagy', 'lanshanbot' => 'Lanshanbot',
        'fedoraplanet' => 'FedoraPlanet', 'okhttp' => 'okhttp', 'okhttp/' => 'okhttp',
        'fasthttp' => 'fasthttp', 'axios' => 'axios', 'node-fetch' => 'node-fetch',
        'guzzlehttp' => 'Guzzle', 'monitoring' => 'Monitoring', 'uptime' => 'Uptime monitor',
        'zabbix' => 'Zabbix', 'pandora' => 'Pandora', 'havij' => 'Havij',
    ];

    /** @return array{is_bot:bool,category:string,name:?string} */
    public static function detect(string $userAgent): array
    {
        $ua = mb_strtolower(trim($userAgent));

        if ($ua === '') {
            return ['is_bot' => true, 'category' => 'tool', 'name' => 'User-Agent vide'];
        }

        foreach (self::SEARCH as $needle => $name) {
            if (str_contains($ua, $needle)) {
                return ['is_bot' => true, 'category' => 'search', 'name' => $name];
            }
        }
        foreach (self::AI as $needle => $name) {
            if (str_contains($ua, $needle)) {
                return ['is_bot' => true, 'category' => 'ai', 'name' => $name];
            }
        }
        foreach (self::SOCIAL as $needle => $name) {
            if (str_contains($ua, $needle)) {
                return ['is_bot' => true, 'category' => 'social', 'name' => $name];
            }
        }
        foreach (self::TOOLS as $needle => $name) {
            if (str_contains($ua, $needle)) {
                return ['is_bot' => true, 'category' => 'tool', 'name' => $name];
            }
        }
        if (preg_match('/bot|crawler|spider|crawl|scraper|fetcher|scanner/i', $ua)) {
            return ['is_bot' => true, 'category' => 'tool', 'name' => 'Bot non identifié'];
        }

        return ['is_bot' => false, 'category' => 'human', 'name' => null];
    }
}
