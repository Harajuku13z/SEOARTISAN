<?php
declare(strict_types=1);
namespace App\Services\Content;
use App\Core\Database;
use App\Models\Company;
final class LocalPageLinker
{
    public function __construct(private Database $db){}
    public function forWordPressPost(array $post,int $limit=6):array
    {
        $company=Company::current();if(!$company)return[];$lp=$this->db->table('local_pages');$cs=$this->db->table('company_services');$ct=$this->db->table('cities');$pg=$this->db->table('pages');
        $rows=$this->db->select("SELECT cs.public_name service,c.name city,p.title,p.slug FROM {$lp} lp JOIN {$cs} cs ON cs.id=lp.company_service_id JOIN {$ct} c ON c.id=lp.city_id JOIN {$pg} p ON p.id=lp.page_id WHERE cs.company_id=? AND lp.is_active=1 AND lp.status='published' AND lp.generation_status='generated' AND p.status='published' AND p.indexable=1",[$company->id()]);
        $haystack=$this->normalize(strip_tags((string)($post['title']['rendered']??'').' '.(string)($post['excerpt']['rendered']??'').' '.(string)($post['content']['rendered']??'')));$scored=[];
        foreach($rows as $row){$score=0;$service=$this->normalize((string)$row['service']);$city=$this->normalize((string)$row['city']);if($service!==''&&str_contains($haystack,$service))$score+=3;if($city!==''&&str_contains($haystack,$city))$score+=2;if($score>0)$scored[]=['score'=>$score,'title'=>(string)$row['title'],'url'=>'/'.ltrim((string)$row['slug'],'/'),'service'=>(string)$row['service'],'city'=>(string)$row['city']];}
        usort($scored,static fn($a,$b)=>$b['score']<=>$a['score']);return array_map(static function($row){unset($row['score']);return $row;},array_slice($scored,0,$limit));
    }
    private function normalize(string $value):string{$value=mb_strtolower($value,'UTF-8');return strtr($value,['à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ç'=>'c','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u']);}
}
