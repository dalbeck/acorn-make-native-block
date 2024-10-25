# Native Block Maker

An Acorn package that automates the creation of native WordPress blocks for Sage projects.

## Features

- Scaffolds the block structure in `resources/blocks/`
- Adds the necessary provider and Bud configuration
- Updates `setup.php` for `wp_enqueue_scripts` and `enqueue_block_editor_assets`

## Installation

To install the package, add it to your Sage project:

```bash
composer require your-vendor/native-block-maker
```

## Usage

Use the `make:native-block` command to generate a new block:

```bash
wp acorn make:native-block <block-name> --namespace=<your-namespace>
```

### Arguments

- `<block-name>`: The name of the block (e.g., hero-cta)
- `--namespace`: (Optional) The block namespace (default: your-vendor)

### Example

To create a hero-cta block with namespace my-theme, run:

```bash
wp acorn make:native-block hero-cta --namespace=my-theme
```

This command will:

1. Scaffold the block in `resources/blocks/hero-cta/`
2. Update `bud.config.js` to include the block's entry point
3. Add `enqueue_block_editor_assets` and `wp_enqueue_scripts` calls to `setup.php`

## Contributing

[Add information about how to contribute to the project]

## License

[Specify the license for your package]
