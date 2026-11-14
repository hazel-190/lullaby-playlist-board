<?php

session_start();
require 'db.php';

$_SESSION['visits'] = ($_SESSION['visits'] ?? 0) + 1;
$visits = $_SESSION['visits'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $author   = trim($_POST['author'] ?? '');
    $body     = trim($_POST['body'] ?? '');
    $remember = isset($_POST['remember']);

    if ($author === '' || $body === '') {
        $error = 'Please add your name and a song.';
    } elseif (mb_strlen($author) > 50) {
        $error = 'Your name must be 50 characters or fewer.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO messages (author, body) VALUES (:author, :body)');
        $stmt->execute([':author' => $author, ':body' => $body]);

        if ($remember) {
            setcookie('listener_name', $author, [
                'expires'  => time() + 60 * 60 * 24 * 30,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            setcookie('listener_name', '', time() - 3600, '/');
        }

        header('Location: index.php');
        exit;
    }
}

$savedName  = $_COOKIE['listener_name'] ?? '';
$nameValue  = $_POST['author'] ?? $savedName;
$remembered = $savedName !== '';

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare(
        'SELECT author, body, created_at FROM messages
         WHERE body ILIKE :term
         ORDER BY created_at DESC, id DESC'
    );
    $stmt->execute([':term' => '%' . $search . '%']);
} else {
    $stmt = $pdo->query('SELECT author, body, created_at FROM messages ORDER BY created_at DESC, id DESC');
}
$posts = $stmt->fetchAll();

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Lullaby Playlist Board</title>
    <script>
        (function () {
            var t = 'light';
            try { t = localStorage.getItem('theme') || 'light'; } catch (e) {}
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
    <style>
        :root {
            --pink: #F7C6DB; --mint: #B3E0DC; --sky: #B8D8EB;
            --ink: #4a4668;
            --card: rgba(255,255,255,.75);
            --soft: rgba(255,255,255,.55);
            --bar: rgba(255,255,255,.9);
            --field: #fff;
            --dialog: #fff;
            --line: #C4C8E0;
            --accent: #F7C6DB;
            --accent-text: #4a4668;
            --hover: #D7D0F5;
            --err: #f7d9e3;
            --shadow: rgba(120,110,170,.25);
            --bg-url: url('bg.svg');
            color-scheme: light;
        }
        :root[data-theme="dark"] {
            --ink: #ece8ff;
            --card: rgba(33,30,68,.82);
            --soft: rgba(33,30,68,.7);
            --bar: rgba(33,30,68,.92);
            --field: #2a2650;
            --dialog: #2a2650;
            --line: #5a5690;
            --accent: #E7A8C6;
            --accent-text: #241f4a;
            --hover: #4a4580;
            --err: #5a3350;
            --shadow: rgba(0,0,10,.5);
            --bg-url: url('bg-night.svg');
            color-scheme: dark;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 0 1rem 6rem;
            font-family: Georgia, 'Times New Roman', serif;
            color: var(--ink);
            background: #D5C7E8 var(--bg-url) center / cover fixed no-repeat;
            min-height: 100vh;
        }
        .wrap { max-width: 920px; margin: 0 auto; }

        .topbar { position: sticky; top: 0; z-index: 10; padding: .8rem 0 .6rem; display: flex; gap: .6rem; align-items: center; }
        .search { flex: 1; min-width: 0; display: flex; align-items: center; gap: .5rem; background: var(--bar); border-radius: 999px; padding: .35rem .5rem .35rem 1rem; box-shadow: 0 4px 16px var(--shadow); }
        .search input { flex: 1; border: 0; background: transparent; font: inherit; color: inherit; padding: .4rem .2rem; min-width: 0; }
        .search input:focus { outline: none; }
        ::placeholder { color: var(--ink); opacity: .55; }
        .search button { background: var(--accent); border: 0; border-radius: 999px; padding: .45rem .9rem; font: inherit; color: var(--accent-text); cursor: pointer; }
        .field { position: relative; flex: 1; min-width: 0; }
        .field input { width: 100%; padding-right: 2rem; }
        .clear { position: absolute; right: .2rem; top: 50%; transform: translateY(-50%); color: var(--ink); text-decoration: none; font-size: 1rem; line-height: 1; padding: .3rem .45rem; border-radius: 50%; opacity: .6; }
        .clear:hover { opacity: 1; background: var(--hover); }
        .theme { flex: none; width: 46px; height: 46px; border-radius: 50%; border: 0; background: var(--bar); color: var(--ink); font-size: 1.25rem; cursor: pointer; box-shadow: 0 4px 16px var(--shadow); }
        .theme:hover { background: var(--hover); }

        header { text-align: center; background: var(--soft); border-radius: 22px; padding: 1rem; margin: .4rem 0 1.25rem; }
        h1 { font-size: 2.1rem; margin: 0; }
        .tagline { margin: .2rem 0; font-style: italic; }
        .counter { font-size: .9rem; margin: .2rem 0; }
        h2 { background: var(--soft); display: inline-block; padding: .2rem .9rem; border-radius: 999px; font-size: 1.1rem; margin: 0 0 1rem; }

        .wall { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem; }
        .card { background: var(--card); border-radius: 18px; padding: 1.1rem; box-shadow: 0 4px 16px var(--shadow); }
        .post { border-top: 6px solid var(--pink); }
        .post:nth-child(3n+2) { border-top-color: var(--mint); }
        .post:nth-child(3n+3) { border-top-color: var(--sky); }
        .meta { font-size: .85rem; opacity: .75; }
        .post p { margin: .5rem 0 0; line-height: 1.5; overflow-wrap: anywhere; }

        .fab { position: fixed; right: calc(1.25rem + env(safe-area-inset-right, 0px)); bottom: calc(1.25rem + env(safe-area-inset-bottom, 0px)); width: 62px; height: 62px; border-radius: 50%; border: 0; background: var(--accent); color: var(--accent-text); font-size: 1.6rem; cursor: pointer; box-shadow: 0 6px 20px var(--shadow); z-index: 20; }
        .fab:hover { background: var(--hover); color: var(--ink); }

        dialog { border: 0; border-radius: 20px; padding: 1.4rem; width: min(480px, 92vw); background: var(--dialog); color: var(--ink); font-family: Georgia, serif; }
        dialog::backdrop { background: rgba(20,18,50,.55); }
        dialog form, dialog > div { position: relative; }
        dialog h3 { margin: 0 0 .5rem; padding-right: 2rem; }
        .close { position: absolute; top: -.2rem; right: -.3rem; background: transparent; border: 0; color: var(--ink); font-size: 1.2rem; line-height: 1; padding: .35rem .5rem; border-radius: 50%; cursor: pointer; opacity: .65; }
        .close:hover { opacity: 1; background: var(--hover); }
        .hint { font-size: .8rem; opacity: .7; margin: .3rem 0 0; }
        label { display: block; font-weight: bold; margin: .6rem 0 .25rem; }
        .check { display: inline; font-weight: normal; }
        input[type=text], textarea { width: 100%; padding: .6rem .8rem; border: 2px solid var(--line); border-radius: 10px; background: var(--field); font: inherit; color: inherit; }
        input[type=text]:focus, textarea:focus { outline: none; border-color: var(--accent); }
        .actions { display: flex; gap: .5rem; margin-top: .8rem; }
        .btn { background: var(--accent); border: 2px solid var(--line); color: var(--accent-text); padding: .5rem 1.2rem; border-radius: 999px; font: inherit; cursor: pointer; }
        .btn:hover { background: var(--hover); color: var(--ink); }
        .error { background: var(--err); color: var(--ink); padding: .5rem .8rem; border-radius: 10px; }
    </style>
</head>
<body>
<div class="wrap">

    <div class="topbar">
        <form method="GET" action="index.php" class="search">
            <span aria-hidden="true">&#128269;</span>
            <div class="field">
                <input type="text" name="search" placeholder="Search by song or mood (e.g. rain)..." value="<?= e($search) ?>" aria-label="Search songs">
                <?php if ($search !== ''): ?><a href="index.php" class="clear" title="Clear search" aria-label="Clear search">&#10005;</a><?php endif; ?>
            </div>
            <button type="submit">Search</button>
        </form>
        <button type="button" class="theme" id="themeBtn" aria-label="Switch theme"></button>
    </div>

    <header>
        <h1>&#9790; Lullaby Playlist Board &#9835;</h1>
        <p class="tagline">Share the calm songs that help the world slow down.</p>
        <p class="counter">You’ve visited <?= (int)$visits ?> time<?= $visits === 1 ? '' : 's' ?> this session.</p>
    </header>

    <h2><?= $search !== '' ? 'Songs matching “' . e($search) . '”' : 'Shared playlist' ?> (<?= count($posts) ?>)</h2>

    <?php if (!$posts): ?>
        <div class="card">No songs yet. Tap the pen to add the first one.</div>
    <?php endif; ?>

    <div class="wall">
        <?php foreach ($posts as $p): ?>
            <div class="card post">
                <strong><?= e($p['author']) ?></strong>
                <span class="meta">&middot; <?= e($p['created_at']) ?></span>
                <p><?= nl2br(e($p['body'])) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<button class="fab" id="openForm" type="button" title="Add a song" aria-label="Add a song">&#9998;</button>

<dialog id="postDialog" data-open="<?= $error ? '1' : '0' ?>">
    <form method="POST" action="index.php" autocomplete="off">
        <button type="button" class="close" id="closeForm" aria-label="Close" title="Close">&#10005;</button>
        <h3>Add a song</h3>
        <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>

        <label for="author">Your name</label>
        <input type="text" id="author" name="author" maxlength="50" autocomplete="off" value="<?= e($nameValue) ?>" required>

        <label for="body">Song, artist &amp; mood</label>
        <textarea id="body" name="body" rows="4" placeholder="Weightless - Marconi Union (calm, rainy night)" required><?= e($_POST['body'] ?? '') ?></textarea>
        <p class="hint">Press Enter to post, Shift+Enter for a new line.</p>

        <p>
            <input type="checkbox" id="remember" name="remember" <?= $remembered ? 'checked' : '' ?>>
            <label for="remember" class="check">Remember my name</label>
        </p>
        <div class="actions">
            <button type="submit" class="btn">Add to the playlist</button>
        </div>
    </form>
</dialog>

<script>
    var dlg = document.getElementById('postDialog');
    document.getElementById('openForm').addEventListener('click', function () { dlg.showModal(); });
    document.getElementById('closeForm').addEventListener('click', function () { dlg.close(); });
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
    var nameInput = document.getElementById('author');
    var songInput = document.getElementById('body');
    nameInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.isComposing) { e.preventDefault(); songInput.focus(); }
    });
    songInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); songInput.form.requestSubmit(); }
    });
    if (dlg.dataset.open === '1') dlg.showModal();   // reopen if there was a form error

    var themeBtn = document.getElementById('themeBtn');
    function paintTheme() {
        var dark = document.documentElement.getAttribute('data-theme') === 'dark';
        themeBtn.textContent = dark ? '\u2600\uFE0E' : '\u263E';
        themeBtn.title = dark ? 'Switch to light theme' : 'Switch to night theme';
        themeBtn.setAttribute('aria-label', themeBtn.title);
    }
    themeBtn.addEventListener('click', function () {
        var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        try { localStorage.setItem('theme', next); } catch (e) {}
        paintTheme();
    });
    paintTheme();

</script>
</body>
</html>
