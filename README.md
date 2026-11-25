<div align="center">

# 🎨 Trademark NG for GLPI 12+

**Make GLPI look like your brand.**
Customize logos, login background, favicons, and custom CSS/SCSS styles, all from the GLPI interface.

![GLPI](https://img.shields.io/badge/GLPI-%3E%3D%2012.0-blue)
![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.5.0-777bb4)
![Plugin folder](https://img.shields.io/badge/folder-trademark-orange)

[Features](#features) · [Requirements](#requirements) · [Installation](#installation) · [Usage](#usage)

</div>

---

> **Note:** This is a maintained fork updated for compatibility and security hardening with **GLPI 12.0+**.
> For older GLPI versions (`<= 10.x` or `<= 9.5`), please refer to the upstream repository: [edgardmessias/glpi-trademark](https://github.com/edgardmessias/glpi-trademark).

## Features

| | |
|---|---|
| 🖼️ **Login screen** | Custom background and logos |
| 🏷️ **Interface branding** | Custom internal header logos and favicons |
| 🎨 **Custom styles** | SCSS/CSS injection per theme |


## Requirements

| Component | Version |
|-----------|---------|
| GLPI      | 12 or newer |
| PHP       | 8.5 or newer |

## Installation

> ⚠️ **Important:** the plugin folder **must be named `trademark`** (all lowercase). If it has any other name, GLPI will not recognize the plugin. See [Folder name](#folder-name-important) below.

### Via Git (Recommended)

1. Clone this repository directly into your GLPI `plugins` directory, already using the correct folder name:

   ```bash
   cd /var/www/html/glpi/plugins
   git clone https://github.com/sofiasouzzz/trademark-glpi.git trademark
   cd trademark
   composer install --no-dev --no-interaction
   ```

2. Make sure the web server user can read the files:

   ```bash
   chown -R www-data:www-data /var/www/html/glpi/plugins/trademark
   ```

### Manual installation (release archive)

1. Open the latest GitHub release and, under **Assets**, download `trademark.zip` or `trademark.tar.gz`.
2. Extract the archive into the GLPI `plugins` directory. It contains the correctly named `trademark/` folder and is ready to install.

Do not download GitHub's automatically generated **Source code (zip)** or **Source code (tar.gz)** links. Those archives use a versioned repository folder name (such as `glpi-trademark-ng-1.0.0`) that GLPI does not recognize.

The release workflow creates and attaches the installable assets when a release is published. To package an existing release, open **Actions > Package GLPI plugin release > Run workflow**, enter the release tag, and run it. Download the named archives from the release's **Assets** only after the workflow completes successfully.

### Installing from source

If installing from a GitHub source archive or clone, rename the extracted directory to `trademark` if needed, then install runtime dependencies from that directory:

```bash
composer install --no-dev --no-interaction
```

### Activating the plugin

1. Log in to GLPI as a super-admin.
2. Go to **Setup > Plugins**.
3. Find **Trademark NG** in the list, click **Install**, then **Enable**.

## Usage

After enabling the plugin, open **Setup > Trademark NG** to:

* Upload your login background, logos, and favicons.
* Add custom CSS/SCSS for each theme.

## Development

Install the development dependencies and run the PHPUnit suite:

```bash
composer install
composer test
```

## Folder name (important)

GLPI identifies a plugin by the name of its directory, which must match the plugin key. The release archives already contain the required `trademark/` directory. When installing from a source archive, rename the extracted directory to `trademark`:

```bash
mv trademark-glpi trademark
```

The final path must look like this:

```
glpi/
└── plugins/
    └── trademark/
        ├── setup.php
        └── ...
```

If you cloned the repository without specifying a folder name, the directory may be called `glpi-trademark-ng` (or include the release version). GLPI will **not** detect the plugin under that name. Rename it to `trademark` and reload the **Setup > Plugins** page.
