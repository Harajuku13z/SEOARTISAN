<?php
declare(strict_types=1);
namespace App\Controllers\Admin;
use App\Core\Request;
use App\Models\ConversionEvent;
use App\Models\Setting;
final class ConversionsController extends AdminController
{
    public function index(Request $request): \App\Core\Response
    {
        $now=new \DateTimeImmutable('now');$today=$now->setTime(0,0)->format('Y-m-d H:i:s');$week=$now->modify('-7 days')->format('Y-m-d H:i:s');$month=$now->modify('-30 days')->format('Y-m-d H:i:s');
        $data=['events'=>ConversionEvent::where([],'created_at DESC',200),'callsTotal'=>ConversionEvent::humanCallCount(),'callsToday'=>ConversionEvent::humanCallCount($today),'callsWeek'=>ConversionEvent::humanCallCount($week),'callsMonth'=>ConversionEvent::humanCallCount($month),'humanForms'=>ConversionEvent::count(['event_type'=>'form_conversion','is_human'=>1]),'rejected'=>ConversionEvent::count(['is_human'=>0])];
        $read=Setting::first(['key'=>'tracking.calls_read_at'])??new Setting();$read->fill(['key'=>'tracking.calls_read_at','value'=>json_encode($now->format('Y-m-d H:i:s')),'autoload'=>1])->save();
        return $this->render('admin.conversions.index',$data,'conversions');
    }
}
