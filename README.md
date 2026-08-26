# Ki Reservation

Ki Reservation is a self-hosted appointment/reservation scheduling platform
developed by Ki Software.

## License

Ki Reservation is proprietary software licensed under the [Ki Software
License Agreement](LICENSE). Use of this Software -- as a Local/Self-Hosted
Deployment, a Cloud Deployment, or a Managed/SaaS Deployment -- requires
prior written permission from Ki Software. See the LICENSE file for full
terms.

Third-party open-source components bundled with Ki Reservation (see
`vendor/` and `system/`) remain governed by their own respective licenses.

## Support

- Issues: https://github.com/miracmk/ki-reservation/issues
- Website: https://kisoftware.com
- Contact: info@kisoftware.com

## Requirements

- PHP >= 8.2 with the `curl`, `json`, `mbstring`, `gd`, `simplexml`, and
  `fileinfo` extensions
- MySQL / MariaDB
- Composer (for dependency management)

## Installation

1. Copy `config-sample.php` to `config.php` and fill in your database
   credentials and base URL.
2. Point your web server's document root at this directory.
3. Visit the application in your browser to complete the guided
   installation.

## Deployment note

Ki Reservation is typically deployed via the accompanying Docker image
build (see the deploying project's `Dockerfile`), which copies this
repository's contents into the application container.
