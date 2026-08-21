<?php
declare(strict_types=1);
namespace App\Controllers\Public;
use App\Core\Request;
use App\Core\Response;
use App\Models\ConversionEvent;
use App\Services\Tracking\HumanSignalService;
final class TrackingController
{
    public function __construct(private HumanSignalService $humans) {}
    public function event(Request $request): Response
    {
        $allowed=['button_click','link_click','call_click','whatsapp_click','form_attempt','form_conversion'];
        $type=(string)$request->input('event_type','button_click');if(!in_array($type,$allowed,true))$type='button_click';
        $assessment=$this->humans->assess($request);
        ConversionEvent::create(['event_type'=>$type,'element_text'=>mb_substr(trim((string)$request->input('element_text','')),0,255)?:null,'target_url'=>mb_substr(trim((string)$request->input('target_url','')),0,500)?:null,'page_path'=>mb_substr(trim((string)$request->input('page_path','/')),0,500),'session_id'=>mb_substr(trim((string)$request->input('session_id','')),0,64)?:null,'ip_hash'=>hash('sha256',$request->ip().'|'.csrf_token()),'user_agent'=>mb_substr($request->userAgent(),0,255),'is_human'=>$assessment['is_human'],'bot_score'=>$assessment['score'],'rejection_reason'=>$assessment['reasons']?implode(',',$assessment['reasons']):null,'metadata'=>['interactions'=>(int)$request->input('_human_interactions',0),'elapsed'=>(int)$request->input('_human_elapsed',0)]]);
        return Response::json(['ok'=>true,'human'=>$assessment['is_human']]);
    }
}
