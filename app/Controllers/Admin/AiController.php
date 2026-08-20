<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\AiGeneration;
use App\Models\AiProvider;
use App\Models\Page;
use App\Services\AI\AIProviderFactory;
use App\Services\AI\AIContentServiceFactory;
use App\Services\Content\PageContentBuilder;
use App\Support\Crypto;

final class AiController extends AdminController
{
    public function show(Request $request): Response
    {
        $generations = AiGeneration::where([], 'created_at DESC', 30);
        $totalCost = 0.0;
        foreach (AiGeneration::where(['status' => 'success']) as $g) {
            $totalCost += (float) ($g->getAttribute('estimated_cost') ?? 0);
        }

        return $this->render('admin.ai.show', [
            'providers' => config('ai.providers'),
            'aiProvider' => AiProvider::active(),
            'generations' => $generations,
            'totalEstimatedCost' => $totalCost,
            'failedCount' => AiGeneration::count(['status' => 'failed']),
        ], 'ai');
    }

    public function test(Request $request): Response
    {
        $provider = (string) $request->input('provider', 'none');
        if ($provider === 'none') {
            return Response::json(['ok' => true, 'message' => 'Mode redaction manuelle - aucune connexion necessaire.']);
        }

        $apiKey = trim((string) $request->input('api_key', ''));
        if ($apiKey === '') {
            $encrypted = (string) (AiProvider::active()?->getAttribute('api_key_encrypted') ?? '');
            $appKey = (string) config('app.key', '');
            if ($encrypted !== '' && $appKey !== '') {
                $apiKey = Crypto::decrypt($encrypted, $appKey) ?? '';
            }
        }
        if ($apiKey === '') {
            return Response::json(['ok' => false, 'message' => 'Aucune clé API disponible. Saisissez votre clé Gemini AI Studio puis enregistrez-la.']);
        }

        $ai = AIProviderFactory::fromRaw(
            $provider,
            $apiKey,
            (string) $request->input('model', ''),
            (string) $request->input('base_url', '')
        );

        return Response::json($ai->testConnection());
    }

    public function update(Request $request): Response
    {
        $provider = (string) $request->input('provider', 'none');
        $provider = AIProviderFactory::normalizeProvider(
            $provider,
            (string) $request->input('model', ''),
            (string) $request->input('base_url', '')
        );
        $apiKey = trim((string) $request->input('api_key', ''));

        $existing = AiProvider::active();
        $model = $existing ?? new AiProvider();

        $encrypted = $existing?->getAttribute('api_key_encrypted');
        if ($apiKey !== '') {
            $appKey = (string) config('app.key', '');
            $encrypted = $appKey !== '' ? Crypto::encrypt($apiKey, $appKey) : null;
        }

        $model->fill([
            'provider' => $provider,
            'api_key_encrypted' => $encrypted,
            'model' => trim((string) $request->input('model', '')) ?: null,
            'base_url' => trim((string) $request->input('base_url', '')) ?: null,
            'temperature' => (float) $request->input('temperature', config('ai.default_temperature')),
            'max_tokens' => (int) $request->input('max_tokens', config('ai.default_max_tokens')),
            'language' => trim((string) $request->input('language', 'fr')) ?: 'fr',
            'tone' => trim((string) $request->input('tone', '')) ?: (string) config('ai.default_tone'),
            'is_active' => true,
        ]);
        $model->save();

        $this->log('ai_provider.update', 'AiProvider', $model->id(), "Fournisseur : {$provider}");
        Session::flash('success', 'Configuration IA enregistree.');

        return Response::redirect('/admin/ai');
    }

    public function retryFailed(Request $request): Response
    {
        $failed = AiGeneration::where(['status' => 'failed'], 'created_at DESC', 100);
        $seen = [];
        $retried = 0;
        $succeeded = 0;
        $ai = AIContentServiceFactory::fromActiveProvider();
        $pageBuilder = new PageContentBuilder();

        foreach ($failed as $generation) {
            $pageId = (int) ($generation->getAttribute('page_id') ?? 0);
            $key = $pageId . ':' . (string) $generation->getAttribute('prompt_type');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $retried++;

            $outcome = $ai->retrySaved($generation, $this->auth->user()?->id());
            if (!$outcome->success) {
                continue;
            }

            $succeeded++;
            $this->applyGeneratedContent($generation, $outcome, $pageBuilder);
        }

        $this->log('ai_generations.retry_failed', 'AiGeneration', null, "{$succeeded}/{$retried} relances réussies");
        if ($retried === 0) {
            Session::flash('success', 'Aucune génération en échec à relancer.');
        } elseif ($succeeded === $retried) {
            Session::flash('success', "{$succeeded} génération(s) relancée(s) avec succès.");
        } else {
            Session::flash('_errors', ['form' => "{$succeeded} génération(s) réussie(s) sur {$retried}. Consultez l'historique pour les erreurs restantes."]);
        }

        return Response::redirect('/admin/ai');
    }

    public function retryOne(Request $request, array $params): Response
    {
        $generation = AiGeneration::find((int) ($params['id'] ?? 0));
        if ($generation === null || $generation->getAttribute('status') !== 'failed') {
            Session::flash('_errors', ['form' => 'Cette génération est introuvable ou a déjà été relancée.']);
            return Response::redirect('/admin/ai');
        }

        $outcome = AIContentServiceFactory::fromActiveProvider()
            ->retrySaved($generation, $this->auth->user()?->id());

        if ($outcome->success) {
            $this->applyGeneratedContent($generation, $outcome, new PageContentBuilder());
            Session::flash('success', 'La génération a été relancée avec succès.');
        } else {
            Session::flash('_errors', ['form' => 'Échec de la génération : ' . ($outcome->errorMessage ?? 'erreur inconnue')]);
        }

        $this->log('ai_generation.retry', 'AiGeneration', $generation->id());
        return Response::redirect('/admin/ai');
    }

    private function applyGeneratedContent(AiGeneration $generation, \App\DTO\AIContentOutcome $outcome, PageContentBuilder $pageBuilder): void
    {
        $pageId = (int) ($generation->getAttribute('page_id') ?? 0);
        $page = $pageId > 0 ? Page::find($pageId) : null;
        if ($page === null) {
            return;
        }

        $pageBuilder->applySuccess($page, $outcome);
        if ($generation->getAttribute('prompt_type') === 'home') {
            $pageBuilder->appendStructuralBlocks($page, ['services', 'service_area', 'projects', 'testimonials', 'form']);
        }
    }
}
