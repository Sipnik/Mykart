# cPanel Setup Guide

## Files

- `install.php` one-time installer
- `config.sample.php` sample config structure

## How to use

1. Upload the project to your cPanel `public_html` directory.
2. Create a MySQL database and user from cPanel.
3. Open `https://your-domain.com/install.php`
4. Enter:
   - application URL
   - database host
   - database port
   - database name
   - database user
   - database password
   - admin name
   - admin email
   - admin password
5. Submit the form.

## What the installer does

- creates `config.php`
- creates basic tables:
  - `users`
  - `categories`
  - `products`
- creates the first admin account

## Important

- Delete or protect `install.php` after setup is complete.
- This installer is built for cPanel-style MySQL hosting.
