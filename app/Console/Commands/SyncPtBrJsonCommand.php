<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;

class SyncPtBrJson extends Command
{
    protected $signature = 'translate
        {--paths=app,resources/views : Pastas relativas ao base_path separadas por vírgula}
        {--file=lang/pt_BR.json : Caminho do JSON relativo ao base_path}
        {--dry : Apenas mostra o que seria adicionado, sem salvar}
    ';

    protected $description = "Varre o código por __('...') e adiciona chaves faltantes em lang/pt_BR.json";

    public function handle(): int
    {
        $paths = array_filter(array_map('trim', explode(',', (string) $this->option('paths'))));
        $langFile = base_path((string) $this->option('file'));
        $dry = (bool) $this->option('dry');

        // Lê JSON atual (ou cria vazio)
        $existing = [];
        if (File::exists($langFile)) {
            $existing = json_decode(File::get($langFile), true) ?: [];
        }

        $keysFound = [];

        foreach ($paths as $relativePath) {
            $dir = base_path($relativePath);

            if (! File::isDirectory($dir)) {
                $this->warn("Pasta não encontrada: {$relativePath}");

                continue;
            }

            $files = File::allFiles($dir);

            foreach ($files as $file) {
                $ext = strtolower($file->getExtension());
                if (! in_array($ext, ['php', 'blade.php', 'blade'], true)) {
                    // allFiles retorna extensão como 'php'. blade.php vem como 'php'
                    // então vamos só filtrar por .php e garantir que blade também entra.
                }

                $content = File::get($file->getRealPath());

                // Captura __('...') e __("...")
                $patterns = [
                    '/__\(\s*[\'"](.+?)[\'"]\s*[\),]/s',
                    '/@lang\(\s*[\'"](.+?)[\'"]\s*\)/s',
                ];

                foreach ($patterns as $pattern) {
                    if (preg_match_all($pattern, $content, $matches)) {
                        foreach ($matches[1] as $rawKey) {
                            $key = trim($rawKey);

                            // Ignora coisas que não são literais simples (ex: __('foo '.$bar))
                            if ($key === '' || str_contains($key, '...') || str_contains($key, '$') || str_contains($key, '{') || str_contains($key, '}')) {
                                continue;
                            }

                            $keysFound[$key] = true;
                        }
                    }
                }
            }
        }

        $keysFound = array_keys($keysFound);
        sort($keysFound, SORT_NATURAL | SORT_FLAG_CASE);

        $toAdd = [];
        foreach ($keysFound as $key) {
            if (! array_key_exists($key, $existing)) {

                $response = Prism::text()
                    ->using(Provider::Anthropic, 'claude-haiku-4-5-20251001')
                    ->withPrompt('Informe a tradução direta do texto "'.$key.'" em português do Brasil. Apenas a tradução direta, sem explicações, pois vou adicionar esse texto diretamente em uma label na minha aplicação.')
                    ->asText();

                $toAdd[$key] = $response->text;
            }
        }

        if (empty($toAdd)) {
            $this->info('Nada para adicionar. Seu pt_BR.json já está sincronizado ✅');

            return self::SUCCESS;
        }

        $this->info('Chaves novas encontradas: '.count($toAdd));
        foreach (array_keys($toAdd) as $k) {
            $this->line(" + {$k}");
        }

        if ($dry) {
            $this->warn('Modo --dry: não salvei o arquivo.');

            return self::SUCCESS;
        }

        // Mescla e ordena pelo key (opcional, mas deixa organizado)
        $merged = $existing + $toAdd;
        // ksort($merged, SORT_NATURAL | SORT_FLAG_CASE);

        File::ensureDirectoryExists(dirname($langFile));
        File::put(
            $langFile,
            json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL
        );

        $this->info('Arquivo atualizado: '.str_replace(base_path().'/', '', $langFile));

        return self::SUCCESS;
    }
}
