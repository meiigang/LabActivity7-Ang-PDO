<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    $_SESSION['flash'] = 'Please log in to continue.';
    header('Location: login.php');
    exit;
}

require __DIR__ . '/db.php';

$userId = (int) $_SESSION['user_id'];
$errors = [];
$message = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $content = trim((string) ($_POST['content'] ?? ''));

    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        header('Location: login.php');
        exit;
    }

    if ($content === '' || strlen($content) > 5000) {
        $errors[] = 'Text must be between 1 and 5000 characters.';
    } elseif ($action === 'create_post') {
        $statement = $pdo->prepare('INSERT INTO posts (user_id, content) VALUES (:user_id, :content)');
        $statement->execute(['user_id' => $userId, 'content' => $content]);
        $message = 'Post published.';
    } elseif ($action === 'create_comment') {
        $postId = (int) ($_POST['post_id'] ?? 0);
        $statement = $pdo->prepare('SELECT id FROM posts WHERE id = :post_id');
        $statement->execute(['post_id' => $postId]);
        if ($statement->fetch() === false) {
            $errors[] = 'The selected post does not exist.';
        } else {
            $statement = $pdo->prepare(
                'INSERT INTO comments (post_id, user_id, content) VALUES (:post_id, :user_id, :content)'
            );
            $statement->execute(['post_id' => $postId, 'user_id' => $userId, 'content' => $content]);
            $message = 'Comment added.';
        }
    } elseif ($action === 'edit_post') {
        $postId = (int) ($_POST['post_id'] ?? 0);
        $statement = $pdo->prepare(
            'UPDATE posts SET content = :content, is_edited = 1, updated_at = CURRENT_TIMESTAMP
             WHERE id = :post_id AND user_id = :user_id'
        );
        $statement->execute(['content' => $content, 'post_id' => $postId, 'user_id' => $userId]);
        $message = $statement->rowCount() > 0 ? 'Post updated.' : 'You may only edit your own posts.';
    } elseif ($action === 'edit_comment') {
        $commentId = (int) ($_POST['comment_id'] ?? 0);
        $statement = $pdo->prepare(
            'UPDATE comments SET content = :content, is_edited = 1, updated_at = CURRENT_TIMESTAMP
             WHERE id = :comment_id AND user_id = :user_id'
        );
        $statement->execute(['content' => $content, 'comment_id' => $commentId, 'user_id' => $userId]);
        $message = $statement->rowCount() > 0 ? 'Comment updated.' : 'You may only edit your own comments.';
    }
}

$posts = $pdo->query(
    'SELECT posts.*, users.name, users.email FROM posts JOIN users ON users.id = posts.user_id
     ORDER BY posts.created_at DESC, posts.id DESC'
)->fetchAll();
$comments = $pdo->query(
    'SELECT comments.*, users.name, users.email FROM comments JOIN users ON users.id = comments.user_id
     ORDER BY comments.created_at ASC, comments.id ASC'
)->fetchAll();
$commentsByPost = [];
foreach ($comments as $comment) {
    $commentsByPost[(int) $comment['post_id']][] = $comment;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Feed</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Blog Feed</h1>
    <p>Signed in as <?= htmlspecialchars((string) $_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) $_SESSION['user_email'], ENT_QUOTES, 'UTF-8') ?>)</p>
    <?php foreach ($errors as $error): ?><p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endforeach; ?>
    <?php if ($message !== ''): ?><p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

    <form method="post"><button type="submit" name="action" value="logout">Log out</button></form>

    <h2>New post</h2>
    <form method="post">
        <input type="hidden" name="action" value="create_post">
        <label>Content <textarea name="content" maxlength="5000" required></textarea></label><br>
        <button type="submit">Publish</button>
    </form>

    <?php foreach ($posts as $post): ?>
        <article>
            <h2>Post<?= $post['is_edited'] ? ' (edited)' : '' ?></h2>
            <p>By <?= htmlspecialchars($post['name'], ENT_QUOTES, 'UTF-8') ?> on <?= htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><?= nl2br(htmlspecialchars($post['content'], ENT_QUOTES, 'UTF-8')) ?></p>
            <?php if ((int) $post['user_id'] === $userId): ?>
                <details><summary>Edit post</summary>
                    <form method="post">
                        <input type="hidden" name="action" value="edit_post"><input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
                        <textarea name="content" maxlength="5000" required><?= htmlspecialchars($post['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
                        <button type="submit">Save post</button>
                    </form>
                </details>
            <?php endif; ?>
            <h3>Comments</h3>
            <?php foreach ($commentsByPost[(int) $post['id']] ?? [] as $comment): ?>
                <p><strong><?= htmlspecialchars($comment['name'], ENT_QUOTES, 'UTF-8') ?><?= $comment['is_edited'] ? ' (edited)' : '' ?>:</strong> <?= nl2br(htmlspecialchars($comment['content'], ENT_QUOTES, 'UTF-8')) ?></p>
                <?php if ((int) $comment['user_id'] === $userId): ?>
                    <details><summary>Edit comment</summary>
                        <form method="post">
                            <input type="hidden" name="action" value="edit_comment"><input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                            <textarea name="content" maxlength="5000" required><?= htmlspecialchars($comment['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            <button type="submit">Save comment</button>
                        </form>
                    </details>
                <?php endif; ?>
            <?php endforeach; ?>
            <form method="post">
                <input type="hidden" name="action" value="create_comment"><input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
                <textarea name="content" maxlength="5000" required placeholder="Add a comment"></textarea><button type="submit">Comment</button>
            </form>
        </article>
    <?php endforeach; ?>
</body>
</html>