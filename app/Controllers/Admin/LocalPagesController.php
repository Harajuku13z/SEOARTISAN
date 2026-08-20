<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Cache;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanyService;
use App\Models\LocalPage;
use App\Models\ServiceLocation;
use App\Services\Auth\AuthService;
use App\Services\Content\LocalPageGenerator;
use Throwable;

final class LocalPagesController extends AdminController
{
    public function __construct(AuthService $auth,private Database $db,private LocalPageGenerator $generator){parent::__construct($auth);}

    public function index(Request $request):Response
    {
        $company=Company::current();$services=$company?CompanyService::where(['company_id'=>$company->id(),'is_active'=>1],'sort_order ASC'):[];
        $cities=[];
        if($company){$cl=$this->db->table('company_locations');$ct=$this->db->table('cities');$cities=$this->db->select("SELECT c.* FROM {$ct} c JOIN {$cl} cl ON cl.city_id=c.id WHERE cl.company_id=? AND cl.is_active=1 ORDER BY cl.is_primary DESC,c.name ASC",[$company->id()]);}
        $rows=$this->rows();
        return $this->render('admin.local_pages.index',['pageTitle'=>'Pages locales service × ville','services'=>$services,'cities'=>$cities,'localPages'=>$rows],'local_pages');
    }

    public function create(Request $request):Response
    {
        $serviceId=(int)$request->input('company_service_id',0);$cityIds=array_values(array_unique(array_map('intval',(array)$request->input('city_ids',[]))));
        $mode=$request->input('content_mode','ai')==='manual'?'manual':'ai';$status=$request->input('status','draft')==='published'?'published':'draft';
        if(!$this->ownedService($serviceId)||$cityIds===[]){Session::flash('_errors',['form'=>'Sélectionnez un service et au moins une ville.']);return Response::redirect('/admin/local-pages');}
        $count=0;foreach($cityIds as $cityId){if(!$this->allowedCity($cityId))continue;$local=LocalPage::first(['company_service_id'=>$serviceId,'city_id'=>$cityId])??new LocalPage();$local->fill(['company_service_id'=>$serviceId,'city_id'=>$cityId,'is_active'=>1,'content_mode'=>$mode,'status'=>$status,'generation_status'=>'pending'])->save();$this->activateServiceLocation($serviceId,$cityId);$count++;}
        Cache::flush();Session::flash('success',$count.' combinaison(s) activée(s).');return Response::redirect('/admin/local-pages');
    }

    public function update(Request $request,array $params):Response
    {
        $local=LocalPage::find((int)($params['id']??0));if(!$local||!$this->ownedService((int)$local->getAttribute('company_service_id')))return Response::json(['error'=>'Page locale introuvable.'],404);
        $payloadText=trim((string)$request->input('content_payload',''));$payload=$payloadText===''?[]:json_decode($payloadText,true);
        if(!is_array($payload)){Session::flash('_errors',['form'=>'Le contenu JSON de la page est invalide.']);return Response::redirect('/admin/local-pages');}
        $mode=$request->input('content_mode','ai')==='manual'?'manual':'ai';
        $active=(bool)$request->input('is_active',false);$status=$request->input('status','draft')==='published'?'published':'draft';
        $local->fill(['is_active'=>$active,'content_mode'=>$mode,'content_payload'=>$payload,'status'=>$status,'generation_status'=>'pending','error_message'=>null])->save();
        if($local->getAttribute('page_id')&&($page=\App\Models\Page::find((int)$local->getAttribute('page_id')))){$page->fill(['indexable'=>$active,'status'=>$active?$status:'draft']);$page->save();}
        $link=ServiceLocation::first(['company_service_id'=>$local->getAttribute('company_service_id'),'city_id'=>$local->getAttribute('city_id')]);if($link){$link->fill(['is_active'=>$active])->save();}
        Cache::flush();Session::flash('success','Configuration locale enregistrée.');return Response::redirect('/admin/local-pages');
    }

    public function importJson(Request $request):Response
    {
        $decoded=json_decode(trim((string)$request->input('payload','')) ,true);
        $items=is_array($decoded)&&array_is_list($decoded)?$decoded:(is_array($decoded)&&isset($decoded['pages'])&&is_array($decoded['pages'])?$decoded['pages']:null);
        if($items===null){return Response::json(['error'=>'JSON invalide. Envoyez un tableau ou un objet contenant « pages ».'],422);}
        $created=0;$errors=[];
        foreach($items as $index=>$item){if(!is_array($item)){continue;}$service=$this->resolveService($item['service_id']??$item['service']??null);$city=$this->resolveCity($item['city_id']??$item['city']??$item['insee_code']??null);if(!$service||!$city){$errors[]='Ligne '.($index+1).' : service ou ville introuvable.';continue;}$mode=($item['mode']??$item['content_mode']??'ai')==='manual'?'manual':'ai';$content=(array)($item['content']??$item);$local=LocalPage::first(['company_service_id'=>$service->id(),'city_id'=>$city->id()])??new LocalPage();$local->fill(['company_service_id'=>$service->id(),'city_id'=>$city->id(),'is_active'=>(bool)($item['active']??true),'content_mode'=>$mode,'content_payload'=>$content,'status'=>($item['status']??'draft')==='published'?'published':'draft','generation_status'=>'pending','error_message'=>null])->save();$this->activateServiceLocation((int)$service->id(),(int)$city->id());$created++;}
        Cache::flush();return Response::json(['ok'=>true,'created'=>$created,'errors'=>$errors,'ids'=>array_map(static fn($row)=>(int)$row['id'],$this->rows('pending'))]);
    }

    public function exportJson(Request $request):Response
    {
        $pages=[];foreach($this->rows() as $row){$pages[]=['service'=>$row['service_slug'],'city'=>$row['insee_code']?:$row['city_name'],'active'=>(bool)$row['is_active'],'mode'=>$row['content_mode'],'status'=>$row['status'],'content'=>$row['content_payload']?json_decode((string)$row['content_payload'],true):[]];}
        return Response::json(['version'=>1,'pages'=>$pages]);
    }

    public function generate(Request $request,array $params):Response
    {
        $local=LocalPage::find((int)($params['id']??0));if(!$local||!$this->ownedService((int)$local->getAttribute('company_service_id')))return Response::json(['error'=>'Page locale introuvable.'],404);
        try{$page=$this->generator->generate($local);Cache::flush();$this->log('local_page.generate','LocalPage',$local->id(),'Génération de '.$page->getAttribute('slug'));return Response::json(['ok'=>true,'id'=>$local->id(),'url'=>'/'.$page->getAttribute('slug')]);}catch(Throwable $e){return Response::json(['error'=>$e->getMessage()],422);}
    }

    private function rows(?string $generationStatus=null):array
    {
        $lp=$this->db->table('local_pages');$cs=$this->db->table('company_services');$ct=$this->db->table('cities');$pg=$this->db->table('pages');$company=Company::current();if(!$company)return[];
        $sql="SELECT lp.*,cs.public_name service_name,cs.slug service_slug,c.name city_name,c.postal_code,c.insee_code,p.slug page_slug FROM {$lp} lp JOIN {$cs} cs ON cs.id=lp.company_service_id JOIN {$ct} c ON c.id=lp.city_id LEFT JOIN {$pg} p ON p.id=lp.page_id WHERE cs.company_id=?";$args=[$company->id()];if($generationStatus!==null){$sql.=' AND lp.generation_status=?';$args[]=$generationStatus;}$sql.=' ORDER BY cs.public_name,c.name';return $this->db->select($sql,$args);
    }

    private function ownedService(int $id):bool{$company=Company::current();$service=CompanyService::find($id);return $company&&$service&&(int)$service->getAttribute('company_id')===(int)$company->id();}
    private function allowedCity(int $id):bool{$company=Company::current();if(!$company)return false;$table=$this->db->table('company_locations');return $this->db->selectOne("SELECT id FROM {$table} WHERE company_id=? AND city_id=? AND is_active=1",[$company->id(),$id])!==null;}
    private function activateServiceLocation(int $serviceId,int $cityId):void{$link=ServiceLocation::first(['company_service_id'=>$serviceId,'city_id'=>$cityId])??new ServiceLocation();$link->fill(['company_service_id'=>$serviceId,'city_id'=>$cityId,'is_active'=>true])->save();}
    private function resolveService(mixed $value):?CompanyService{if(is_numeric($value)){$service=CompanyService::find((int)$value);return $service&&$this->ownedService((int)$service->id())?$service:null;}$company=Company::current();return $company?CompanyService::first(['company_id'=>$company->id(),'slug'=>(string)$value]):null;}
    private function resolveCity(mixed $value):?City
    {
        $company=Company::current();$value=trim((string)$value);if(!$company||$value==='')return null;
        $cities=$this->db->table('cities');$locations=$this->db->table('company_locations');
        $row=$this->db->selectOne("SELECT c.id FROM {$cities} c JOIN {$locations} cl ON cl.city_id=c.id WHERE cl.company_id=? AND cl.is_active=1 AND (c.id=? OR c.insee_code=? OR c.slug=? OR c.name=?) LIMIT 1",[$company->id(),is_numeric($value)?(int)$value:0,$value,$value,$value]);
        return $row?City::find((int)$row['id']):null;
    }
}
