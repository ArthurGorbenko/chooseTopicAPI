<?php
include "connection.php";

$userInfo = identifyUser($_POST["api_key"]);
if ($userInfo == null) {
    exit("You aren't legal customer.");
}

switch ($_POST["action"]) {
    case "addStudents":
        $userInfo["role_id"] > 1 && addStudents();
        break;
    case "getStudents":
        $userInfo["role_id"] >= 1 && getStudents();
        break;
    case "addList":
        $userInfo["role_id"] > 1 && addList();
        break;
    case "getLists":
        $userInfo["role_id"] >= 1 && getLists();
        break;
    case "getList":
        $userInfo["role_id"] >= 1 && getList();
        break;
    case "editStudents":
        $userInfo["role_id"] > 1 && editStudents();
        break;
    case "takeItem":
        $userInfo["role_id"] >= 1  && procceedItem($userInfo["id"]);
        break;
    case "giveItem":
        $userInfo["role_id"] > 1 
            ? true : 
            exit("You are'n allowed give items.") 
            && procceedItem($_POST["student_id"]);
        break;
    case "delStudents":
        $userInfo["role_id"] > 1 && delStudents();
        break;
    default:echo "Method isn't valid!";
}

function addStudents()
{
    $arr = explode(",", $_POST["names"]);
    foreach ($arr as $name) {
        $api_key = strrev($name);
        $insert = "INSERT INTO students (name,role_id,api_key) VALUES ('$name',1,'$api_key')";
        queryToDB($insert);
    }
    echo "All students happily added!";
}

function getStudents()
{
    $sql = "SELECT * FROM students";
    $data = queryToDB($sql);
    echo json_encode($data);
}

function addList()
{
    global $conn;
    $listName = $_POST["nameOfList"];
    $insertInLists = "INSERT INTO lists (name) VALUES('$listName');";
    $setLastId = "SET @lastID := LAST_INSERT_ID();";
    $items = explode(".", $_POST["items"]);
    $str = "";
    foreach ($items as $key => $value) {
        $str = $str . "(" . "@lastID," . "'$value'" . "),";
    }
    $str[-1] = ";";
    var_dump($str);
    $insertItems = "INSERT INTO items (list_id,name) VALUES $str";
    if (!mysqli_multi_query($conn, $insertInLists . $setLastId . $insertItems)) {
        printf("Error: %s\n", mysqli_error($conn));
    }
    echo "List was happily added!";
}

function getLists()
{
    $isLong = $_POST["long"];
    if ($isLong) {
        $sql = "SELECT * FROM lists";
        $lists = queryToDB($sql, false);
        foreach ($lists as $key => $value) {
            $sql = "SELECT * FROM items WHERE list_id='" . $value['id'] . "';";
            $lists[$key]['items'] = queryToDB($sql);
        }
        echo json_encode($lists);
    } else {
        $sql = "SELECT * FROM lists";
        $listsShort = queryToDB($sql);
        echo json_encode($listsShort);
    }
}

function getList()
{
    $id = $_POST["id"];
    $sql = "SELECT * FROM items WHERE list_id=$id";
    $list = queryToDB($sql);
    echo json_encode($list);
}

function editStudents()
{
    $students = explode(',', $_POST['students']);
    foreach ($students as $key => $student) {
        $studentInfo = explode(':', $student);
        $idWhereChange = $studentInfo[0];
        $name = $studentInfo[1];
        $api_key = strrev($name);
        $sql = "UPDATE students SET name='$name',api_key='$api_key' WHERE id='$idWhereChange'";
        queryToDB($sql);
    }
    $students = queryToDB("SELECT * FROM students");
    echo json_encode($students);
}

function delStudents()
{
    $students = $_POST['students'];
    $itemsToDelete = explode(',', $students);
    foreach ($itemsToDelete as $key => $value) {
        $sql = "DELETE FROM students WHERE name='$value'";
        queryToDB($sql);
    }
    $students = queryToDB("SELECT * FROM students");
    echo json_encode($students);
}

function procceedItem($id)
{
    $listID = $_POST["list_id"];
    $itemID = $_POST["item_id"];
    $sql = "UPDATE items SET student_id='$id' WHERE list_id='$listID' AND id='$itemID';";
    queryToDB($sql);
    $items = queryToDB("SELECT * FROM items WHERE list_id='$listID' AND id='$itemID'");
    echo json_encode($items);
}

function queryToDB($query)
{
    global $conn;
    $result = mysqli_query($conn, $query);
    if (!$result) {
        printf("Error: %s\n", mysqli_error($conn));
        exit();
    }
    if (is_bool($result)) {
        return;
    }
    $rows = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function identifyUser($key)
{
    $sql = "SELECT id,role_id FROM students WHERE api_key='$key'";
    $result = queryToDB($sql, false);
    if ($result == null) {
        return null;
    } else {
        return $result[0];
    }
}
