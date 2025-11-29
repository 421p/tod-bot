<?php

namespace NapevBot\Service;

use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

class I18n
{
    private static ?Translator $translator = null;

    public static function translator(): Translator
    {
        if (self::$translator !== null) {
            return self::$translator;
        }

        $locale = getenv('BOT_LOCALE');
        if (!$locale) {
            $locale = 'ru';
        }

        $translator = new Translator($locale);
        $translator->addLoader('array', new ArrayLoader());

        $baseDir = dirname(__DIR__, 2) . '/translations';
        // Register resources for English and Russian from PHP array files to avoid YAML dependency
        if (is_dir($baseDir)) {
            $ru = $baseDir . '/messages.ru.php';
            if (file_exists($ru)) {
                /** @var array $data */
                $data = include $ru;
                if (is_array($data)) {
                    $translator->addResource('array', $data, 'ru');
                }
            }
            $en = $baseDir . '/messages.en.php';
            if (file_exists($en)) {
                /** @var array $dataEn */
                $dataEn = include $en;
                if (is_array($dataEn)) {
                    $translator->addResource('array', $dataEn, 'en');
                }
            }
        }

        self::$translator = $translator;
        return self::$translator;
    }

    public static function t($key, array $params = []): string
    {
        return self::translator()->trans($key, $params);
    }
}
