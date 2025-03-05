<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Show extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tmdb_id',
        'title',
        'formatted_title',
        'type',
        'background_image',
        'logo_image'
    ];
    
    /**
     * Get the lists that contain this show.
     */
    public function lists()
    {
        return $this->belongsToMany(ShowList::class, 'show_list_show')
                    ->withTimestamps();
    }
    
    /**
     * Get the users who favorited this show.
     */
    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'user_favorites')
                    ->withTimestamps();
    }
    
    /**
     * Format title according to specifications (remove accents, special chars, replace spaces with underscores)
     * 
     * @param string $title
     * @return string
     */
    public static function formatTitle($title)
    {
        return ShowList::formatTitle($title);
    }
}
