<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $fillable = ['translation_key_id','locale_id','content'];

    public function key() { 
        return $this->belongsTo(TranslationKey::class, 'translation_key_id'); 
    }

    public function locale() { 
        return $this->belongsTo(Locale::class); 
    }
}
