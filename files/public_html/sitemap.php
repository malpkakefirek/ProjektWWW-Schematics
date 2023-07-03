<?php

include("config.inc.php");
class DB {
    private static $dbh = null; // tutaj będziemy przechowywać obiekt PDO

    public static function getInstance(): PDO {

        // Jeśli w zmiennej statycznej self::$dbh nie mamy jeszcze utworzonego połączenia do bazy danych to je tworzymy.
        if (!self::$dbh) {
            /*
             * Instrukcja try-catch występuje w wielu językach programowania. Służy ona do przechwycenia wyjątku (błędu)
             * rzuconego przez kod znajdujący się w środku bloku "try" oraz odpowiednią reakcję na rzucony wyjątek
             * w bloku "catch".
             */
            try {
                /*
                 * Tworzymy nowy obiekt wbudowanej w PHP klasy PDO, służącej do komunikacji z bazą danych, przekazując
                 * do jego konstruktora parametry połączenia do bazy danych. Obiekt PDO reprezentujący połączenie z bazą
                 * danych przypisujemy do zmiennej statycznej self::$dbh. Dzięki temu w dowolnym miejscu kodu możemy
                 * uzyskać połączenie do bazy danych za pomocą DB::getInstance().
                 * Za pomocą metody "setAttribute" ustawiamy na utworzonym obiekcie, aby PDO obsługiwało błędy za pomocą
                 * wyjątków (są tez inne tryby, na przykład zwracanie standardowych błędów PHP).
                 * PDO jest potężną biblioteką. Potrafi obsługiwać różne typy baz w uniwersalny sposób, dzięki temu
                 * zmiana oprogramowania serwera bazy danych nie musi oznaczać zmiany kodu/zapytań w PHP.
                 * Opis klasy PDO i jej metod znajdziesz na stronie: https://www.php.net/manual/en/class.pdo
                */
                self::$dbh = new PDO(
                    'mysql:host=' . CONFIG['db_host'] . ';dbname=' . CONFIG['db_name'] . ';charset=utf8mb4',
                    CONFIG['db_user'],
                    CONFIG['db_password']
                );
                self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                exit("Cannot connect to the database: " . $e->getMessage());
            }
        }
        // Zwracamy obiekt PDO, reprezentujący połączenie z bazą danych.
        return self::$dbh;
    }
}

header("Content-Type: application/xml; charset=utf-8");

$db = DB::getInstance();
$stmt = $db->prepare('SELECT id FROM schematics');
$stmt->execute();
$ids_schematics = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $db->prepare('SELECT id FROM redstone');
$stmt2->execute();
$ids_redstone = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$stmt3 = $db->prepare('SELECT id FROM mods');
$stmt3->execute();
$ids_mods = $stmt3->fetchAll(PDO::FETCH_ASSOC);

$stmt4 = $db->prepare('SELECT id FROM resourcepacks');
$stmt4->execute();
$ids_resourcepacks = $stmt4->fetchAll(PDO::FETCH_ASSOC);

$pages = ["schematics", "mods", "resourcepacks", "redstone", "login", "register"];

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

foreach ($pages as $page) {
    echo '<url>';
    echo '<loc>http://s413180.labagh.pl/'.$page.'</loc>';
    echo '<changefreq>always</changefreq>';
    echo '<priority>1.0</priority>';
    echo '</url>';
}

foreach ($ids_schematics as $id) {
    echo '<url>';
    echo '<loc>http://s413180.labagh.pl/schematics/'.$id['id'].'</loc>';
    echo '<changefreq>daily</changefreq>';
    echo '<priority>0.7</priority>';
    echo '</url>';
}

foreach ($ids_redstone as $id) {
    echo '<url>';
    echo '<loc>http://s413180.labagh.pl/redstone/'.$id['id'].'</loc>';
    echo '<changefreq>daily</changefreq>';
    echo '<priority>0.7</priority>';
    echo '</url>';
}

foreach ($ids_resourcepacks as $id) {
    echo '<url>';
    echo '<loc>http://s413180.labagh.pl/resourcepacks/'.$id['id'].'</loc>';
    echo '<changefreq>daily</changefreq>';
    echo '<priority>0.7</priority>';
    echo '</url>';
}

foreach ($ids_mods as $id) {
    echo '<url>';
    echo '<loc>http://s413180.labagh.pl/mods/'.$id['id'].'</loc>';
    echo '<changefreq>daily</changefreq>';
    echo '<priority>0.7</priority>';
    echo '</url>';
}


echo '</urlset>';
?>