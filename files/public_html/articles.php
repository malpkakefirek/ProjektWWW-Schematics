<?php

/*
 * Adnotacje i wskazówki umieszczono w tym pliku za pomocą komentarzy
 * z gwiazdkami, takimi jak ten, a miejsca do uzupełnienia komentarzami
 * z ukośnikami. Możesz dowolnie modyfikować strukturę tego pliku
 * jeśli uznasz, że tak będzie dla Ciebie wygodniej. Istotne jest
 * tylko zrealizowanie wymagań postawionych w specyfikacji labu.
 */

if (!defined('IN_INDEX')) { exit("This file can only be included in index.php."); }

/*
 * Jeśli użytkownik nie jest zalogowany to wyświetlamy komunikat błędu
 * oraz szablon główny bez żadnej podstrony. Następnie wywołujemy exit(),
 * aby dalsza część tego pliku nie została wykonana.
 */
if (!isset($_SESSION['id'])) {
    TwigHelper::addMsg('Access only for registered users.', 'error');
    print TwigHelper::getInstance()->render('base.html');
    exit();
}

/*
 * Formularz dodawania artykułu:
 * GET  /articles/add     --> GET  /index.php?page=articles&add=
 * Odbiór formularza dodawania artykułu:
 * POST /articles/add     --> POST /index.php?page=articles&add=
 *
 * Parametr "add" może być pusty - istotne jest jego wystąpienie
 * w URLu, które wykrywamy za pomocą isset($_GET['add']), co oznacza,
 * że wybrano podstronę dotyczącą dodawania artykułu.
 */
if (isset($_GET['add'])) {

    /*
     * Fragment poniżej zakłada, że w formularzu dodawania, dla tytułu i treści
     * nadano nazwy "title" oraz "content". Jeśli tak nie jest to popraw
     * warunek if.
     */
    if (isset($_POST['title']) && isset($_POST['content'])) {

        if ($_POST['title']
            && $_POST['content']
            && mb_strlen($_POST['title']) <= 255
            && mb_strlen($_POST['content']) <= 2000
        ) {
            $stmt = DB::getInstance()->prepare("
                INSERT INTO articles
                (user_id, title, content, created)
                VALUES
                (:user_id, :title, :content, NOW())
            ");
            $stmt->execute([
                ':user_id' => $_SESSION['id'],
                ':title'   => $_POST['title'],
                ':content' => $_POST['content']
            ]);

            $new_id = DB::getInstance()->lastInsertId();

            header('Location: /articles/show/' . $new_id);
        } else {
            TwigHelper::addMsg(
                'The title can be up to 255 and content up to 2000 characters.',
                'error'
            );
        }
    }

    print TwigHelper::getInstance()->render('articles_add.html');


/*
 * Podgląd artykułu:
 * GET /articles/show/<id>     --> GET /index.php?page=articles&show=<id>
 */
} elseif (isset($_GET['show']) && intval($_GET['show']) > 0) {
    $id = intval($_GET['show']);

    $stmt = DB::getInstance()->prepare("SELECT * FROM articles WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($article) {
        print TwigHelper::getInstance()->render('articles_show.html', [
            'article' => $article
        ]);
    } else {
        header('Location: /articles');
    }


/*
 * Formularz edycji artykułu:
 * GET  /articles/edit/<id>     --> GET  /index.php?page=articles&edit=<id>
 * Odbiór formularza edycji artykułu:
 * POST /articles/edit/<id>     --> POST /index.php?page=articles&edit=<id>
 */
} elseif (isset($_GET['edit']) && intval($_GET['edit']) > 0) {
    $id = intval($_GET['edit']);

    $stmt = DB::getInstance()->prepare("SELECT * FROM articles WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$article || $article['user_id'] != $_SESSION['id']) {
        header('Location: /articles');
        exit();
    }

    /*
     * Fragment poniżej zakłada, że w formularzu edycji, dla tytułu i treści
     * nadano nazwy "title" oraz "content". Jeśli tak nie jest to popraw
     * warunek if.
     */
    if (isset($_POST['title']) && isset($_POST['content'])) {

        if ($_POST['title']
            && $_POST['content']
            && mb_strlen($_POST['title']) <= 255
            && mb_strlen($_POST['content']) <= 2000
        ) {
            $stmt = DB::getInstance()->prepare("
                UPDATE articles
                SET title = :title, content = :content
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':title'   => $_POST['title'],
                ':content' => $_POST['content']
            ]);

            header('Location: /articles/show/' . $id);
        } else {
            TwigHelper::addMsg(
                'The title can be up to 255 and content up to 2000 characters.',
                'error'
            );
        }
    }

    print TwigHelper::getInstance()->render('articles_edit.html', [
        'article' => $article
    ]);


/*
 * Lista artykułów:
 * GET /articles                 --> GET /index.php?page=articles
 * Usuwanie artykułu:
 * GET /articles/delete/<id>     --> GET /index.php?page=articles&delete=<id>
 */
} else {

    if (isset($_GET['delete']) && intval($_GET['delete']) > 0) {
        $id = intval($_GET['delete']);

        $stmt = DB::getInstance()->prepare("
            DELETE FROM articles WHERE id = :id AND user_id = :user_id
        ");
        $stmt->execute([':id' => $id, ':user_id' => $_SESSION['id']]);

        TwigHelper::addMsg('The article has been deleted.', 'success');
    }

    $stmt = DB::getInstance()->prepare("
        SELECT a.*, u.email
        FROM articles a INNER JOIN users u ON a.user_id = u.id
        ORDER BY a.id DESC
    ");
    $stmt->execute();
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    print TwigHelper::getInstance()->render('articles.html', [
        'articles' => $articles
    ]);
}

