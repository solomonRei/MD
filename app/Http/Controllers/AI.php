<?php

namespace App\Http\Controllers;
use Sentiment\Analyzer;
use LanguageDetection\Language;
use Stichoza\GoogleTranslate\GoogleTranslate;

class AI extends Controller
{

    /**
     * @throws \Stichoza\GoogleTranslate\Exceptions\LargeTextException
     * @throws \Stichoza\GoogleTranslate\Exceptions\RateLimitException
     * @throws \Stichoza\GoogleTranslate\Exceptions\TranslationRequestException
     */
    public function index()
    {
        $analyzer = new Analyzer();

        $output_text = $analyzer->getSentiment("David is smart, handsome, and funny.");

        $output_emoji = $analyzer->getSentiment("😁");

        $output_text_with_emoji = $analyzer->getSentiment("Aproko doctor made me 🤣.");

        $ld = new Language;

        echo GoogleTranslate::trans('Пошел нахуй?', $ld->detect('Пошел нахуй?'));

        print_r($output_text);
        print_r($output_emoji);
        print_r($output_text_with_emoji);
    }
}
