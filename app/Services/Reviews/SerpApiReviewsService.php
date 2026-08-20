<?php

declare(strict_types=1);

namespace App\Services\Reviews;

use App\Core\Logger;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\Testimonial;

/**
 * Fetches real Google reviews via SerpApi and stores them as Testimonial
 * rows (source='google', deduped on google_review_id). Never fabricates -
 * a business with no matching Google Maps listing simply syncs zero
 * reviews rather than inventing any.
 */
final class SerpApiReviewsService
{
    private const BASE_URL = 'https://serpapi.com';

    public function __construct(private string $apiKey)
    {
    }

    /** @return array{ok:bool,message:string} */
    public function testConnection(): array
    {
        if (trim($this->apiKey) === '') {
            return ['ok' => false, 'message' => 'Aucune cle SerpApi renseignee.'];
        }

        // account.json is free (does not consume search quota) - the
        // right endpoint for a "test connection" action.
        [$status, $body, $error] = $this->get('/account.json', ['api_key' => $this->apiKey]);

        if ($error !== null) {
            return ['ok' => false, 'message' => $error];
        }

        $decoded = json_decode((string) $body, true);
        if ($status !== 200 || !is_array($decoded)) {
            return ['ok' => false, 'message' => "SerpApi a repondu avec le statut {$status}."];
        }

        if (($decoded['error'] ?? null) !== null) {
            return ['ok' => false, 'message' => (string) $decoded['error']];
        }

        $email = $decoded['account_email'] ?? 'compte inconnu';
        $left = $decoded['total_searches_left'] ?? '?';

        return ['ok' => true, 'message' => "Connexion reussie ({$email}, {$left} recherches restantes)."];
    }

    /**
     * Resolves and caches the company's Google Maps listing (data_id).
     * Returns null if no listing is found or the company has no name to
     * search with - never guesses.
     */
    public function findDataId(Company $company): ?string
    {
        $existing = $company->getAttribute('google_maps_data_id');
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $name = (string) $company->getAttribute('trade_name');
        $city = (string) $company->getAttribute('city');
        if ($city === '') {
            $city = $this->primaryCityName($company) ?? '';
        }
        if ($name === '') {
            return null;
        }

        $query = trim($name . ' ' . $city);
        $params = [
            'engine' => 'google_maps',
            'q' => $query,
            'type' => 'search',
            'hl' => 'fr',
            'gl' => 'fr',
            'api_key' => $this->apiKey,
        ];

        $ll = $this->primaryCityLatLon($company);
        if ($ll !== null) {
            $params['ll'] = sprintf('@%F,%F,14z', $ll[0], $ll[1]);
        }

        [$status, $body, $error] = $this->get('/search.json', $params);
        if ($error !== null || $status !== 200) {
            Logger::warning('SerpApi google_maps lookup failed', ['error' => $error, 'status' => $status]);

            return null;
        }

        $decoded = json_decode((string) $body, true);
        // Exact business queries are returned by SerpApi as place_results;
        // broader searches use local_results. Support both response shapes.
        $dataId = $decoded['place_results']['data_id'] ?? null;
        if (!is_string($dataId) || $dataId === '') {
            $normalizedName = mb_strtolower(preg_replace('/\s+/', '', $name));
            foreach ((array) ($decoded['local_results'] ?? []) as $candidate) {
                $candidateName = mb_strtolower(preg_replace('/\s+/', '', (string) ($candidate['title'] ?? '')));
                if ($normalizedName !== '' && str_contains($candidateName, $normalizedName)) {
                    $dataId = $candidate['data_id'] ?? null;
                    break;
                }
            }
        }
        if (!is_string($dataId) || $dataId === '') {
            return null;
        }

        $company->setAttribute('google_maps_data_id', $dataId);
        $company->save();

        return $dataId;
    }

    /**
     * @return array<int,array{google_review_id:string,author_name:string,author_city:?string,rating:?int,content:string,reviewed_at:?string,avatar_url:?string}>
     */
    public function fetchReviews(string $dataId): array
    {
        $params = [
            'engine' => 'google_maps_reviews',
            'data_id' => $dataId,
            'hl' => 'fr',
            'api_key' => $this->apiKey,
        ];
        $result = [];
        $seen = [];

        // SerpApi returns eight reviews per page. Follow its opaque token
        // with a conservative cap so a bad pagination response can never
        // exhaust the account quota or loop forever.
        for ($page = 0; $page < 15; $page++) {
            [$status, $body, $error] = $this->get('/search.json', $params);
            if ($error !== null || $status !== 200) {
                Logger::warning('SerpApi google_maps_reviews fetch failed', ['error' => $error, 'status' => $status, 'page' => $page + 1]);
                break;
            }

            $decoded = json_decode((string) $body, true);
            foreach ((array) ($decoded['reviews'] ?? []) as $review) {
                $reviewId = (string) ($review['review_id'] ?? '');
                $content = trim((string) ($review['extracted_snippet']['original'] ?? $review['snippet'] ?? ''));
                if ($reviewId === '' || $content === '' || isset($seen[$reviewId])) {
                    continue;
                }
                $seen[$reviewId] = true;
                $isoDate = $review['iso_date'] ?? null;
                $result[] = [
                    'google_review_id' => $reviewId,
                    'author_name' => (string) ($review['user']['name'] ?? 'Client Google'),
                    'author_city' => null,
                    'rating' => isset($review['rating']) ? (int) round((float) $review['rating']) : null,
                    'content' => $content,
                    'reviewed_at' => is_string($isoDate) ? substr($isoDate, 0, 10) : null,
                    'avatar_url' => $review['user']['thumbnail'] ?? null,
                ];
            }

            $token = $decoded['serpapi_pagination']['next_page_token'] ?? null;
            if (!is_string($token) || $token === '') {
                break;
            }
            $params['next_page_token'] = $token;
        }

        return $result;
    }

    /** @return array{created:int,updated:int,total:int,error:?string} */
    public function sync(Company $company): array
    {
        $dataId = $this->findDataId($company);
        if ($dataId === null) {
            return ['created' => 0, 'updated' => 0, 'total' => 0, 'error' => "Aucune fiche Google Maps trouvee pour cette entreprise."];
        }

        $reviews = $this->fetchReviews($dataId);
        $created = 0;
        $updated = 0;
        $maxSortOrder = 0;
        foreach (Testimonial::all() as $existing) {
            $maxSortOrder = max($maxSortOrder, (int) $existing->getAttribute('sort_order'));
        }

        foreach ($reviews as $review) {
            $existing = Testimonial::findByGoogleReviewId($review['google_review_id']);

            if ($existing !== null) {
                $existing->fill([
                    'content' => $review['content'],
                    'rating' => $review['rating'],
                    'reviewed_at' => $review['reviewed_at'],
                ]);
                $existing->save();
                $updated++;

                continue;
            }

            Testimonial::create([
                'author_name' => $review['author_name'],
                'author_city' => $review['author_city'],
                'content' => $review['content'],
                'rating' => $review['rating'],
                'reviewed_at' => $review['reviewed_at'],
                'source' => 'google',
                'google_review_id' => $review['google_review_id'],
                'is_visible' => true,
                'sort_order' => ++$maxSortOrder,
            ]);
            $created++;
        }

        return ['created' => $created, 'updated' => $updated, 'total' => count($reviews), 'error' => null];
    }

    /** @return array{0:float,1:float}|null */
    private function primaryCityLatLon(Company $company): ?array
    {
        $location = CompanyLocation::first(['company_id' => $company->id(), 'is_primary' => 1]);
        if ($location === null) {
            return null;
        }

        $city = City::find((int) $location->getAttribute('city_id'));
        $lat = $city?->getAttribute('latitude');
        $lon = $city?->getAttribute('longitude');

        return ($lat !== null && $lon !== null) ? [(float) $lat, (float) $lon] : null;
    }

    private function primaryCityName(Company $company): ?string
    {
        $location = CompanyLocation::first(['company_id' => $company->id(), 'is_primary' => 1])
            ?? CompanyLocation::first(['company_id' => $company->id()]);
        if ($location === null) {
            return null;
        }

        $name = City::find((int) $location->getAttribute('city_id'))?->getAttribute('name');

        return is_string($name) && $name !== '' ? $name : null;
    }

    /**
     * @param array<string,mixed> $query
     * @return array{0:int,1:?string,2:?string}
     */
    private function get(string $path, array $query): array
    {
        $url = self::BASE_URL . $path . '?' . http_build_query($query);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            return [0, null, "Erreur reseau : {$curlError}"];
        }

        return [$status, is_string($body) ? $body : null, null];
    }
}
