<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sentiment\Analyzer;
use LanguageDetection\Language;
use Stichoza\GoogleTranslate\GoogleTranslate;

class Comment extends Model
{
    protected $fillable = [
        'rating',
        'comment',
        'rating_comment_share_idrating_comment_share_id',
    ];

    public function ai($comment)
    {
        $analyzer = new Analyzer();
        $ld = new Language;
        $translate = GoogleTranslate::trans($comment, 'en');
        if (isset($translate) && !empty($translate)) {
            $output_text = $analyzer->getSentiment($translate);
            $rating = $output_text['compound'];
        }else {
            $rating = 1;
        }
        return $rating;
    }
}
