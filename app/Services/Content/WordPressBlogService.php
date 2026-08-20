<?php
declare(strict_types=1);
namespace App\Services\Content;

use App\Repositories\SettingsRepository;
use RuntimeException;

final class WordPressBlogService
{
    public function __construct(private SettingsRepository $settings) {}
    public function url(): string { return rtrim((string)$this->settings->get('wordpress.url',''),'/'); }
    public function saveUrl(string $url): void { $this->settings->set('wordpress.url', rtrim($url,'/')); }
    public function posts(int $page=1, int $perPage=12): array { return $this->get('/wp-json/wp/v2/posts?_embed=1&status=publish&per_page='.$perPage.'&page='.$page); }
    public function post(string $slug): ?array { $rows=$this->get('/wp-json/wp/v2/posts?_embed=1&status=publish&slug='.rawurlencode($slug)); return $rows[0]??null; }
    public function test(): array { $posts=$this->posts(1,1); return ['ok'=>true,'count'=>count($posts)]; }
    private function get(string $path): array
    {
        $base=$this->url(); if($base==='') throw new RuntimeException('Renseignez d’abord l’URL du site WordPress.');
        $ch=curl_init($base.$path); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_HTTPHEADER=>['Accept: application/json'],CURLOPT_USERAGENT=>'ArtisanSiteBlog/1.0']);
        $body=curl_exec($ch); $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); $error=curl_error($ch); curl_close($ch);
        if($body===false||$status<200||$status>=300) throw new RuntimeException('Connexion WordPress impossible'.($error!==''?' : '.$error:' (HTTP '.$status.')'));
        $data=json_decode((string)$body,true); if(!is_array($data)) throw new RuntimeException('Réponse WordPress invalide.'); return $data;
    }
}
