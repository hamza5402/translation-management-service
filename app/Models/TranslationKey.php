<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationKey extends Model
{
    protected $fillable = ['key','description'];

    public function translations(){
        return $this->hasMany(Translation::class);
    }

    public function tags(){
        return $this->belongsToMany(Tag::class, 'tag_translation_key');
    }

    public function getTranslationByLocale(string $localeCode){
        return $this->translations->firstWhere('locale.code', $localeCode) ?? null;
    }
}
