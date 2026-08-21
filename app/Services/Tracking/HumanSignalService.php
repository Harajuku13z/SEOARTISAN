<?php
declare(strict_types=1);
namespace App\Services\Tracking;
use App\Core\Request;
use App\Repositories\SettingsRepository;
final class HumanSignalService
{
    public function __construct(private SettingsRepository $settings) {}
    /** @return array{is_human:bool,score:int,reasons:array<int,string>} */
    public function assess(Request $request): array
    {
        $score=0;$reasons=[];$ua=mb_strtolower($request->userAgent());
        $elapsed=(int)$request->input('_human_elapsed',0);$interactions=(int)$request->input('_human_interactions',0);
        $minSeconds=max(2,(int)$this->settings->get('tracking.human_min_seconds',4));
        $minInteractions=max(1,(int)$this->settings->get('tracking.human_min_interactions',2));
        if($ua===''||preg_match('/bot|crawler|spider|headless|phantom|selenium|playwright|curl|wget|python|scrapy|httpclient|preview|facebookexternalhit|googlebot|bingbot|claude|gptbot|chatgpt|anthropic/i',$ua)){$score+=100;$reasons[]='agent_automatise';}
        if(trim((string)$request->input('website',''))!==''){$score+=100;$reasons[]='honeypot';}
        if((string)$request->input('_human_webdriver','0')==='1'){$score+=100;$reasons[]='webdriver';}
        if($elapsed<$minSeconds){$score+=35;$reasons[]='delai_trop_court';}
        if($interactions<$minInteractions){$score+=35;$reasons[]='interactions_insuffisantes';}
        if((string)$request->input('_human_visible','1')!=='1'){$score+=20;$reasons[]='page_non_visible';}
        return ['is_human'=>$score<50,'score'=>min(100,$score),'reasons'=>$reasons];
    }
}
