<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShowList extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'shows'
    ];

    protected $casts = [
        'shows' => 'array'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function formatShowData($show)
    {
        return [
            'tmdb_id' => $show['tmdb_id'],
            'title' => $show['title'],
            'formatted_title' => self::formatTitle($show['title']),
            'background_image' => $show['background_image'],
            'logo_image' => $show['logo_image'],
            'type' => $show['type']
        ];
    }

    public static function formatTitle($title)
    {
        // Remove letters with accents by removing them completely
        $title = preg_replace('/[áàâãäåÁÀÂÃÄÅ]/', '', $title);  // Remove a's with accents
        $title = preg_replace('/[éèêëÉÈÊË]/', '', $title);      // Remove e's with accents
        $title = preg_replace('/[íìîïÍÌÎÏ]/', '', $title);      // Remove i's with accents
        $title = preg_replace('/[óòôõöÓÒÔÕÖ]/', '', $title);    // Remove o's with accents
        $title = preg_replace('/[úùûüÚÙÛÜ]/', '', $title);      // Remove u's with accents
        $title = preg_replace('/[ñÑ]/', '', $title);            // Remove ñ's
        $title = preg_replace('/[çÇ]/', '', $title);            // Remove ç's
        
        // Remove special characters
        $title = preg_replace('/[\(\)\[\]\{\}:;,\.\'\"!¡\?¿&%\$#@\*\+\-\/\\\\]/', '', $title);
        
        // Replace spaces with underscores
        $title = preg_replace('/\s+/', '_', $title);
        
        return strtolower($title);
    }
}
