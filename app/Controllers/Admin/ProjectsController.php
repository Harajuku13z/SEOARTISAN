<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Cache;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\Project;
use App\Models\Setting;
use App\Repositories\CityRepository;
use App\Repositories\CompanyServiceRepository;
use App\Services\Auth\AuthService;
use App\Services\Media\MediaUploadService;
use App\Services\Content\MenuService;
use Throwable;

final class ProjectsController extends AdminController
{
    public function __construct(
        AuthService $auth,
        private MediaUploadService $uploads,
        private CityRepository $cities,
        private CompanyServiceRepository $companyServices
    ) {
        parent::__construct($auth);
    }

    public function index(Request $request): Response
    {
        $videos = [];
        $entries = json_decode((string) (Setting::first(['key' => 'content.realisation_videos'])?->getAttribute('value') ?? '[]'), true) ?: [];
        foreach ($entries as $entry) {
            $media = Media::find((int) ($entry['media_id'] ?? 0));
            if ($media !== null) {
                $videos[] = ['media' => $media, 'title' => (string) ($entry['title'] ?? 'Vidéo')];
            }
        }

        return $this->render('admin.projects.index', [
            'projects' => Project::all('sort_order ASC, id DESC'),
            'videos' => $videos,
        ], 'projects');
    }

    public function storeVideo(Request $request): Response
    {
        $file = $request->file('video');
        if ($file === null) {
            Session::flash('_errors', ['form' => 'Veuillez sélectionner une vidéo.']);
            return Response::redirect('/admin/projects');
        }

        try {
            $media = $this->uploads->store($file, 'realization_video', $this->auth->user()?->id());
            $setting = Setting::first(['key' => 'content.realisation_videos']) ?? new Setting();
            $entries = json_decode((string) ($setting->getAttribute('value') ?? '[]'), true) ?: [];
            $title = trim((string) $request->input('video_title', ''));
            $entries[] = ['media_id' => (int) $media->id(), 'title' => $title !== '' ? $title : pathinfo((string) $file['name'], PATHINFO_FILENAME)];
            $setting->fill(['key' => 'content.realisation_videos', 'value' => json_encode($entries, JSON_UNESCAPED_UNICODE), 'autoload' => 1])->save();
            Cache::flush();
            Session::flash('success', 'Vidéo ajoutée dans « Ils parlent de nous ».');
        } catch (Throwable $e) {
            Session::flash('_errors', ['form' => $e->getMessage()]);
        }

        return Response::redirect('/admin/projects');
    }

    public function deleteVideo(Request $request, array $params): Response
    {
        $mediaId = (int) $params['id'];
        $setting = Setting::first(['key' => 'content.realisation_videos']);
        $entries = json_decode((string) ($setting?->getAttribute('value') ?? '[]'), true) ?: [];
        $entries = array_values(array_filter($entries, static fn (array $entry): bool => (int) ($entry['media_id'] ?? 0) !== $mediaId));
        if ($setting !== null) {
            $setting->fill(['value' => json_encode($entries, JSON_UNESCAPED_UNICODE)])->save();
        }

        $media = Media::find($mediaId);
        if ($media !== null) {
            $path = public_path((string) $media->getAttribute('disk_path'));
            if (is_file($path)) {
                @unlink($path);
            }
            $media->delete();
        }
        Cache::flush();
        Session::flash('success', 'Vidéo supprimée.');

        return Response::redirect('/admin/projects');
    }

    public function create(Request $request): Response
    {
        [$serviceGroups, $serviceGroupMap] = $this->serviceGroups();
        return $this->render('admin.projects.form', [
            'project' => null,
            'cities' => \App\Models\Company::current() ? $this->cities->forCompany((int) \App\Models\Company::current()->id()) : [],
            'services' => \App\Models\Company::current() ? $this->companyServices->forCompany((int) \App\Models\Company::current()->id()) : [],
            'serviceGroups' => $serviceGroups,
            'serviceGroupMap' => $serviceGroupMap,
        ], 'projects');
    }

    public function edit(Request $request, array $params): Response
    {
        $project = Project::find((int) $params['id']);
        if ($project === null) {
            return Response::redirect('/admin/projects');
        }

        [$serviceGroups, $serviceGroupMap] = $this->serviceGroups();
        return $this->render('admin.projects.form', [
            'project' => $project,
            'cities' => \App\Models\Company::current() ? $this->cities->forCompany((int) \App\Models\Company::current()->id()) : [],
            'services' => \App\Models\Company::current() ? $this->companyServices->forCompany((int) \App\Models\Company::current()->id()) : [],
            'serviceGroups' => $serviceGroups,
            'serviceGroupMap' => $serviceGroupMap,
        ], 'projects');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, new Project());
    }

    public function update(Request $request, array $params): Response
    {
        $project = Project::find((int) $params['id']);
        if ($project === null) {
            return Response::redirect('/admin/projects');
        }

        return $this->save($request, $project);
    }

    private function save(Request $request, Project $project): Response
    {
        $wasNew = !$project->exists();
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            Session::flash('_errors', ['form' => 'Le titre est obligatoire.']);

            return Response::redirect('/admin/projects');
        }

        $project->fill([
            'title' => $title,
            'description' => trim((string) $request->input('description', '')) ?: null,
            'category' => trim((string) $request->input('category', '')) ?: null,
            'city_id' => $request->input('city_id', '') !== '' ? (int) $request->input('city_id') : null,
            'company_service_id' => $request->input('company_service_id', '') !== '' ? (int) $request->input('company_service_id') : null,
            'project_date' => trim((string) $request->input('project_date', '')) ?: null,
            'alt_text' => trim((string) $request->input('alt_text', '')) ?: null,
            'is_visible' => $request->input('is_visible') !== null,
            'sort_order' => (int) $request->input('sort_order', 0),
        ]);

        $uploadedBy = $this->auth->user()?->id();
        foreach (['before_media_id' => 'before_image', 'after_media_id' => 'after_image'] as $column => $inputName) {
            $file = $request->file($inputName);
            if ($file === null) {
                continue;
            }
            try {
                $media = $this->uploads->store($file, 'realization', $uploadedBy);
                $project->setAttribute($column, $media->id());
            } catch (Throwable $e) {
                Session::flash('_errors', ['form' => $e->getMessage()]);

                return Response::redirect('/admin/projects');
            }
        }

        $project->save();
        $this->log($wasNew ? 'project.create' : 'project.update', 'Project', $project->id());
        Session::flash('success', 'Realisation enregistree.');

        return Response::redirect('/admin/projects');
    }

    public function destroy(Request $request, array $params): Response
    {
        Project::find((int) $params['id'])?->delete();
        Session::flash('success', 'Realisation supprimee.');

        return Response::redirect('/admin/projects');
    }

    /**
     * Import ponctuel: cree une realisation (photo seule, sans avant/apres) par
     * fichier trouve sous storage/import_realisations/<categorie>/. A supprimer
     * (route + dossier + cette methode) une fois l'import termine.
     */
    private const IMPORT_TOKEN = '7f49f90592b442496d0ea0ad4c4a5f84e71ae9b954844e42';

    public function importDebug(Request $request): Response
    {
        if (!hash_equals(self::IMPORT_TOKEN, (string) $request->input('token', ''))) {
            return Response::html('Forbidden', 403);
        }

        $lines = [];
        $projects = Project::all('sort_order ASC');
        $lines[] = 'Total projects: ' . count($projects);
        foreach ($projects as $p) {
            $lines[] = sprintf(
                '#%d title=%s visible=%s after_media=%s sort=%s',
                (int) $p->id(),
                (string) $p->getAttribute('title'),
                $p->getAttribute('is_visible') ? '1' : '0',
                (string) $p->getAttribute('after_media_id'),
                (string) $p->getAttribute('sort_order')
            );
        }

        $page = \App\Models\Page::findBySlug('realisations');
        $lines[] = '---';
        $lines[] = 'Page found: ' . ($page !== null ? ('yes id=' . $page->id() . ' status=' . $page->getAttribute('status')) : 'NO');
        if ($page !== null) {
            $blocks = \App\Models\PageBlock::forPage((int) $page->id());
            $lines[] = 'Blocks count: ' . count($blocks);
            foreach ($blocks as $b) {
                $lines[] = 'block raw=' . var_export($b->toArray(), true);
            }

            $db = \App\Core\Database::instance();
            $lines[] = 'DB prefix: "' . $db->prefix() . '"';
            $rawRows = $db->select(
                'SELECT id, page_id, `type`, position, is_active FROM ' . $db->table('page_blocks') . ' WHERE page_id = ?',
                [(int) $page->id()]
            );
            $lines[] = 'RAW SQL rows: ' . var_export($rawRows, true);

            $columnInfo = $db->select('SHOW COLUMNS FROM ' . $db->table('page_blocks') . ' LIKE \'type\'');
            $lines[] = 'COLUMN DEF: ' . var_export($columnInfo, true);

            $sqlModeRow = $db->select('SELECT @@sql_mode AS m');
            $lines[] = 'sql_mode: ' . var_export($sqlModeRow, true);

            $insertTest = $db->select('SELECT ? AS test_insert_projects', ['projects']);
            $lines[] = 'bind test: ' . var_export($insertTest, true);
        }

        return Response::html('<pre>' . htmlspecialchars(implode("\n", $lines), ENT_QUOTES) . '</pre>');
    }

    /**
     * Idempotent: purge d'abord TOUTES les realisations existantes (et leurs
     * media associes) avant de reimporter depuis storage/import_realisations/.
     * Sans quoi rappeler cette route dupliquerait les fiches a chaque fois.
     * Assure aussi la presence d'un bloc "projects" sur la page /realisations.
     */
    public function importRealisations(Request $request): Response
    {
        if (!hash_equals(self::IMPORT_TOKEN, (string) $request->input('token', ''))) {
            return Response::html('Forbidden', 403);
        }

        $lines = [];

        // 1) Purge de tout ce qui existe deja (idempotence).
        $existingProjects = Project::all();
        $mediaIds = [];
        foreach ($existingProjects as $p) {
            foreach (['before_media_id', 'after_media_id'] as $col) {
                $id = $p->getAttribute($col);
                if ($id !== null) {
                    $mediaIds[(int) $id] = true;
                }
            }
            $p->delete();
        }
        $lines[] = 'Projects purges: ' . count($existingProjects);

        $mediaDeleted = 0;
        foreach (array_keys($mediaIds) as $mediaId) {
            $media = Media::find($mediaId);
            if ($media === null) {
                continue;
            }
            $path = public_path((string) $media->getAttribute('disk_path'));
            if (is_file($path)) {
                @unlink($path);
            }
            $media->delete();
            $mediaDeleted++;
        }
        $lines[] = 'Media purges: ' . $mediaDeleted;

        // 2) Reimport depuis le dossier local.
        $baseDir = storage_path('import_realisations');
        if (!is_dir($baseDir)) {
            $lines[] = "Dossier d'import introuvable, rien reimporte : storage/import_realisations";
        } else {
            $categories = [
                'adoucisseur' => "Adoucisseur d'eau",
                'clim' => 'Climatisation',
                'pompe' => 'Pompe a chaleur',
            ];

            $uploadedBy = $this->auth->user()?->id();
            $sortOrder = 0;
            $created = 0;
            $failed = [];

            foreach ($categories as $folder => $label) {
                $dir = $baseDir . '/' . $folder;
                if (!is_dir($dir)) {
                    continue;
                }

                foreach (glob($dir . '/*') as $filePath) {
                    if (!is_file($filePath)) {
                        continue;
                    }

                    $file = [
                        'name' => basename($filePath),
                        'tmp_name' => $filePath,
                        'size' => filesize($filePath) ?: 0,
                        'error' => UPLOAD_ERR_OK,
                    ];

                    try {
                        $media = $this->uploads->store($file, 'realization', $uploadedBy);
                    } catch (Throwable $e) {
                        $failed[] = basename($filePath) . ': ' . $e->getMessage();
                        continue;
                    }

                    $sortOrder++;
                    $project = new Project();
                    $project->fill([
                        'title' => $label,
                        'alt_text' => $label,
                        'is_visible' => true,
                        'sort_order' => $sortOrder,
                    ]);
                    $project->setAttribute('after_media_id', $media->id());
                    $project->save();
                    $created++;
                }
            }

            $lines[] = $created . ' realisation(s) reimportee(s).';
            if ($failed !== []) {
                $lines[] = 'Echecs: ' . implode(', ', $failed);
            }
        }

        // 3) S'assurer que la page /realisations a bien un bloc "projects" actif.
        $page = Page::findBySlug('realisations');
        if ($page === null) {
            $lines[] = 'Page "realisations" introuvable - bloc non ajoute.';
        } else {
            $blocks = PageBlock::forPage((int) $page->id());
            $hasProjectsBlock = false;
            foreach ($blocks as $b) {
                if ($b->getAttribute('type') === 'projects') {
                    $hasProjectsBlock = true;
                    break;
                }
            }
            if ($hasProjectsBlock) {
                $lines[] = 'Bloc "projects" deja present sur la page.';
            } else {
                $maxPosition = 0;
                foreach ($blocks as $b) {
                    $maxPosition = max($maxPosition, (int) $b->getAttribute('position'));
                }
                PageBlock::create([
                    'page_id' => (int) $page->id(),
                    'type' => 'projects',
                    'position' => $maxPosition + 1,
                    'data' => [],
                    'is_active' => true,
                ]);
                $lines[] = 'Bloc "projects" ajoute sur la page (position ' . ($maxPosition + 1) . ').';
            }
        }

        $this->log('project.import', 'Project', null);
        Session::flash('success', implode(' | ', $lines));

        return Response::html('<pre>' . htmlspecialchars(implode("\n", $lines), ENT_QUOTES) . '</pre>');
    }

    /** @return array{0:array<string,string>,1:array<int,string>} */
    private function serviceGroups(): array
    {
        $groups = [];
        $slugToGroup = [];
        foreach (MenuService::tree() as $root) {
            if (empty($root['children'])) continue;
            $groupSlug = trim((string) parse_url((string)($root['url'] ?? ''), PHP_URL_PATH), '/');
            if ($groupSlug === '') continue;
            $groups[$groupSlug] = (string)($root['label'] ?? ucfirst($groupSlug));
            foreach ((array)($root['children'] ?? []) as $child) {
                $childSlug = trim((string) parse_url((string)($child['url'] ?? ''), PHP_URL_PATH), '/');
                if ($childSlug !== '') $slugToGroup[$childSlug] = $groupSlug;
            }
        }
        $serviceMap = [];
        $company = \App\Models\Company::current();
        if ($company) foreach ($this->companyServices->forCompany((int)$company->id()) as $service) {
            $serviceMap[(int)$service['id']] = $slugToGroup[(string)$service['slug']] ?? '';
        }
        return [$groups, $serviceMap];
    }
}
