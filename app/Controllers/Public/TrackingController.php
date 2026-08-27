<?php
declare(strict_types=1);
namespace App\Controllers\Public;
use App\Core\Request;
use App\Core\Response;
use App\Models\ConversionEvent;
use App\Services\Tracking\HumanSignalService;
use App\Support\Crypto;
final class TrackingController
{
    public function __construct(private HumanSignalService $humans) {}
    public function event(Request $request): Response
    {
        $allowed=['button_click','link_click','call_click','whatsapp_click','form_attempt','form_conversion'];
        $type=(string)$request->input('event_type','button_click');if(!in_array($type,$allowed,true))$type='button_click';
        $session=mb_substr(trim((string)$request->input('session_id','')),0,64);
        if($type==='call_click'&&$session!==''){$recent=ConversionEvent::where(['event_type'=>'call_click','session_id'=>$session],'created_at DESC',1)[0]??null;if($recent&&time()-strtotime((string)$recent->getAttribute('created_at'))<15)return Response::json(['ok'=>true,'human'=>(bool)$recent->getAttribute('is_human'),'deduplicated'=>true]);}
        $assessment=$this->humans->assess($request);
        $city=trim((string)$request->header('CF-IPCity',''));$country=trim((string)$request->header('CF-IPCountry',''));$location=trim(implode(', ',array_filter([$city,$country])))?:mb_substr(trim((string)$request->input('page_title','')),0,255);
        ConversionEvent::create(['event_type'=>$type,'element_text'=>mb_substr(trim((string)$request->input('element_text','')),0,255)?:null,'target_url'=>mb_substr(trim((string)$request->input('target_url','')),0,500)?:null,'page_path'=>mb_substr(trim((string)$request->input('page_path','/')),0,500),'location_label'=>$location?:null,'session_id'=>mb_substr(trim((string)$request->input('session_id','')),0,64)?:null,'ip_hash'=>hash('sha256',$request->ip().'|'.csrf_token()),'ip_encrypted'=>Crypto::encrypt($request->ip(),(string)config('app.key','')),'user_agent'=>mb_substr($request->userAgent(),0,255),'is_human'=>$assessment['is_human'],'bot_score'=>$assessment['score'],'rejection_reason'=>$assessment['reasons']?implode(',',$assessment['reasons']):null,'metadata'=>['interactions'=>(int)$request->input('_human_interactions',0),'elapsed'=>(int)$request->input('_human_elapsed',0)]]);
        return Response::json(['ok'=>true,'human'=>$assessment['is_human']]);
    }

    /**
     * Compteur public de visites humaines (les robots ne comptent pas).
     * GET /track/stats — cache 60 s pour tenir la charge.
     */
    public function stats(Request $request): Response
    {
        $payload = \App\Core\Cache::remember('visit_stats_public', 60, static function (): array {
            $pdo = \App\Core\Database::instance()->pdo();
            $total = (int) $pdo->query("SELECT COUNT(*) FROM artisan_visits WHERE is_bot = 0 AND status_code < 400")->fetchColumn();
            $today = (int) $pdo->query("SELECT COUNT(*) FROM artisan_visits WHERE is_bot = 0 AND status_code < 400 AND visited_at >= CURDATE()")->fetchColumn();
            $month = (int) $pdo->query("SELECT COUNT(*) FROM artisan_visits WHERE is_bot = 0 AND status_code < 400 AND visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
            return ['total' => $total, 'today' => $today, 'month' => $month];
        });

        $raw = (string) (\App\Models\Setting::first(['key' => 'visits.counter_offset'])?->getAttribute('value') ?? '0');
        $offset = max(0, (int) (json_decode($raw, true) ?? 0));

        return Response::json([
            'ok' => true,
            'total' => $payload['total'] + $offset,
            'today' => $payload['today'],
            'month' => $payload['month'],
        ]);
    }
}
