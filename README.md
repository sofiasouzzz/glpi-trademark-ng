# Trademark for GLPI 12+

Customize logos, login background, favicons, and custom CSS/SCSS styles in GLPI.

> **Note:** This is a maintained fork updated for compatibility and security hardening with **GLPI 12.0+**.  
> For older GLPI versions (`<= 10.x` or `<= 9.5`), please refer to the upstream repository: [edgardmessias/glpi-trademark](https://github.com/edgardmessias/glpi-trademark).
>
> This fork currently requires GLPI 12.0.0 through versions before 12.9.9, and PHP 8.5.0 or newer.

## Features
* Custom login screen background and logos
* Custom internal header logos and favicons
* Custom SCSS/CSS injection per theme
* Hardened public endpoints (`login.css.php`) compatible with GLPI 12 Firewall strategies

## Installation

### Via Git (Recommended)
1. Clone this repository directly into your GLPI `plugins` directory:
   ```bash
   cd /var/www/html/glpi/plugins
   git clone [https://github.com/sofiasouzzz/trademark-glpi.git](https://github.com/sofiasouzzz/trademark-glpi.git) trademark
