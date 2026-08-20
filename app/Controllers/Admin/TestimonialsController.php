<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Testimonial;
use App\Models\Company;
use App\Repositories\SettingsRepository;
use App\Services\Auth\AuthService;
use App\Services\Media\MediaUploadService;
use App\Services\Reviews\SerpApiReviewsService;
use App\Support\Crypto;
use Throwable;

final class TestimonialsController extends AdminController
{
    private const SERPAPI_KEY_SETTING = 'reviews.serpapi_key_encrypted';

    public function __construct(
        AuthService $auth,
        private MediaUploadService $uploads,
        private SettingsRepository $settings
    )
    {
        parent::__construct($auth);
    }

    public function index(Request $request): Response
    {
        return $this->render('admin.testimonials.index', [
            'testimonials' => Testimonial::all('sort_order ASC, id DESC'),
            'serpApiConfigured' => $this->serpApiKey() !== '',
            'googleMapsDataId' => Company::current()?->getAttribute('google_maps_data_id'),
        ], 'testimonials');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, new Testimonial());
    }

    public function update(Request $request, array $params): Response
    {
        $testimonial = Testimonial::find((int) $params['id']);
        if ($testimonial === null) {
            return Response::redirect('/admin/testimonials');
        }

        return $this->save($request, $testimonial);
    }

    private function save(Request $request, Testimonial $testimonial): Response
    {
        $wasNew = !$testimonial->exists();
        $authorName = trim((string) $request->input('author_name', ''));
        $content = trim((string) $request->input('content', ''));

        if ($authorName === '' || $content === '') {
            Session::flash('_errors', ['form' => "Le nom de l'auteur et le contenu de l'avis sont obligatoires."]);

            return Response::redirect('/admin/testimonials');
        }

        $testimonial->fill([
            'author_name' => $authorName,
            'role_or_service' => trim((string) $request->input('role_or_service', '')) ?: null,
            'content' => $content,
            'rating' => $request->input('rating', '') !== '' ? (int) $request->input('rating') : null,
            'source' => in_array($request->input('source'), ['manual', 'google'], true) ? $request->input('source') : 'manual',
            'is_visible' => $request->input('is_visible') !== null,
            'sort_order' => (int) $request->input('sort_order', 0),
        ]);

        $file = $request->file('avatar');
        if ($file !== null) {
            try {
                $media = $this->uploads->store($file, 'other', $this->auth->user()?->id());
                $testimonial->setAttribute('avatar_media_id', $media->id());
            } catch (Throwable $e) {
                Session::flash('_errors', ['form' => $e->getMessage()]);

                return Response::redirect('/admin/testimonials');
            }
        }

        $testimonial->save();
        $this->log($wasNew ? 'testimonial.create' : 'testimonial.update', 'Testimonial', $testimonial->id());
        Session::flash('success', 'Avis enregistre.');

        return Response::redirect('/admin/testimonials');
    }

    public function destroy(Request $request, array $params): Response
    {
        Testimonial::find((int) $params['id'])?->delete();
        Session::flash('success', 'Avis supprime.');

        return Response::redirect('/admin/testimonials');
    }

    public function saveSerpApi(Request $request): Response
    {
        $apiKey = trim((string) $request->input('api_key', ''));
        if ($apiKey === '') {
            if ($this->serpApiKey() !== '') {
                Session::flash('success', 'La cle SerpApi existante a ete conservee.');

                return Response::redirect('/admin/testimonials');
            }

            Session::flash('_errors', ['form' => 'Renseignez une cle SerpApi.']);

            return Response::redirect('/admin/testimonials');
        }

        try {
            $encrypted = Crypto::encrypt($apiKey, (string) config('app.key', ''));
            $this->settings->set(self::SERPAPI_KEY_SETTING, $encrypted);
        } catch (Throwable $e) {
            Session::flash('_errors', ['form' => "Impossible de chiffrer la cle SerpApi : verifiez APP_KEY."]);

            return Response::redirect('/admin/testimonials');
        }

        $this->log('serpapi.settings.update');
        Session::flash('success', 'Cle SerpApi enregistree.');

        return Response::redirect('/admin/testimonials');
    }

    public function testSerpApi(Request $request): Response
    {
        $apiKey = trim((string) $request->input('api_key', '')) ?: $this->serpApiKey();

        return Response::json((new SerpApiReviewsService($apiKey))->testConnection());
    }

    public function syncGoogle(Request $request): Response
    {
        $company = Company::current();
        $apiKey = $this->serpApiKey();
        if ($company === null || $apiKey === '') {
            Session::flash('_errors', ['form' => $company === null
                ? "L'entreprise doit etre configuree avant de synchroniser les avis."
                : 'Enregistrez une cle SerpApi avant de synchroniser les avis.']);

            return Response::redirect('/admin/testimonials');
        }

        $result = (new SerpApiReviewsService($apiKey))->sync($company);
        if ($result['error'] !== null) {
            Session::flash('_errors', ['form' => $result['error']]);

            return Response::redirect('/admin/testimonials');
        }

        $this->log('testimonials.google_sync', 'Company', $company->id(),
            "{$result['created']} ajoutes, {$result['updated']} actualises");
        Session::flash('success', sprintf(
            'Synchronisation terminee : %d avis trouves, %d ajoutes et %d actualises.',
            $result['total'], $result['created'], $result['updated']
        ));

        return Response::redirect('/admin/testimonials');
    }

    private function serpApiKey(): string
    {
        $encrypted = $this->settings->get(self::SERPAPI_KEY_SETTING);
        if (!is_string($encrypted) || $encrypted === '') {
            return '';
        }

        try {
            return Crypto::decrypt($encrypted, (string) config('app.key', '')) ?? '';
        } catch (Throwable) {
            return '';
        }
    }
}
