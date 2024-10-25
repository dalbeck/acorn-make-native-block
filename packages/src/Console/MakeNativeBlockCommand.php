<?php

namespace DANativeBlocks\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeNativeBlock extends Command
{
    protected $signature = 'make:native-block {name} {--namespace=DANativeBlocks}';
    protected $description = 'Create a new native WordPress block with full setup.';

    public function handle(Filesystem $files)
    {
        $blockName = Str::slug($this->argument('name')); // Block name as slug
        $namespace = Str::slug($this->option('namespace')); // Default to DANativeBlocks or user-defined namespace

        // Define paths
        $blocksPath = resource_path("blocks/{$blockName}");
        $blockFiles = [
            "{$blocksPath}/block.json",
            "{$blocksPath}/index.js",
            "{$blocksPath}/edit.js",
            "{$blocksPath}/save.js",
            "{$blocksPath}/script.js",
            "{$blocksPath}/editor.scss",
            "{$blocksPath}/{$blockName}.scss",
        ];

        // Create directories
        if (!$files->exists($blocksPath)) {
            $files->makeDirectory($blocksPath, 0755, true);
        }

        // Create each file with some default content
        foreach ($blockFiles as $file) {
            $files->put($file, $this->getFileTemplate($file, $blockName, $namespace));
        }

        // Update Bud configuration in bud.config.js to add block entry point
        $budConfigPath = base_path('bud.config.js');
        $budConfigContent = $files->get($budConfigPath);
        $entryConfig = ".entry('blocks/{$blockName}', ['@blocks/{$blockName}/index.js'])";

        if (!Str::contains($budConfigContent, $entryConfig)) {
            $updatedConfig = preg_replace(
                '/(app\n\s*\.[a-z]+\(\'.*?\'\)\n\s*\.[a-z]+\(\'.*?\'\))/',
                "$1\n    $entryConfig",
                $budConfigContent
            );
            $files->put($budConfigPath, $updatedConfig);
        }

        // Update setup.php to automatically enqueue assets for the new block
        $setupPath = base_path('config/setup.php');
        $setupContent = $files->get($setupPath);

        if (!Str::contains($setupContent, 'enqueue_block_editor_assets')) {
            $files->put(
                $setupPath,
                $setupContent . <<<PHP

// Automatically enqueue assets for blocks in /resources/blocks/
add_action('wp_enqueue_scripts', function () {
    bundle('app')->enqueue();

    // Enqueue styles and scripts for each block
    \$block_dirs = glob(get_theme_file_path('/resources/blocks/*'), GLOB_ONLYDIR);
    foreach (\$block_dirs as \$block_dir) {
        \$block_name = basename(\$block_dir);
        bundle("blocks/\$block_name")->enqueue();
    }
}, 100);

add_action('enqueue_block_editor_assets', function () {
    bundle('editor')->enqueue();

    // Enqueue editor assets for each block
    \$block_dirs = glob(get_theme_file_path('/resources/blocks/*'), GLOB_ONLYDIR);
    foreach (\$block_dirs as \$block_dir) {
        \$block_json_path = \$block_dir . '/block.json';

        // Check if block.json exists
        if (file_exists(\$block_json_path)) {
            \$block_json = json_decode(file_get_contents(\$block_json_path), true);
            \$block_name_with_namespace = \$block_json['name'];
            \$block_name_parts = explode('/', \$block_name_with_namespace);
            \$namespace = \$block_name_parts[0];
            \$block_name = \$block_name_parts[1];

            wp_enqueue_script(
                "\$namespace-\$block_name-block",
                asset("js/blocks/\$block_name.js")->uri(),
                ['wp-blocks', 'wp-element', 'wp-editor'],
                null,
                true
            );
        }
    }
}, 100);
PHP
            );
        }

        $this->info("Block {$blockName} created successfully in namespace {$namespace}.");
    }

    /**
     * Generate default content for block files.
     */
    protected function getFileTemplate($file, $blockName, $namespace)
    {
        switch (pathinfo($file, PATHINFO_BASENAME)) {
            case 'block.json':
                return json_encode([
                    'apiVersion' => 3,
                    'name' => "{$namespace}/{$blockName}",
                    'title' => Str::title(str_replace('-', ' ', $blockName)),
                    'category' => 'widgets',
                    'icon' => 'smiley',
                    'description' => "A {$blockName} block for demonstration.",
                    'supports' => ['html' => false],
                    'textdomain' => $namespace,
                    'editorScript' => "file:./index.js",
                    'editorStyle' => "file:./editor.css",
                    'style' => "file:./{$blockName}.css",
                    'script' => "file:./script.js",
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            case 'index.js':
                return "import './editor.scss';\nimport './{$blockName}.scss';\nimport { registerBlockType } from '@wordpress/blocks';\nimport Edit from './edit';\n\nregisterBlockType('{$namespace}/{$blockName}', {\n  edit: Edit,\n  save: () => null,\n});";
            case 'edit.js':
                return "const Edit = () => <div>Edit {$blockName} Block</div>;\nexport default Edit;";
            case 'save.js':
                return "const Save = () => <div>Save {$blockName} Block</div>;\nexport default Save;";
            case 'script.js':
                return "console.log('{$blockName} block loaded.');";
            case 'editor.scss':
            case "{$blockName}.scss":
                return "/* Styles for the {$blockName} block */";
            default:
                return '';
        }
    }
}
