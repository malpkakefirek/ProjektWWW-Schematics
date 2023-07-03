<?php


if (!defined('IN_INDEX')) { exit("This file can only be included in index.php."); }

$versions = CONFIG['versions'];

// View in 3D redirect
if (isset($_GET['view'])) {
    if ($_GET['view'] == '') {
        header('location:/mods');
        exit();
    }

    // Mod
    $mod_id = $_GET['view'];
    $schm = DB::getInstance()->prepare("SELECT * FROM mods WHERE :id=id;");
    $schm->execute([':id'=>$mod_id]);
    $mod = $schm->fetch();

    // Exit if no mod found
    if (!$mod) {
        header('location:/mods?status=fail&reason=id');
        exit();
    }

    // Rating
    $sql_avg = "
        SELECT 
            AVG(rating) AS avg_rating 
        FROM 
            mods_ratings 
        WHERE 
            modId = :mod_id";
    $avg_query = DB::getInstance()->prepare($sql_avg);
    $avg_query->execute([':mod_id' => $mod['id']]);
    $avg_result = $avg_query->fetch(PDO::FETCH_ASSOC);
    $avg_rating = $avg_result['avg_rating'];

    // Mod creator
    $creator_id = $mod['userId'];
    $schm = DB::getInstance()->prepare("SELECT * FROM users WHERE id=:userId;");
    $schm->execute([':userId' => $creator_id]);
    $creator = $schm->fetch(PDO::FETCH_ASSOC);

    $basename = 'assets/mods/' . $mod['file_name'];
    $imageExtensions = ['png', 'jpg', 'jpeg', 'gif'];

    foreach ($imageExtensions as $extension) {
        $imagePath = $basename . '.' . $extension;
        if (file_exists($imagePath)) {
            $image_path = '/' . $imagePath;
            break;
        }
    }

    $filePath = $basename . '.jar';
    if (file_exists($filePath)) {
        $file_path = $filePath;
    }

    if(isset($_SESSION['id'])){
        $rating_query = DB::getInstance()->prepare("SELECT * FROM mods_ratings WHERE modId=:mod_id AND userId=:userid;");
        $rating_query->execute([
            ':mod_id' => $mod['id'],
            ':userid' => $_SESSION['id'],
        ]);
        $rating_result = $rating_query->fetch(PDO::FETCH_ASSOC);
        if (isset($rating_result['rating'])) {
            $user_rating = $rating_result['rating'];
        } else {
            $user_rating = null;
        }
    }else{
        $user_rating = null;
    }
    // Link
    $sql_link = "
        SELECT 
            link 
        FROM 
            mods 
        WHERE 
            id = :mod_id";
    $schm2 = DB::getInstance()->prepare($sql_link);
    $schm2->execute([':mod_id' => $mod['id']]);
    $link_result = $schm2->fetch(PDO::FETCH_ASSOC);
    $link = $link_result['link'];

    $file_size = filesize($file_path);
    $filesize_kb = round($file_size / 1024, 2);
    $filesize_mb = round($file_size / (1024 * 1024), 2);

    print TwigHelper::getInstance()->render('mods_view.html', [
        'redirect' => 'mods',
        'id' => $mod['id'],
        'name' => $mod['name'],
        'date_added' => $mod['date_added'],
        'description' => $mod['description'],
        'version_start' => $mod['version_start'],
        'version_end' => $mod['version_end'],
        'file_name' => $mod['file_name'],
        'userId' => $mod['userId'],
        'link' => $mod['link'],
        'avg_rating' => $avg_rating,
        'username' => $creator['username'],
        'email' => $creator['email'],
        'file_path' => $file_path,
        'user_rating' => $user_rating,
        'file_size' => $file_size,
        'file_size_kb' => $filesize_kb,
        'file_size_mb' => $filesize_mb,
    ]);
    exit();
}


// Upload alerts
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        if (isset($_GET['name'])) {
            $file_name = $_GET['name'] . " ";
        } else {
            $file_name = "";
        }
        TwigHelper::addMsg("The file " . $file_name . "has been uploaded.", 'success');
    } elseif ($_GET['status'] == 'fail') {
        if (!isset($_GET['reason'])) {
            TwigHelper::addMsg("Unknown error occured", 'error');
        } elseif ($_GET['reason'] == 'login') {
            TwigHelper::addMsg("You have to login to upload files!", 'error');
        } elseif ($_GET['reason'] == 'name') {
            TwigHelper::addMsg(ucwords($_GET['file']) . " with this name already exists!", 'error');
        } elseif ($_GET['reason'] == 'size') {
            TwigHelper::addMsg(ucwords($_GET['file']) . " is too large! Maximum size is 25 MB", 'error');
        } elseif ($_GET['reason'] == 'length') {
            TwigHelper::addMsg("The name can be up to 24, description up to 240, and file name up to 255 characters!", 'error');
        } elseif ($_GET['reason'] == 'type') {
            if ($_GET['file'] == "image") {
                TwigHelper::addMsg("Only '.png', '.jpg', '.jpeg' and '.gif' images are allowed!", 'error');
            } elseif ($_GET['file'] == "schematic") {
                TwigHelper::addMsg("Only '.litematic' schematics are allowed!", 'error');
            } elseif ($_GET['file'] == "resourcepack") {
                TwigHelper::addMsg("Only '.zip/.rar' resourcepacks are allowed!", 'error');
            } elseif ($_GET['file'] == "mod") {
                TwigHelper::addMsg("Only '.jar' mods are allowed!", 'error');
            } else {
                TwigHelper::addMsg("Unknown file type error!", 'error');
            }
        } elseif ($_GET['reason'] == 'upload') {
            if ($_GET['file'] == "image") {
                TwigHelper::addMsg("Image failed to upload!", 'error');
            } elseif ($_GET['file'] == "schematic") {
                TwigHelper::addMsg("Schematic failed to upload!", 'error');
            } elseif ($_GET['file'] == "resourcepack") {
                TwigHelper::addMsg("Resourcepack failed to upload!", 'error');
            } elseif ($_GET['file'] == "mod") {
                TwigHelper::addMsg("Mod failed to upload!", 'error');
            } elseif ($_GET['file'] == "image") {
                TwigHelper::addMsg("Image failed to upload!", 'error');
            } elseif ($_GET['file'] == "both") {
                TwigHelper::addMsg("Both files failed to upload!", 'error');
            } else {
                TwigHelper::addMsg("Unknown file upload error!", 'error');
            }
        } else {
            TwigHelper::addMsg("Unknown fail reason!", 'error');
        }
    } else {
        TwigHelper::addMsg("Unknown status!", 'error');
    }
}

// Rating form handling
$data = json_decode(file_get_contents('php://input'), true);
if (isset($data['stars']) && isset($data['product-id']) && isset($data['user-id'])) {
    $rating = $data['stars'];
    $product_id = $data['product-id'];
    $user_id = $data['user-id'];

    // Let's do this :)
    // Send info to database:
    $db = DB::getInstance();

    // check if user added rating to the product-id already
    $check = $db->prepare("SELECT id FROM mods_ratings WHERE modId = ? AND userId = ?");
    $check->execute([$product_id, $user_id]);
    $results_for_check = $check->fetch();

    // if it exists, update, if not create new
    if($results_for_check){
        $update_query = $db->prepare("UPDATE mods_ratings SET rating = ? WHERE modId = ? AND userId = ?");
        $update_query->execute([$rating, $product_id, $user_id]);
    } else {
        $insert_query = $db->prepare("INSERT INTO mods_ratings (modId, userId, rating) VALUES (?, ?, ?)");
        $insert_query->execute([$product_id, $user_id, $rating]);
    }


    exit();
}
if (isset($data['delete']) && isset($data['product-id']) && isset($data['user-id'])) {
    $product_id = $data['product-id'];
    $user_id = $data['user-id'];

    // Let's do this :)
    // Send info to database:
    $db = DB::getInstance();

    // check if user added rating to the product-id already
    $check = $db->prepare("SELECT id FROM mods_ratings WHERE modId = ? AND userId = ?");
    $check->execute([$product_id, $user_id]);
    $results_for_check = $check->fetch();

    // if it exists, delete it
    if ($results_for_check) {
        $delete_query = $db->prepare("DELETE FROM mods_ratings WHERE modId = ? AND userId = ?");
        $delete_query->execute([$product_id, $user_id]);
        echo "Record deleted successfully!";
    } else {
        echo "Record doesn't exist.";
    }


    exit();
}

// Get version from cookie
if(isset($_COOKIE['version'])) {
    $version = $_COOKIE['version'];
} else {
    $version = '1.' . $versions[sizeOf($versions)-1];
}

$ver = (float)(substr($version, 2));
$epsilon = 0.001; // fix for bad float comparison
$sql = "SELECT * FROM mods WHERE $ver + $epsilon > version_start AND $ver - $epsilon < version_end;";
$schm = DB::getInstance()->prepare($sql);
$schm->execute();

$results = $schm->fetchAll(PDO::FETCH_ASSOC);

// Determine the variables
foreach ($results as &$result) {
    $basename = 'assets/mods/' . $result['file_name'];
    /* Image not needed
    $imageExtensions = ['png', 'jpg', 'jpeg', 'gif'];

    foreach ($imageExtensions as $extension) {
        $imagePath = $basename . '.' . $extension;
        if (file_exists($imagePath)) {
            $result['image'] = $imagePath;
            break;
        }
    }
    */ 
    //Let's get a link instead
    $sql_link = "
        SELECT 
            link 
        FROM 
            mods 
        WHERE 
            id = :mod_id";
    $schm2 = DB::getInstance()->prepare($sql_link);
    $schm2->bindParam(':mod_id', $result['id'], PDO::PARAM_INT);
    $schm2->execute();
    $link_result = $schm2->fetch(PDO::FETCH_ASSOC);
    $result['link'] = $link_result['link'];

    $basename = 'assets/mods/' . $result['file_name'];
    $filePath = $basename . '.jar';
    if (file_exists($filePath)) {
        $result['file_path'] = $filePath;
    }

    $user_id = $result['userId'];
    $sql = "SELECT * FROM users WHERE id=$user_id;";
    $schm = DB::getInstance()->prepare($sql);
    $schm->execute();
    $user = $schm->fetch(PDO::FETCH_ASSOC);
    $result['user'] = $user['username'];

    $sql_avg = "
        SELECT 
            AVG(rating) AS avg_rating 
        FROM 
            mods_ratings 
        WHERE 
            modId = :mod_id";
    $avg_query = DB::getInstance()->prepare($sql_avg);
    $avg_query->bindParam(':mod_id', $result['id'], PDO::PARAM_INT);
    $avg_query->execute();
    $avg_result = $avg_query->fetch(PDO::FETCH_ASSOC);
    $result['avg_rating'] = $avg_result['avg_rating'];

    if(isset($_SESSION['id'])){
        $mod_id = $result['id'];
        $rating_query = DB::getInstance()->prepare("SELECT * FROM mods_ratings WHERE modId=:mod_id AND userId=:userid;");
        $rating_query->execute([
            ':mod_id' => $mod_id,
            ':userid' => $_SESSION['id'],
        ]);
        $rating_result = $rating_query->fetch(PDO::FETCH_ASSOC);
        if (isset($rating_result['rating'])) {
            $result['user_rating'] = $rating_result['rating'];
        } else {
            $result['user_rating'] = null;
        }
    }else{
        $result['user_rating'] = null;
    }

}
unset($result);


print TwigHelper::getInstance()->render('mods.html', ['page' => $_GET['page'], 'results' => $results, 'versions' => $versions, 'current_version' => $version]);