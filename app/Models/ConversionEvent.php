<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
use App\Support\Crypto;
final class ConversionEvent extends Model
{
    protected static string $table = 'conversion_events';
    protected static array $fillable = ['event_type','element_text','target_url','page_path','location_label','session_id','ip_hash','ip_encrypted','user_agent','is_human','bot_score','rejection_reason','metadata'];
    protected static array $casts = ['is_human'=>'bool','bot_score'=>'int','metadata'=>'json'];
    public function ipAddress(): ?string { $value=(string)$this->getAttribute('ip_encrypted');if($value==='')return null;try{return Crypto::decrypt($value,(string)config('app.key',''));}catch(\Throwable){return null;} }
}
