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

$versions = CONFIG['versions'];

// View in 3D redirect
if (isset($_GET['view'])) {
    if ($_GET['view'] == '') {
        header('location:/schematics');
        exit();
    }
    if ($_GET['type'] == '') {
        header('location:/#');
        exit();
    }

    if($_GET['type']=="schematics"){
        // Schematic
        $schematic_id = $_GET['view'];
        $schm = DB::getInstance()->prepare("SELECT * FROM schematics WHERE :id=id;");
        $schm->execute([':id'=>$schematic_id]);
        $schematic = $schm->fetch();

        // Exit if no schematic found
        if (!$schematic) {
            header('location:/schematics?status=fail&reason=id');
            exit();
        }

        // Rating
        $sql_avg = "
            SELECT 
                AVG(rating) AS avg_rating 
            FROM 
                schematic_ratings 
            WHERE 
                schematicId = :schematic_id";
        $avg_query = DB::getInstance()->prepare($sql_avg);
        $avg_query->execute([':schematic_id' => $schematic['id']]);
        $avg_result = $avg_query->fetch(PDO::FETCH_ASSOC);
        $avg_rating = $avg_result['avg_rating'];
    }
    if($_GET['type']=="redstone"){
        // redstone
        $redstone_id = $_GET['view'];
        $schm = DB::getInstance()->prepare("SELECT * FROM redstone WHERE :id=id;");
        $schm->execute([':id'=>$redstone_id]);
        $redstone = $schm->fetch();

        // Exit if no redstone found
        if (!$redstone) {
            header('location:/redstone?status=fail&reason=id');
            exit();
        }

        // Rating
        $sql_avg = "
            SELECT 
                AVG(rating) AS avg_rating 
            FROM 
                redstone_ratings 
            WHERE 
                redstoneId = :redstone_id";
        $avg_query = DB::getInstance()->prepare($sql_avg);
        $avg_query->execute([':redstone_id' => $redstone['id']]);
        $avg_result = $avg_query->fetch(PDO::FETCH_ASSOC);
        $avg_rating = $avg_result['avg_rating'];
    }

    // Schematic creator
    // $creator_id = $schematic['userId'];
    // $schm = DB::getInstance()->prepare("SELECT * FROM users WHERE id=:userId;");
    // $schm->execute([':userId' => $creator_id]);
    // $creator = $schm->fetch(PDO::FETCH_ASSOC);

    // $basename = 'assets/schematics/' . $schematic['file_name'];
    // $imageExtensions = ['png', 'jpg', 'jpeg', 'gif'];

    // foreach ($imageExtensions as $extension) {
    //     $imagePath = $basename . '.' . $extension;
    //     if (file_exists($imagePath)) {
    //         $image_path = '/' . $imagePath;
    //         break;
    //     }
    // }

    // $filePath = $basename . '.litematic';
    // if (file_exists($filePath)) {
    //     $file_path = $filePath;
    // }

    // if(isset($_SESSION['id'])){
    //     $rating_query = DB::getInstance()->prepare("SELECT * FROM schematic_ratings WHERE schematicId=:schem_id AND userId=:userid;");
    //     $rating_query->execute([
    //         ':schem_id' => $schematic['id'],
    //         ':userid' => $_SESSION['id'],
    //     ]);
    //     $rating_result = $rating_query->fetch(PDO::FETCH_ASSOC);
    //     if (isset($rating_result['rating'])) {
    //         $user_rating = $rating_result['rating'];
    //     } else {
    //         $user_rating = null;
    //     }
    // }else{
    //     $user_rating = null;
    // }

    header('Content-Type: application/json');
    echo json_encode([
        // 'redirect' => 'schematics',
        // 'id' => $schematic['id'],
        // 'name' => $schematic['name'],
        // 'date_added' => $schematic['date_added'],
        // 'description' => $schematic['description'],
        // 'version_start' => $schematic['version_start'],
        // 'version_end' => $schematic['version_end'],
        // 'file_name' => $schematic['file_name'],
        // 'userId' => $schematic['userId'],
        'avg_rating' => $avg_rating,
        // 'username' => $creator['username'],
        // 'email' => $creator['email'],
        // 'image_path' => $image_path,
        // 'file_path' => $file_path,
        // 'user_rating' => $user_rating,
    ]);
}

