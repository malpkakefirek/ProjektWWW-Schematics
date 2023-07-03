<?php

if (!defined('IN_INDEX')) { exit("This file can only be included in index.php."); }

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        if (isset($_GET['file_name'])) {
            $file_name = $_GET['file_name'] . " ";
        } else {
            $file_name = "";
        }
        TwigHelper::addMsg("The file " . $file_name . "has been uploaded.", 'success');
    } elseif ($_GET['status'] == 'fail') {
        if (!isset($_GET['reason'])) {
            TwigHelper::addMsg("Unknown error occured", 'error');
        } elseif ($_GET['reason'] == 'name') {
            TwigHelper::addMsg("File with this name already exists!", 'error');
        } elseif ($_GET['reason'] == 'size') {
            TwigHelper::addMsg("Your file is too large!", 'error');
        } elseif ($_GET['reason'] == 'type') {
            TwigHelper::addMsg("Only litematic files are allowed!", 'error');
        } else {
            TwigHelper::addMsg("Unknown fail reason!", 'error');
        }
    } else {
        TwigHelper::addMsg("Unknown status!", 'error');
    }
}

print TwigHelper::getInstance()->render('test1.html', ['page' => $_GET['page']]);