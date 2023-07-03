<?php


if (!defined('IN_INDEX')) { exit("This file can only be included in index.php."); }

$versions = CONFIG['versions'];

// View in 3D redirect
if (isset($_GET['view'])) {
    if ($_GET['view'] == '') {
        header('location:/redstone');
        exit();
    }

    // Schematic
    $redstone_id = $_GET['view'];
    $schm = DB::getInstance()->prepare("SELECT * FROM redstone WHERE :id=id;");
    $schm->execute([':id'=>$redstone_id]);
    $schematic = $schm->fetch();

    // Exit if no schematic found
    if (!$schematic) {
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
    $avg_query->execute([':redstone_id' => $schematic['id']]);
    $avg_result = $avg_query->fetch(PDO::FETCH_ASSOC);
    $avg_rating = $avg_result['avg_rating'];

    // Schematic creator
    $creator_id = $schematic['userId'];
    $schm = DB::getInstance()->prepare("SELECT * FROM users WHERE id=:userId;");
    $schm->execute([':userId' => $creator_id]);
    $creator = $schm->fetch(PDO::FETCH_ASSOC);

    $basename = 'assets/redstone/' . $schematic['file_name'];
    $imageExtensions = ['png', 'jpg', 'jpeg', 'gif'];

    foreach ($imageExtensions as $extension) {
        $imagePath = $basename . '.' . $extension;
        if (file_exists($imagePath)) {
            $image_path = '/' . $imagePath;
            break;
        }
    }

    $filePath = $basename . '.litematic';
    if (file_exists($filePath)) {
        $file_path = $filePath;
    }

    if(isset($_SESSION['id'])){
        $rating_query = DB::getInstance()->prepare("SELECT * FROM redstone_ratings WHERE redstoneId=:redstone_id AND userId=:userid;");
        $rating_query->execute([
            ':redstone_id' => $schematic['id'],
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

    print TwigHelper::getInstance()->render('tests/view3d.html', [
        'redirect' => 'redstone',
        'id' => $schematic['id'],
        'name' => $schematic['name'],
        'date_added' => $schematic['date_added'],
        'description' => $schematic['description'],
        'version_start' => $schematic['version_start'],
        'version_end' => $schematic['version_end'],
        'file_name' => $schematic['file_name'],
        'userId' => $schematic['userId'],
        'avg_rating' => $avg_rating,
        'username' => $creator['username'],
        'email' => $creator['email'],
        'image_path' => $image_path,
        'file_path' => $file_path,
        'user_rating' => $user_rating,
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
    $check = $db->prepare("SELECT id FROM redstone_ratings WHERE redstoneId = ? AND userId = ?");
    $check->execute([$product_id, $user_id]);
    $results_for_check = $check->fetch();

    // if it exists, update, if not create new
    if($results_for_check){
        $update_query = $db->prepare("UPDATE redstone_ratings SET rating = ? WHERE redstoneId = ? AND userId = ?");
        $update_query->execute([$rating, $product_id, $user_id]);
    } else {
        $insert_query = $db->prepare("INSERT INTO redstone_ratings (redstoneId, userId, rating) VALUES (?, ?, ?)");
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
    $check = $db->prepare("SELECT id FROM redstone_ratings WHERE redstoneId = ? AND userId = ?");
    $check->execute([$product_id, $user_id]);
    $results_for_check = $check->fetch();

    // if it exists, delete it
    if ($results_for_check) {
        $delete_query = $db->prepare("DELETE FROM redstone_ratings WHERE redstoneId = ? AND userId = ?");
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
$sql = "SELECT * FROM redstone WHERE $ver + $epsilon > version_start AND $ver - $epsilon < version_end;";
$schm = DB::getInstance()->prepare($sql);
$schm->execute();

$results = $schm->fetchAll(PDO::FETCH_ASSOC);

// Determine the image paths
foreach ($results as &$result) {
    $basename = 'assets/redstone/' . $result['file_name'];
    $imageExtensions = ['png', 'jpg', 'jpeg', 'gif'];

    foreach ($imageExtensions as $extension) {
        $imagePath = $basename . '.' . $extension;
        if (file_exists($imagePath)) {
            $result['image'] = $imagePath;
            break;
        }
    }

    $basename = 'assets/redstone/' . $result['file_name'];
    $filePath = $basename . '.litematic';
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
            redstone_ratings 
        WHERE 
            redstoneId = :redstone_id";
    $avg_query = DB::getInstance()->prepare($sql_avg);
    $avg_query->bindParam(':redstone_id', $result['id'], PDO::PARAM_INT);
    $avg_query->execute();
    $avg_result = $avg_query->fetch(PDO::FETCH_ASSOC);
    $result['avg_rating'] = $avg_result['avg_rating'];

    if(isset($_SESSION['id'])){
        $redstone_id = $result['id'];
        $rating_query = DB::getInstance()->prepare("SELECT * FROM redstone_ratings WHERE redstoneId=:redstone_id AND userId=:userid;");
        $rating_query->execute([
            ':redstone_id' => $redstone_id,
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


print TwigHelper::getInstance()->render('redstone.html', ['page' => $_GET['page'], 'results' => $results, 'versions' => $versions, 'current_version' => $version]);