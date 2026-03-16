# Veloura App Updater

## Included pieces

- `version.json` current installed version
- `updates/latest-version.json` latest release manifest
- `updates/remote-latest-version.json` GitHub-ready remote manifest template
- `check-update.php` version checker endpoint
- `update-app.php` one-click updater endpoint
- `app-settings.php` saves updater settings like the remote manifest URL
- `includes/app_update.php` shared updater logic
- `migrations/` database migrations

## How it works

1. `check-update.php` reads the current version and the latest manifest.
2. It compares versions and reports whether an update is available.
3. `update-app.php` creates a backup zip in `backups/`.
4. It copies files from the configured release package.
5. It runs pending migrations from `migrations/`.
6. It updates `config.php` with the new version and timestamps.

## Release source

By default the app reads:

- `updates/latest-version.json`

You can later point `config.php` to a remote manifest by setting:

- `updates.manifest_url`

That remote manifest can live on GitHub Releases, your own server, or any public JSON URL.

You can also save that URL from the admin `My App` screen.

## GitHub release ZIP flow

1. Create a GitHub release like `v1.1.0`.
2. Upload a ZIP package such as `veloura-release-1.1.0.zip`.
3. Put that ZIP URL in `package_url` inside your remote manifest JSON.
4. Save the manifest URL in the admin `My App` panel.
5. Run `Check for Updates`, then `Update App`.

## Recommended production flow

1. Keep source code in GitHub.
2. Build a release package for each version.
3. Upload the release package to your server or publish it from GitHub Releases.
4. Update the manifest with the new version and `package_url`.
5. Use `Check for Updates` and `Update App` from the admin area.

## Notes

- `config.php` is preserved during updates.
- A backup zip is created before copying files.
- The included local sample package upgrades the app from `1.0.0` to `1.1.0`.
