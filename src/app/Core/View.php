<?php

declare(strict_types=1);

namespace App\Core;

use Smarty\Smarty;

final class View
{
    private static ?Smarty $smarty = null;

    public static function engine(): Smarty
    {
        if (self::$smarty instanceof Smarty) {
            return self::$smarty;
        }

        $base = \dirname(__DIR__, 2);

        $smarty = new Smarty();
        $smarty->setTemplateDir($base . '/app/Views/templates/');
        $smarty->setCompileDir($base . '/var/cache/smarty/compile/');
        $smarty->setCacheDir($base . '/var/cache/smarty/cache/');
        $smarty->setCaching(Smarty::CACHING_OFF);
        $smarty->setEscapeHtml(true);

        if (getenv('APP_DEBUG') === 'true') {
            $smarty->setForceCompile(true);
        }

        self::$smarty = $smarty;

        return $smarty;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = []): string
    {
        $smarty = self::engine();
        $smarty->assign($data);

        return $smarty->fetch(self::normalize($template));
    }

    private static function normalize(string $template): string
    {
        return str_ends_with($template, '.tpl') ? $template : $template . '.tpl';
    }
}
