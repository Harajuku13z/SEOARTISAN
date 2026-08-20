<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Page;
use App\Models\CompanyService;
use App\Models\PageBlock;
use App\Services\Content\MenuService;
use App\Support\Str;

final class MenuController extends AdminController
{
    public function index(Request $request): Response
    {
        return $this->render('admin.menu.index', ['items' => MenuService::items(), 'pages' => Page::all('title ASC'), 'services' => CompanyService::all('public_name ASC')], 'menu');
    }

    public function store(Request $request): Response
    {
        $label = trim((string)$request->input('label', ''));
        if ($label !== '') {
            $items = MenuService::items();
            $items[] = ['id' => bin2hex(random_bytes(6)), 'label' => $label, 'url' => trim((string)$request->input('url', '')) ?: '#', 'parent_id' => trim((string)$request->input('parent_id', '')), 'sort_order' => (int)$request->input('sort_order', count($items) + 1), 'active' => true];
            MenuService::save($items);
            Session::flash('success', 'Élément de menu créé.');
        }
        return Response::redirect('/admin/menu');
    }

    public function storeDomain(Request $request): Response
    {
        $label = trim((string)$request->input('label', ''));
        if ($label === '') return Response::redirect('/admin/menu');
        $items = MenuService::items();
        $id = bin2hex(random_bytes(6));
        $slug = $this->availableSlug(Str::slug($label));
        $items[] = ['id'=>$id, 'label'=>$label, 'url'=>'/'.$slug, 'parent_id'=>'', 'sort_order'=>count($items)+1, 'active'=>true];
        MenuService::save($items);
        $this->createDomainPage($id, $label, $slug);
        Session::flash('success', "Le domaine « {$label} » et sa page principale ont été créés.");
        return Response::redirect('/admin/menu');
    }

    public function autoOrganize(Request $request): Response
    {
        $definitions = [
            'Chauffage' => ['chauffage', 'chaudiere', 'chauffe-eau', 'desembouage', 'radiateur'],
            'Pompe à chaleur' => ['pompe-a-chaleur'],
            'Climatisation' => ['climatisation', 'rafraichissement'],
            'Adoucisseur d’eau' => ['adoucisseur'],
            'Ballon thermodynamique' => ['ballon-thermodynamique'],
        ];
        $items = MenuService::items();
        $definitionLabels = array_keys($definitions);
        foreach ($items as &$item) {
            if (empty($item['parent_id']) && !in_array((string)($item['label'] ?? ''), $definitionLabels, true)) {
                $item['active'] = false;
            }
        }
        unset($item);
        $roots = [];
        foreach ($definitions as $label => $keywords) {
            $rootIndex = null;
            foreach ($items as $index => $item) if (empty($item['parent_id']) && mb_strtolower((string)$item['label']) === mb_strtolower($label)) { $rootIndex = $index; break; }
            if ($rootIndex === null) {
                $id = bin2hex(random_bytes(6)); $slug = $this->availableSlug(Str::slug($label));
                $items[] = ['id'=>$id,'label'=>$label,'url'=>'/'.$slug,'parent_id'=>'','sort_order'=>count($roots),'active'=>true];
                $rootIndex = array_key_last($items); $this->createDomainPage($id, $label, $slug);
            } elseif (($items[$rootIndex]['url'] ?? '#') === '#') {
                $slug = $this->availableSlug(Str::slug($label)); $items[$rootIndex]['url'] = '/'.$slug;
                $this->createDomainPage((string)$items[$rootIndex]['id'], $label, $slug);
            }
            $roots[$label] = (string)$items[$rootIndex]['id'];
        }
        foreach (CompanyService::all('sort_order ASC') as $service) {
            if (!$service->getAttribute('is_active')) continue;
            $slug = (string)$service->getAttribute('slug'); $category = 'Chauffage';
            foreach ($definitions as $label => $keywords) foreach ($keywords as $keyword) if (str_contains($slug, $keyword)) { $category = $label; break 2; }
            $url = '/'.$slug; $found = null;
            foreach ($items as $index => $item) if (($item['url'] ?? '') === $url) { $found = $index; break; }
            $row = ['id'=>$found !== null ? $items[$found]['id'] : bin2hex(random_bytes(6)),'label'=>(string)$service->getAttribute('public_name'),'url'=>$url,'parent_id'=>$roots[$category],'sort_order'=>(int)$service->getAttribute('sort_order'),'active'=>true];
            if ($found !== null) $items[$found] = $row; else $items[] = $row;
        }
        MenuService::save($items);
        Session::flash('success', 'Les catégories et leurs pages ont été créées, puis les services ont été classés automatiquement.');
        return Response::redirect('/admin/menu');
    }

    public function createPage(Request $request, array $params): Response
    {
        $id = (string)($params['id'] ?? '');
        $items = MenuService::items();
        foreach ($items as &$item) {
            if (($item['id'] ?? '') !== $id || !empty($item['parent_id'])) continue;
            $slug = $this->availableSlug(Str::slug((string)$item['label']));
            $item['url'] = '/'.$slug;
            $this->createDomainPage($id, (string)$item['label'], $slug);
            MenuService::save($items);
            Session::flash('success', 'Page principale créée et reliée au domaine.');
            break;
        }
        return Response::redirect('/admin/menu');
    }

    public function reorder(Request $request): Response
    {
        $structure = json_decode((string)$request->input('structure', '[]'), true);
        if (!is_array($structure)) return Response::redirect('/admin/menu');
        $items = MenuService::items();
        $positions = [];
        foreach ($structure as $row) $positions[(string)($row['id'] ?? '')] = $row;
        foreach ($items as &$item) {
            $row = $positions[(string)$item['id']] ?? null;
            if (!$row) continue;
            $item['parent_id'] = (string)($row['parent_id'] ?? '');
            $item['sort_order'] = (int)($row['sort_order'] ?? 0);
        }
        MenuService::save($items);
        Session::flash('success', 'Nouvel ordre du menu enregistré.');
        return Response::redirect('/admin/menu');
    }

    public function storeSelection(Request $request): Response
    {
        $items = MenuService::items();
        $parentId = trim((string)$request->input('parent_id', ''));
        $existingUrls = array_map(static fn ($item) => (string)($item['url'] ?? ''), $items);
        $added = 0;

        foreach ((array)$request->input('service_ids', []) as $id) {
            $service = CompanyService::find((int)$id);
            if ($service === null) continue;
            $url = '/' . ltrim((string)$service->getAttribute('slug'), '/');
            if (in_array($url, $existingUrls, true)) continue;
            $items[] = ['id'=>bin2hex(random_bytes(6)), 'label'=>(string)$service->getAttribute('public_name'), 'url'=>$url, 'parent_id'=>$parentId, 'sort_order'=>count($items)+1, 'active'=>true];
            $existingUrls[] = $url; $added++;
        }

        foreach ((array)$request->input('page_ids', []) as $id) {
            $page = Page::find((int)$id);
            if ($page === null) continue;
            $slug = (string)$page->getAttribute('slug');
            $url = ($page->getAttribute('type') === 'home' || $slug === 'accueil') ? '/' : '/' . ltrim($slug, '/');
            if (in_array($url, $existingUrls, true)) continue;
            $items[] = ['id'=>bin2hex(random_bytes(6)), 'label'=>(string)$page->getAttribute('title'), 'url'=>$url, 'parent_id'=>$parentId, 'sort_order'=>count($items)+1, 'active'=>true];
            $existingUrls[] = $url; $added++;
        }

        MenuService::save($items);
        Session::flash('success', $added . ' lien(s) ajouté(s) automatiquement au menu.');
        return Response::redirect('/admin/menu');
    }

    public function update(Request $request): Response
    {
        $rows = (array)$request->input('items', []);
        $items = MenuService::items();
        foreach ($items as &$item) {
            $row = $rows[$item['id']] ?? null;
            if (!is_array($row)) continue;
            $item['label'] = trim((string)($row['label'] ?? $item['label']));
            $item['url'] = trim((string)($row['url'] ?? '#')) ?: '#';
            $item['parent_id'] = (string)($row['parent_id'] ?? '');
            if ($item['parent_id'] === $item['id']) $item['parent_id'] = '';
            $item['sort_order'] = (int)($row['sort_order'] ?? 0);
            $item['active'] = isset($row['active']);
        }
        MenuService::save($items);
        Session::flash('success', 'Menu mis à jour.');
        return Response::redirect('/admin/menu');
    }

    public function destroy(Request $request, array $params): Response
    {
        $id = (string)($params['id'] ?? '');
        $items = array_values(array_filter(MenuService::items(), static fn ($i) => ($i['id'] ?? '') !== $id));
        foreach ($items as &$item) if (($item['parent_id'] ?? '') === $id) $item['parent_id'] = '';
        MenuService::save($items);
        return Response::redirect('/admin/menu');
    }

    private function availableSlug(string $base): string
    {
        $slug = $base !== '' ? $base : 'domaine'; $i = 2;
        while (Page::findBySlug($slug) !== null) $slug = $base . '-' . $i++;
        return $slug;
    }

    private function createDomainPage(string $menuId, string $label, string $slug): void
    {
        $page = Page::create(['type'=>'custom','slug'=>$slug,'title'=>$label,'h1'=>$label,'meta_title'=>$label.' | '.config('app.name'),'meta_description'=>'Découvrez nos services de '.$label.' et choisissez la prestation adaptée à votre projet.','status'=>'published','indexable'=>true,'content_is_placeholder'=>false,'published_at'=>date('Y-m-d H:i:s')]);
        PageBlock::create(['page_id'=>$page->id(),'type'=>'text','position'=>0,'data'=>['content'=>'Découvrez les services proposés pour vos projets de '.$label.'. Sélectionnez une prestation pour consulter les détails et demander un devis personnalisé.'],'is_active'=>true]);
        PageBlock::create(['page_id'=>$page->id(),'type'=>'domain_services','position'=>1,'data'=>['menu_id'=>$menuId,'title'=>'Nos services '.$label],'is_active'=>true]);
    }
}
