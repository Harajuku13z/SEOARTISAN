<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
final class ConversionEvent extends Model
{
    protected static string $table = 'conversion_events';
    protected static array $fillable = ['event_type','element_text','target_url','page_path','session_id','ip_hash','user_agent','is_human','bot_score','rejection_reason','metadata'];
    protected static array $casts = ['is_human'=>'bool','bot_score'=>'int','metadata'=>'json'];
}
