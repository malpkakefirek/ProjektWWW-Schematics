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

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$name = $_POST['name'];
$description = $_POST['description'];
$version_start = $_POST['version_start'];
$version_end = $_POST['version_end'];
$page = $_REQUEST['formPage'];
$user_id = $_REQUEST['userId'];
$link = $_POST['mod_url'];
$target_dir = "assets/" . $page . "/";

if ($page == 'schematics') {
    $file = 'schematic';
} elseif ($page == 'resourcepacks') {
    $file = 'resourcepack';
} elseif ($page == 'mods') {
    $file = 'mod';
} else {
    $file = 'unknown';
}

// Abort if user is not logged in
if ($user_id == '') {
    if ($page == '') {
        header('location:/?status=fail&reason=timeout');
        exit();
    }
    header('location:' . $page . '?status=fail&reason=login');
    exit();
}

echo '<br>' . print_r($_FILES["fileToUpload"]) . '<br>';

$target_schematic = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$file_name = pathinfo($target_schematic, PATHINFO_FILENAME);
$schematicFileType = strtolower(pathinfo($target_schematic,PATHINFO_EXTENSION));

$target_image = $target_dir . $file_name . "." . pathinfo($_FILES["imageToUpload"]["name"], PATHINFO_EXTENSION);
$imageFileType = strtolower(pathinfo($target_image,PATHINFO_EXTENSION));

$uploadOk = 0;
if(isset($_POST["submit"])) {
    $uploadOk = 1;
}
// Check if file already exists
if (file_exists($target_schematic)) {
    header('location:' . $page . '?status=fail&reason=name&file='. $file);
    exit();
}
if ($page != "mods" && file_exists($target_image)) {
    header('location:' . $page . '?status=fail&reason=name&file=image');
    exit();
}

// Check file size 25MB
if ($_FILES["fileToUpload"]["size"] > 25000000) {
    header('location:' . $page . '?status=fail&reason=size&file='. $file);
    exit();
}
// Check file size 25MB
if ($_FILES["imageToUpload"]["size"] > 25000000) {
    header('location:' . $page . '?status=fail&reason=size&file=image');
    exit();
}

// Allow certain file formats
if($page == "schematics" && $schematicFileType != "litematic") {
    header('location:' . $page . '?status=fail&reason=type&file=schematic');
    exit();
}
/* no need
if($page == "redstone" && $schematicFileType != "???") {
    header('location:' . $page . '?status=fail&reason=type&file=schematic');
    exit();
}
*/
if($page == "resourcepacks" && $schematicFileType != "zip" && $schematicFileType != "rar") {
    header('location:' . $page . '?status=fail&reason=type&file=resourcepack');
    exit();
}
if($page == "mods" && $schematicFileType != "jar") {
    header('location:' . $page . '?status=fail&reason=type&file=mod');
    exit();
}

if($page != "mods" && !in_array($imageFileType, array('png', 'jpg', 'jpeg', 'gif'))) {
    header('location:' . $page . '?status=fail&reason=type&file=image');
    exit();
}

if ($uploadOk == 0) {
    header('location:' . $page . '?status=fail&failid=1');
    exit();
} else {
    if (
        $name
        && mb_strlen($name) <= 24
        && $description
        && mb_strlen($description) <= 240
        && $version_start
        && $version_end
        && $file_name
        && mb_strlen($file_name) <= 225
    ) {
        $check1 = move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_schematic);
        $check2 = $page == "mods" ? true : move_uploaded_file($_FILES["imageToUpload"]["tmp_name"], $target_image);
        if ($check1 && $check2) {
            if($page == "mods"){  
                $stmt = DB::getInstance()->prepare("
                    INSERT INTO $page
                    (name, date_added, description, version_start, version_end, file_name, userId, link)
                    VALUES
                    (:name, CURDATE(), :description, :version_start, :version_end, :file_name, :user_id, :link)
                ");
                $stmt->execute([
                    ':name' => $name,
                    ':description'      => $description,
                    ':version_start'    => $version_start,
                    ':version_end'      => $version_end,
                    ':file_name'        => $file_name,
                    ':user_id'          => $user_id,
                    ':link'             => $link,
                ]);
                $new_id = DB::getInstance()->lastInsertId();
                header('location:' . $page . '?status=success&name=' . htmlspecialchars($name));
                exit();
            }else{
                $stmt = DB::getInstance()->prepare("
                    INSERT INTO $page
                    (name, date_added, description, version_start, version_end, file_name, userId)
                    VALUES
                    (:name, CURDATE(), :description, :version_start, :version_end, :file_name, :user_id)
                ");
                $stmt->execute([
                    ':name' => $name,
                    ':description'      => $description,
                    ':version_start'    => $version_start,
                    ':version_end'      => $version_end,
                    ':file_name'        => $file_name,
                    ':user_id'          => $user_id,
                ]);
                $new_id = DB::getInstance()->lastInsertId();
                header('location:' . $page . '?status=success&name=' . htmlspecialchars($name));
                exit();
            }
        } else {
            if (!$check1 && $check2) {  // Schematic failed
                header('location:' . $page . '?status=fail&reason=upload&file=' . $file);
                exit();
            } elseif ($check1) {    // Image failed
                header('location:' . $page . '?status=fail&reason=upload&file=image');
                exit();
            } else {    // Both failed
                header('location:' . $page . '?status=fail&reason=upload&file=both');
                exit();
            }
        }
    } else {
        header('location:' . $page . '?status=fail&reason=length');
        exit();
    }
}

?>