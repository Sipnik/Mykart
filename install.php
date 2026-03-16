<?php
declare(strict_types=1);

$configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$installed = false;

if (file_exists($configFile)) {
    $existingConfig = include $configFile;
    $installed = is_array($existingConfig) && !empty($existingConfig['installed']);
}

$errors = [];
$success = '';

function create_tables(mysqli $mysqli): void
{
    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(180) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(30) NOT NULL DEFAULT 'admin',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            description TEXT NULL,
            parent_id INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_category_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS products (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id INT UNSIGNED NULL,
            name VARCHAR(160) NOT NULL,
            description TEXT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            sale_price DECIMAL(10,2) NULL,
            rating DECIMAL(3,2) NULL,
            image_url TEXT NULL,
            featured TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($queries as $query) {
        if (!$mysqli->query($query)) {
            throw new RuntimeException('Database setup failed: ' . $mysqli->error);
        }
    }
}

function seed_admin(mysqli $mysqli, string $name, string $email, string $password): void
{
    $check = $mysqli->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->bind_param('s', $email);
    $check->execute();
    $result = $check->get_result();
    if ($result && $result->num_rows > 0) {
        throw new RuntimeException('Admin email already exists in the database.');
    }
    $check->close();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'admin';

    $insert = $mysqli->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $insert->bind_param('ssss', $name, $email, $passwordHash, $role);
    if (!$insert->execute()) {
        throw new RuntimeException('Unable to create admin account: ' . $insert->error);
    }
    $insert->close();
}

function write_config(string $configFile, array $config): void
{
    $export = var_export($config, true);
    $content = "<?php\nreturn " . $export . ";\n";

    if (file_put_contents($configFile, $content) === false) {
        throw new RuntimeException('Unable to write config.php. Check file permissions.');
    }
}

function load_version(string $versionFile): string
{
    if (!file_exists($versionFile)) {
        return '1.0.0';
    }

    $decoded = json_decode((string) file_get_contents($versionFile), true);
    if (!is_array($decoded) || empty($decoded['version'])) {
        return '1.0.0';
    }

    return (string) $decoded['version'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    $versionFile = __DIR__ . DIRECTORY_SEPARATOR . 'version.json';
    $appUrl = trim($_POST['app_url'] ?? '');
    $dbHost = trim($_POST['db_host'] ?? '');
    $dbPort = (int) ($_POST['db_port'] ?? 3306);
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = trim($_POST['db_pass'] ?? '');
    $adminName = trim($_POST['admin_name'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPassword = trim($_POST['admin_password'] ?? '');

    if ($appUrl === '' || $dbHost === '' || $dbName === '' || $dbUser === '' || $adminName === '' || $adminEmail === '' || $adminPassword === '') {
        $errors[] = 'Please fill in all required fields.';
    }

    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid admin email address.';
    }

    if (strlen($adminPassword) < 8) {
        $errors[] = 'Admin password must be at least 8 characters.';
    }

    if (!$errors) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
            $mysqli->set_charset('utf8mb4');

            create_tables($mysqli);
            seed_admin($mysqli, $adminName, $adminEmail, $adminPassword);
            $currentVersion = load_version($versionFile);

            $config = [
                'installed' => true,
                'app_url' => $appUrl,
                'version' => $currentVersion,
                'db' => [
                    'host' => $dbHost,
                    'port' => $dbPort,
                    'name' => $dbName,
                    'user' => $dbUser,
                    'pass' => $dbPass,
                    'charset' => 'utf8mb4',
                ],
                'updates' => [
                    'manifest_url' => '',
                    'manifest_path' => 'updates/latest-version.json',
                    'current_version' => $currentVersion,
                    'last_checked_at' => null,
                    'last_updated_at' => null,
                ],
            ];

            write_config($configFile, $config);
            $success = 'Setup completed successfully. Your database is configured and the admin account was created.';
            $installed = true;
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veloura Setup</title>
    <style>
        :root {
            --bg: #f8f3ed;
            --surface: #ffffff;
            --text: #1d1a18;
            --muted: #6e645c;
            --accent: #b8612e;
            --accent-dark: #8e4418;
            --line: rgba(29, 26, 24, 0.08);
            --danger: #ad2d2d;
            --success: #1f7a52;
            --shadow: 0 24px 60px rgba(58, 35, 20, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--text);
            background: linear-gradient(180deg, #fffdf9, var(--bg));
        }

        .wrap {
            width: min(980px, calc(100% - 2rem));
            margin: 2rem auto;
        }

        .panel {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 32px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .eyebrow {
            display: inline-flex;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: rgba(184, 97, 46, 0.12);
            color: var(--accent-dark);
            text-transform: uppercase;
            font-size: 0.82rem;
            letter-spacing: 0.08em;
        }

        .lead {
            color: var(--muted);
            line-height: 1.7;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .field {
            display: grid;
            gap: 0.45rem;
            margin-top: 1rem;
        }

        input {
            width: 100%;
            padding: 0.95rem 1rem;
            border-radius: 16px;
            border: 1px solid var(--line);
        }

        .button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 0.95rem 1.2rem;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: white;
            cursor: pointer;
            margin-top: 1.5rem;
        }

        .message {
            padding: 1rem 1.1rem;
            border-radius: 18px;
            margin-bottom: 1rem;
        }

        .message.error {
            background: rgba(173, 45, 45, 0.08);
            color: var(--danger);
        }

        .message.success {
            background: rgba(31, 122, 82, 0.08);
            color: var(--success);
        }

        ul {
            margin: 0;
            padding-left: 1.25rem;
        }

        @media (max-width: 800px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="panel">
            <span class="eyebrow">cPanel installer</span>
            <h1>Set up your application</h1>
            <p class="lead">Use this one-time installer after uploading the project to cPanel. It will connect to your MySQL database, create the required tables, and create the first admin account.</p>

            <?php if ($installed && $success === ''): ?>
                <div class="message success">
                    Setup has already been completed. Delete or protect <strong>install.php</strong> after deployment.
                </div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="message error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="message success">
                    <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if (!$installed): ?>
                <form method="post">
                    <div class="grid">
                        <div class="field">
                            <label>Application URL</label>
                            <input type="text" name="app_url" placeholder="https://your-domain.com" value="<?php echo htmlspecialchars($_POST['app_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="field">
                            <label>Database host</label>
                            <input type="text" name="db_host" placeholder="localhost" value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="field">
                            <label>Database port</label>
                            <input type="number" name="db_port" placeholder="3306" value="<?php echo htmlspecialchars($_POST['db_port'] ?? '3306', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="field">
                            <label>Database name</label>
                            <input type="text" name="db_name" placeholder="cpanel_database_name" value="<?php echo htmlspecialchars($_POST['db_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="field">
                            <label>Database user</label>
                            <input type="text" name="db_user" placeholder="cpanel_database_user" value="<?php echo htmlspecialchars($_POST['db_user'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="field">
                            <label>Database password</label>
                            <input type="password" name="db_pass" placeholder="Database password">
                        </div>
                        <div class="field">
                            <label>Admin name</label>
                            <input type="text" name="admin_name" placeholder="Admin user" value="<?php echo htmlspecialchars($_POST['admin_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="field">
                            <label>Admin email</label>
                            <input type="email" name="admin_email" placeholder="admin@example.com" value="<?php echo htmlspecialchars($_POST['admin_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <div class="field">
                        <label>Admin password</label>
                        <input type="password" name="admin_password" placeholder="Minimum 8 characters">
                    </div>
                    <button class="button" type="submit">Run Setup</button>
                </form>
            <?php else: ?>
                <p class="lead">Next step: delete <strong>install.php</strong> from the server or protect it so it cannot be used again.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
