<?php
declare(strict_types=1);
namespace App\Controllers\Admin;
use App\Core\Request;
use App\Models\ConversionEvent;
final class ConversionsController extends AdminController
{
    public function index(Request $request): \App\Core\Response
    {
        return $this->render('admin.conversions.index',['events'=>ConversionEvent::where([],'created_at DESC',200),'humanCalls'=>ConversionEvent::count(['event_type'=>'call_click','is_human'=>1]),'humanForms'=>ConversionEvent::count(['event_type'=>'form_conversion','is_human'=>1]),'rejected'=>ConversionEvent::count(['is_human'=>0])],'conversions');
    }
}
